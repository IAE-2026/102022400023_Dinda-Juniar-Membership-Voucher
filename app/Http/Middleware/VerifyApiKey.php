<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SsoService;
use App\Models\Membership;

class VerifyApiKey
{
    protected SsoService $sso;

    public function __construct(SsoService $sso)
    {
        $this->sso = $sso;
    }

    /**
     * Daftar API Key yang diizinkan (NIM anggota kelompok).
     */
    private function getValidKeys(): array
    {
        $keys = env('IAE_API_KEYS', '102022400023');

        return array_filter(array_map('trim', explode(',', $keys)));
    }

    /**
     * Handle an incoming request.
     *
     * This middleware accepts either:
     * - Authorization: Bearer <token> (preferred) — verified via SSO
     * - X-IAE-KEY: <api_key> (legacy) — validated against env list
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1) Try Bearer token first
        $bearer = $request->bearerToken();
        if (! empty($bearer)) {
            try {
                // Verify token via SsoService. If valid, allow request.
                $this->sso->verifyToken($bearer);

                // extract user info and attach SSO payload and membership (if any) to request
                try {
                    $user = $this->sso->getUserFromToken($bearer);
                    // always attach raw user payload so controllers can fallback when DB is down
                    $request->attributes->set('auth_user', $user);

                    $email = $user['email'] ?? null;
                    $membership = null;
                    if (! empty($email)) {
                        try {
                            $membership = Membership::where('email', $email)->first();
                        } catch (\Throwable $dbErr) {
                            // DB lookup failed; leave membership null but keep auth_user
                        }
                    }
                    $request->attributes->set('auth_membership', $membership);
                } catch (\Throwable $ignore) {
                    // ignore payload parsing errors — do not block authorization
                }
                return $next($request);
            } catch (\Exception $e) {
                $msg = config('app.debug') ? $e->getMessage() : 'Token tidak valid';
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak: ' . $msg,
                    'errors' => null,
                ], 401);
            }
        }

        // 2) Fallback to legacy X-IAE-KEY header
        $apiKey = $request->header('X-IAE-KEY');

        if (empty($apiKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak terotorisasi: kirim Authorization Bearer token atau header X-IAE-KEY',
                'errors' => null,
            ], 401);
        }

        // If the X-IAE-KEY header looks like a JWT (pattern header.payload.signature),
        // try verifying it via SSO. Use a regex to be more robust than counting dots.
        if (is_string($apiKey) && preg_match('/^[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+$/', $apiKey)) {
            try {
                $this->sso->verifyToken($apiKey);

                // attach SSO payload and membership info from token if possible
                try {
                    $user = $this->sso->getUserFromToken($apiKey);
                    $request->attributes->set('auth_user', $user);
                    $email = $user['email'] ?? null;
                    $membership = null;
                    if (! empty($email)) {
                        try {
                            $membership = Membership::where('email', $email)->first();
                        } catch (\Throwable $dbErr) {
                            // ignore DB lookup errors
                        }
                    }
                    $request->attributes->set('auth_membership', $membership);
                } catch (\Throwable $ignore) {
                    // ignore
                }

                return $next($request);
            } catch (\Exception $e) {
                $msg = config('app.debug') ? $e->getMessage() : 'Token di X-IAE-KEY tidak valid';
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak: ' . $msg,
                    'errors' => null,
                ], 401);
            }
        }

        // Otherwise treat X-IAE-KEY as the legacy static API key list.
        if (! in_array($apiKey, $this->getValidKeys(), true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: API Key tidak valid',
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
