<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SoapHeaderAuth
{
    public function handle(Request $request, Closure $next)
    {
        $raw = $request->getContent();

        if ($raw) {
            // try common patterns: <Authorization>Bearer ...</Authorization>
            if (preg_match('/<Authorization>(.*?)<\/Authorization>/is', $raw, $m)) {
                $val = trim($m[1]);
                // remove newlines/extra whitespace inside token
                $val = preg_replace('/\s+/', '', $val);
                // extract JWT-like sequence (header.payload.sig)
                if (preg_match('/(Bearer)?\s*([A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+)/', $val, $t)) {
                    $token = $t[2];
                    $request->headers->set('Authorization', 'Bearer ' . $token);
                } else {
                    // if it doesn't look like a JWT, set as-is after trimming
                    if (!empty($val)) {
                        $request->headers->set('Authorization', $val);
                    }
                }
            } else {
                // fallback: search for Authorization attribute or header-like element
                if (preg_match('/Authorization\s*=\s*"([^"]+)"/is', $raw, $m2)) {
                    $val = trim($m2[1]);
                    $val = preg_replace('/\s+/', '', $val);
                    if (preg_match('/(Bearer)?\s*([A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+)/', $val, $t)) {
                        $token = $t[2];
                        $request->headers->set('Authorization', 'Bearer ' . $token);
                    } else {
                        $request->headers->set('Authorization', $val);
                    }
                }
            }
        }

        return $next($request);
    }
}
