<?php

use App\Http\Controllers\Api\V1\MembershipController;
use App\Http\Controllers\Api\VoucherController;
use App\Http\Controllers\AuditSoapController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\SsoAuthController;
use App\Http\Middleware\ValidateSsoToken;
use App\Http\Middleware\VerifyJwtBearerSSO;
use Illuminate\Http\Request;
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

    Route::middleware(\App\Http\Middleware\IaeKeyAuth::class)->group(function () {
        // ── Keanggotaan (Membership)
        Route::get('/memberships', [MembershipController::class, 'index']);
        Route::get('/memberships/{id}', [MembershipController::class, 'show']);
        Route::post('/memberships', [MembershipController::class, 'store']);

        // ── Voucher
        Route::get('/vouchers/{code}', [VoucherController::class, 'show']);
        Route::patch('/vouchers/{code}/use', [VoucherController::class, 'markUsed']);
    });

    // ── Demo: endpoint protected by JWKS/RS256 JWT ───────────────────
    Route::get('/me', function (Request $request) {
        $payload = $request->attributes->get('jwt_payload');
        return response()->json(['ok' => true, 'payload' => $payload]);
    })->middleware('auth.jwt');

    // ── Public board messages endpoint ────────────────────────────────
    Route::get('/messages/board', [BoardController::class, 'messages']);
});

// ── SOAP Audit endpoint ───────────────────────────────────────────────────
// Accepts SOAP body, extracts Authorization from SOAP Header if present
Route::post('/soap/audit', [AuditSoapController::class, 'handle'])
    ->middleware(['soap.header.auth', 'auth.jwt']);

// Debug route: accept SOAP without JWT verification (local testing only)
Route::post('/soap/audit/noauth', [AuditSoapController::class, 'handle'])
    ->middleware('soap.header.auth');

// Contoh endpoint SSO (public)
Route::post('/sso/login', [SsoAuthController::class, 'login']);
Route::get('/sso/me', [SsoAuthController::class, 'me'])->middleware(ValidateSsoToken::class);
