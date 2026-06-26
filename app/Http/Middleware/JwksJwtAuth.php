<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class JwksJwtAuth
{
    public function handle(Request $request, Closure $next)
    {
        $auth = $request->header('Authorization');
        if (!$auth || !preg_match('/Bearer\s+(\S+)/', $auth, $m)) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $m[1];

        // Test mode: if SSO_TEST_TOKEN is set in env and matches incoming token,
        // accept it without JWKS verification and attach payload from SSO_TEST_PAYLOAD.
        $testToken = env('SSO_TEST_TOKEN');
        if ($testToken && hash_equals($testToken, $token)) {
            $testPayloadJson = env('SSO_TEST_PAYLOAD', json_encode([
                'iss'  => 'iae-central-mock',
                'sub'  => 'test-user@iae.id',
                'aud'  => 'service-c',
                'iat'  => time(),
                'exp'  => time() + 3600,
                'name' => 'Test User',
            ]));
            $payload = json_decode($testPayloadJson);
            $request->attributes->set('jwt_payload', $payload);
            return $next($request);
        }

        $jwksUri = config('services.sso.jwks_uri') ?: env('SSO_JWKS_URI');
        if (!$jwksUri) {
            return response()->json(['message' => 'SSO JWKS URI not configured'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $jwks = Cache::remember('sso_jwks', 60 * 60, function () use ($jwksUri) {
                $resp = Http::timeout(5)->get($jwksUri);
                if ($resp->failed()) {
                    throw new \RuntimeException('Failed to fetch JWKS');
                }
                return $resp->json();
            });

            // Manual verification (avoid firebase/php-jwt API compatibility issues)
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                throw new \RuntimeException('Invalid JWT structure');
            }

            list($b64Header, $b64Payload, $b64Sig) = $parts;
            $headerJson  = base64_decode(strtr($b64Header, '-_', '+/'));
            $payloadJson = base64_decode(strtr($b64Payload, '-_', '+/'));
            $sig         = base64_decode(strtr($b64Sig, '-_', '+/'));

            $header  = json_decode($headerJson);
            $payload = json_decode($payloadJson);
            if (!$header || empty($header->kid)) {
                throw new \RuntimeException('Missing kid in token header');
            }

            $kid = $header->kid;

            // find matching key in JWKS
            $found = null;
            if (isset($jwks['keys']) && is_array($jwks['keys'])) {
                foreach ($jwks['keys'] as $k) {
                    if (isset($k['kid']) && $k['kid'] === $kid) {
                        $found = $k;
                        break;
                    }
                }
            }
            if (!$found) {
                throw new \RuntimeException('No matching JWK for kid: ' . $kid);
            }

            // convert JWK (n,e) to PEM
            if (empty($found['n']) || empty($found['e'])) {
                throw new \RuntimeException('Invalid JWK material');
            }
            $pem = $this->jwkToPem($found['n'], $found['e']);

            $data = $b64Header . '.' . $b64Payload;
            $ok   = openssl_verify($data, $sig, $pem, OPENSSL_ALGO_SHA256);
            if ($ok !== 1) {
                throw new \RuntimeException('Signature verification failed');
            }

            $expectedIss = config('services.sso.issuer') ?: env('SSO_ISSUER');
            if ($expectedIss && (!isset($payload->iss) || $payload->iss !== $expectedIss)) {
                return response()->json(['message' => 'Invalid token issuer'], Response::HTTP_UNAUTHORIZED);
            }

            $expectedAud = config('services.sso.audience') ?: env('SSO_AUDIENCE');
            if ($expectedAud && (!isset($payload->aud) || ($payload->aud !== $expectedAud && !in_array($expectedAud, (array) $payload->aud)))) {
                return response()->json(['message' => 'Invalid token audience'], Response::HTTP_UNAUTHORIZED);
            }

            // attach payload to request for controllers
            $request->attributes->set('jwt_payload', $payload);

            return $next($request);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid token', 'error' => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }
    }

    private function jwkToPem(string $n_b64, string $e_b64): string
    {
        $n = base64_decode(strtr($n_b64, '-_', '+/'));
        $e = base64_decode(strtr($e_b64, '-_', '+/'));

        $modulus       = $this->encodeInteger($n);
        $publicExponent = $this->encodeInteger($e);

        $sequence  = chr(0x30) . $this->encodeLength(strlen($modulus . $publicExponent)) . $modulus . $publicExponent;
        $bitString = chr(0x03) . $this->encodeLength(strlen($sequence) + 1) . chr(0x00) . $sequence;

        $rsaOid = hex2bin('300d06092a864886f70d0101010500');
        $seq2   = chr(0x30) . $this->encodeLength(strlen($rsaOid . $bitString)) . $rsaOid . $bitString;

        $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($seq2), 64, "\n") . "-----END PUBLIC KEY-----\n";
        return $pem;
    }

    private function encodeInteger(string $data): string
    {
        // ensure positive
        if (ord($data[0]) > 0x7f) {
            $data = chr(0x00) . $data;
        }
        return chr(0x02) . $this->encodeLength(strlen($data)) . $data;
    }

    private function encodeLength(int $length): string
    {
        if ($length <= 0x7f) {
            return chr($length);
        }
        $hexLength = ltrim(pack('N', $length), "\x00");
        return chr(0x80 | strlen($hexLength)) . $hexLength;
    }
}
