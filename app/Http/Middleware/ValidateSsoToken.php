<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\SsoService;

class ValidateSsoToken
{
    protected SsoService $sso;

    public function __construct(SsoService $sso)
    {
        $this->sso = $sso;
    }

    public function handle(Request $request, Closure $next)
    {
        $auth = $request->bearerToken();

        if (! $auth) {
            return response()->json(['message' => 'Token tidak ditemukan.'], 401);
        }

        try {
            $user = $this->sso->getUserFromToken($auth);
            $request->attributes->set('sso_user', $user);
            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Token tidak valid: ' . $e->getMessage()], 401);
        }
    }
}
