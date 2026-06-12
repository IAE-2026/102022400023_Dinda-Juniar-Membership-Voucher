<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SsoService;
use App\Models\Membership;

class VerifyJwtBearerSSO
{
    protected SsoService $sso;

    public function __construct(SsoService $sso)
    {
        $this->sso = $sso;
    }

    /**
     * Verify JWT Bearer token from Authorization header.
     *
     * Reads the JWT from the Authorization Bearer token, verifies it via the SSO service (JWKS),
     * and attaches the authenticated user info and membership (if any) to the request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (empty($token)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak terotorisasi: Bearer token diperlukan',
                'errors' => null,
            ], 401);
        }

        try {
            $this->sso->verifyToken($token);
        } catch (\Exception $e) {
            $msg = config('app.debug') ? $e->getMessage() : 'Token tidak valid';
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: ' . $msg,
                'errors' => null,
            ], 401);
        }

        // Attach user info and membership to request
        try {
            $user = $this->sso->getUserFromToken($token);
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
    }
}
