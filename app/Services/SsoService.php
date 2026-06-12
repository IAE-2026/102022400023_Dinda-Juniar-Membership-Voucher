<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Exception;

class SsoService
{
    protected string $baseUrl;
    protected string $tokenEndpoint;
    protected string $jwksEndpoint;
    protected string $cachePrefix;
    protected int $jwksCacheSeconds;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.sso.base_url', ''), '/');
        $this->tokenEndpoint = config('services.sso.token_endpoint');
        $this->jwksEndpoint = config('services.sso.jwks_endpoint');
        $this->cachePrefix = config('services.sso.cache_prefix', 'sso');
        $this->jwksCacheSeconds = (int) config('services.sso.jwks_cache_seconds', 3600);
    }

    /**
     * Login to SSO using form-data (email + password). Returns token and parsed payload.
     *
     * @throws Exception
     */
    public function login(string $email, string $password): array
    {
        $url = $this->buildUrl($this->tokenEndpoint);

        try {
            $response = Http::asForm()->post($url, [
                'email' => $email,
                'password' => $password,
            ]);
        } catch (Exception $e) {
            Log::error('SSO login request failed', ['exception' => $e]);
            throw new Exception('SSO endpoint tidak dapat diakses.');
        }

        if (! $response->successful()) {
            Log::warning('SSO login failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new Exception('Autentikasi SSO gagal.');
        }

        $data = $response->json();

        $token = $data['access_token'] ?? $data['token'] ?? null;
        if (! $token) {
            Log::error('SSO response missing token', ['response' => $data]);
            throw new Exception('Token tidak ditemukan pada response SSO.');
        }

        $payload = $this->parseJwtPayload($token);

        // Cache token per user (optional)
        $cacheKey = $this->cachePrefix . ':token:' . md5($email);
        if (isset($payload['exp']) && is_numeric($payload['exp'])) {
            $ttl = max(60, (int) $payload['exp'] - time());
            if ($ttl > 0) {
                Cache::put($cacheKey, $token, $ttl);
            } else {
                Cache::forever($cacheKey, $token);
            }
        } else {
            Cache::forever($cacheKey, $token);
        }

        return [
            'token' => $token,
            'payload' => (object) $payload,
        ];
    }

    /**
     * Verify JWT using JWKS and return decoded payload as object.
     *
     * @throws Exception
     */
    public function verifyToken(string $token): object
    {
        $keys = $this->getJwksKeys();

        try {
            // firebase/php-jwt v6+ expects (string $jwt, array|string $keyOrKeyArray)
            $decoded = JWT::decode($token, $keys);

            return $decoded;
        } catch (Exception $e) {
            Log::warning('JWT verification failed', ['exception' => $e]);
            // Rethrow with original message to help debugging (middleware will hide in production)
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Extract user information from a valid token.
     * Returns array with keys: name, email, sub, role, raw
     *
     * @throws Exception
     */
    public function getUserFromToken(string $token): array
    {
        $decoded = $this->verifyToken($token);
        // Deep-convert stdClass to associative array so nested properties (e.g. profile.nim) work
        $payload = json_decode(json_encode($decoded), true);

        $profile = $payload['profile'] ?? [];

        return [
            'name' => $payload['name'] ?? ($profile['name'] ?? null),
            'email' => $payload['email'] ?? ($profile['email'] ?? null),
            'sub' => $payload['sub'] ?? ($payload['user_id'] ?? null),
            'role' => $payload['role'] ?? null,
            'raw' => $payload,
        ];
    }

    /**
     * Fetch JWKS, parse into key set and cache it.
     *
     * @return array
     * @throws Exception
     */
    /**
     * Get JWKS keys. Try remote JWKS, then fallback to env-provided JWKS JSON or PEM public key.
     * Returns either an array (keyset) or a single PEM public key string.
     *
     * @return array|string
     * @throws Exception
     */
    protected function getJwksKeys()
    {
        $cacheKey = $this->cachePrefix . ':jwks';
        $cached = Cache::get($cacheKey);
        if ($cached) {
            // cached can be either the original JWKS array or a PEM string
            if (is_array($cached) && isset($cached['keys'])) {
                // parse and return key resources (do not cache parsed resources)
                return JWK::parseKeySet($cached);
            }

            if (is_string($cached)) {
                return $cached; // PEM public key
            }
        }

        $url = $this->buildUrl($this->jwksEndpoint);

        try {
            $response = Http::get($url);
        } catch (Exception $e) {
            Log::warning('JWKS request failed, will try fallback', ['exception' => $e]);
            $response = null;
        }

        if ($response && $response->successful()) {
            $jwksArray = $response->json();
            if (isset($jwksArray['keys']) && is_array($jwksArray['keys'])) {
                // Cache the raw JWKS array (serializable) and parse on demand.
                Cache::put($cacheKey, $jwksArray, $this->jwksCacheSeconds);
                return JWK::parseKeySet($jwksArray);
            }
            Log::warning('JWKS payload invalid, trying fallback', ['payload' => $response->body()]);
        }

        // Fallback: try JWKS JSON from env
        $jwksJson = config('services.sso.jwks_fallback_json');
        if (! empty($jwksJson)) {
            try {
                $jwksArray = json_decode($jwksJson, true);
                if (isset($jwksArray['keys'])) {
                    Cache::put($cacheKey, $jwksArray, $this->jwksCacheSeconds);
                    return JWK::parseKeySet($jwksArray);
                }
            } catch (Exception $e) {
                Log::warning('Failed to parse SSO_JWKS_JSON', ['exception' => $e]);
            }
        }

        // Fallback: single PEM public key
        $publicKey = config('services.sso.public_key');
        if (! empty($publicKey)) {
            // cache the PEM string (serializable)
            Cache::put($cacheKey, $publicKey, $this->jwksCacheSeconds);
            return $publicKey;
        }

        throw new Exception('Gagal mengambil public keys untuk verifikasi token.');
    }

    protected function parseJwtPayload(string $token): array
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) < 2) {
                return [];
            }
            $payloadB64 = $parts[1];
            $payloadJson = base64_decode(strtr($payloadB64, '-_', '+/'));
            $payload = json_decode($payloadJson, true);
            return is_array($payload) ? $payload : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function buildUrl(string $endpoint): string
    {
        if (str_starts_with($endpoint, 'http')) {
            return $endpoint;
        }

        return $this->baseUrl . '/' . ltrim($endpoint, '/');
    }
}
