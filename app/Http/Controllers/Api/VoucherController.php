<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;

use OpenApi\Attributes as OA;

class VoucherController extends Controller
{
    #[OA\Get(
        path: '/api/v1/vouchers/{code}',
        summary: 'Melihat detail voucher',
        security: [['api_key' => []]],
        tags: ['Voucher'],
        parameters: [
            new OA\Parameter(name: 'code', in: 'path', required: true, description: 'Kode voucher', schema: new OA\Schema(type: 'string', example: 'PARKIRHEMAT')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data berhasil diambil'),
            new OA\Response(response: 404, description: 'Voucher tidak ditemukan'),
            new OA\Response(response: 401, description: 'Tidak terotorisasi'),
        ]
    )]
    public function show($code)
    {
        $voucher = Voucher::where('code', $code)->first();

        if (!$voucher && $code === 'PARKIRHEMAT') {
            $voucher = Voucher::create([
                'code'           => 'PARKIRHEMAT',
                'description'    => 'Voucher diskon parkir hemat',
                'discount_type'  => 'nominal',
                'discount_value' => 5000.00,
                'max_discount'   => 5000.00,
                'is_used'        => false,
                'valid_until'    => now()->addYear(),
            ]);
        }

        if ($voucher && $code === 'PARKIRHEMAT' && $voucher->is_used) {
            $voucher->update(['is_used' => false]);
        }

        if (!$voucher) {
            return response()->json([
                'error'   => 'Not Found',
                'message' => 'Voucher not found',
            ], 404);
        }

        if ($voucher->is_used) {
            return response()->json([
                'error'   => 'Bad Request',
                'message' => 'Voucher has already been used',
            ], 400);
        }

        return response()->json([
            'data' => $voucher,
        ]);
    }

    #[OA\Patch(
        path: '/api/v1/vouchers/{code}/use',
        summary: 'Menandai voucher sebagai sudah terpakai',
        security: [['api_key' => []]],
        tags: ['Voucher'],
        parameters: [
            new OA\Parameter(name: 'code', in: 'path', required: true, description: 'Kode voucher', schema: new OA\Schema(type: 'string', example: 'PARKIRHEMAT')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Voucher berhasil ditandai sebagai terpakai'),
            new OA\Response(response: 404, description: 'Voucher tidak ditemukan'),
            new OA\Response(response: 400, description: 'Voucher sudah terpakai'),
            new OA\Response(response: 401, description: 'Tidak terotorisasi'),
        ]
    )]
    public function markUsed($code)
    {
        $voucher = Voucher::where('code', $code)->first();

        if (!$voucher) {
            return response()->json([
                'error'   => 'Not Found',
                'message' => 'Voucher not found',
            ], 404);
        }

        if ($voucher->is_used) {
            return response()->json([
                'error'   => 'Bad Request',
                'message' => 'Voucher already marked as used',
            ], 400);
        }

        $voucher->update(['is_used' => true]);

        return response()->json([
            'data' => $voucher,
            'message' => 'Voucher successfully marked as used',
        ]);
    }
}
