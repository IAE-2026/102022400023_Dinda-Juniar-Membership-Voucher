<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IaeKeyAuth
{
    /**
     * Handle an incoming request.
     *
     * Middleware / Guard (X-IAE-KEY):
     * Jika request masuk TANPA header X-IAE-KEY, kode harus menolak request
     * dan mengembalikan status 401 Unauthorized.
     * Jika request masuk DENGAN header X-IAE-KEY (berisi NIM), kode harus mengizinkan request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$request->hasHeader('X-IAE-KEY')) {
            return response()->json([
                'error'   => 'Unauthorized',
                'message' => 'Missing X-IAE-KEY header',
            ], 401);
        }

        // Jika request masuk dengan header X-IAE-KEY (berisi NIM), izinkan request
        $nim = $request->header('X-IAE-KEY');
        $request->attributes->set('nim', $nim);

        return $next($request);
    }
}
