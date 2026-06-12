<?php

use App\Http\Controllers\Api\V1\MembershipController;
use App\Http\Controllers\SsoAuthController;
use App\Http\Middleware\ValidateSsoToken;
use App\Http\Middleware\VerifyJwtBearerSSO;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Service C (Keanggotaan & Voucher)
|--------------------------------------------------------------------------
|
| Semua endpoint diproteksi menggunakan middleware VerifyApiKey
| yang memvalidasi header X-IAE-KEY.
|
*/

Route::prefix('v1')->group(function () {

    // ── Keanggotaan (Membership) — protected by SSO JWT only ─────────
    Route::middleware(VerifyJwtBearerSSO::class)->group(function () {
        Route::get('/memberships', [MembershipController::class, 'index']);
        Route::get('/memberships/{id}', [MembershipController::class, 'show']);
        Route::post('/memberships', [MembershipController::class, 'store']);
    });
});

// Contoh endpoint SSO (public)
Route::post('/sso/login', [SsoAuthController::class, 'login']);
Route::get('/sso/me', [SsoAuthController::class, 'me'])->middleware(ValidateSsoToken::class);
