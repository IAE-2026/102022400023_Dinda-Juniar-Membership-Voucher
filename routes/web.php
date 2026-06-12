<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Service C (Keanggotaan & Voucher)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/test-member', function () {
    return view('test-member');
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Service C (Keanggotaan & Voucher) sedang berjalan',
        'data' => [
            'service' => 'Keanggotaan-Voucher-Service',
            'version' => 'v1',
            'uptime' => round(microtime(true) - LARAVEL_START, 2) . 's',
        ],
        'meta' => [
            'service_name' => 'Keanggotaan-Voucher-Service',
            'api_version' => 'v1',
        ],
    ]);
});
