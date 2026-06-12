<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SsoService;
use Illuminate\Http\JsonResponse;
use App\Models\Membership;

class SsoAuthController extends Controller
{
    protected SsoService $sso;

    public function __construct(SsoService $sso)
    {
        $this->sso = $sso;
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $result = $this->sso->login($request->input('email'), $request->input('password'));
            $user = $this->sso->getUserFromToken($result['token']);

            return response()->json([
                'token' => $result['token'],
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // optional endpoint to return current SSO user if middleware attached
    public function me(Request $request): JsonResponse
    {
        $ssoUser = $request->attributes->get('sso_user');

        $membership = null;
        $email = $ssoUser['email'] ?? $ssoUser['sub'] ?? null;
        if (! empty($email)) {
            $membership = Membership::where('email', $email)->first();
        }

        return response()->json([
            'sso_user' => $ssoUser ?? null,
            'membership' => $membership,
        ]);
    }
}
