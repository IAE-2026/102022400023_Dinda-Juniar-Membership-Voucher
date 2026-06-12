<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\RabbitMQService; // Pastikan ini ter-import
use App\Services\AuditSoapService;
use OpenApi\Attributes as OA;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[OA\Info(
    version: '1.0.0',
    title: 'Service C - API Keanggotaan & Voucher',
    description: 'Service Smart Parking untuk mengelola data keanggotaan dan voucher parkir.',
)]
class MembershipController extends Controller
{
    private const DISCOUNT_MAP = [
        'perunggu' => 10,
        'perak' => 15,
        'emas' => 20,
        'platina' => 50,
    ];

    #[OA\Get(
        path: '/api/v1/memberships',
        summary: 'Melihat daftar seluruh member',
        security: [['bearerAuth' => []]],
        tags: ['Keanggotaan'],
        responses: [
            new OA\Response(response: 200, description: 'Data berhasil diambil'),
            new OA\Response(response: 401, description: 'Tidak terotorisasi'),
            new OA\Response(response: 403, description: 'Akses ditolak'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        try {
            $memberships = Membership::all();
        } catch (\Throwable $e) {
            // If DB is unreachable, try to return membership info derived from token (if present)
            $authUser = $request->attributes->get('auth_user');
            if ($authUser && is_array($authUser)) {
                $fallback = $this->buildMembershipFromSso($authUser);
                return $this->successResponse('Database tidak tersedia, mengembalikan data dari token SSO', [$fallback], [
                    'total' => 1,
                    'auth_membership' => $fallback,
                ]);
            }

            return $this->successResponse('Database tidak tersedia, mengembalikan daftar kosong', collect([]), [
                'total' => 0,
            ]);
        }

        // attempt to publish event to RabbitMQ (non-blocking)
        try {
            $publisher = new RabbitMQService(); // Diubah ke RabbitMQService
            $publisher->publish('order_created', [
                'type' => 'memberships_list',
                'count' => $memberships->count(),
                'member_codes' => $memberships->pluck('member_code')->values()->all(),
            ]);
        } catch (\Throwable $e) {
            // silent: publishing failure should not affect API response
        }

        $authMembership = $request->attributes->get('auth_membership');
        $authData = null;
        if ($authMembership) {
            $authData = [
                'member_code' => $authMembership->member_code,
                'name' => $authMembership->name,
                'email' => $authMembership->email,
            ];
        }

        return $this->successResponse('Data berhasil diambil', $memberships, [
            'total' => $memberships->count(),
            'auth_membership' => $authData,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/memberships/{id}',
        summary: 'Mengecek detail dan status aktif seorang member',
        security: [['bearerAuth' => []]],
        tags: ['Keanggotaan'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Member code (contoh: MEM001)', schema: new OA\Schema(type: 'string', example: 'MEM001')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data berhasil diambil'),
            new OA\Response(response: 404, description: 'Anggota tidak ditemukan'),
            new OA\Response(response: 401, description: 'Tidak terotorisasi'),
        ]
    )]
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $membership = Membership::where('member_code', $id)->first();
        } catch (\Throwable $e) {
            // DB issue: if token contains matching user info, return that as fallback
            $authUser = $request->attributes->get('auth_user');
            if ($authUser && is_array($authUser)) {
                $fallback = $this->buildMembershipFromSso($authUser);
                // Only return if fallback member_code matches requested id (or if id is absent)
                if (! empty($fallback['member_code']) && $fallback['member_code'] === $id) {
                    return $this->successResponse('Database tidak tersedia, mengembalikan data dari token SSO', $fallback, []);
                }
            }

            return $this->errorResponse('Gagal mengambil data keanggotaan: masalah koneksi database', 500);
        }

        if (!$membership) {
            return $this->errorResponse("Anggota '{$id}' tidak ditemukan", 404);
        }

        // publish event about membership retrieval (non-blocking)
        try {
            $publisher = new RabbitMQService(); // Diubah ke RabbitMQService
            $publisher->publish('order_created', [
                'type' => 'membership_view',
                'member_code' => $membership->member_code,
                'name' => $membership->name,
            ]);
        } catch (\Throwable $e) {
            // ignore
        }

        $authMembership = $request->attributes->get('auth_membership');
        $authData = null;
        if ($authMembership) {
            $authData = [
                'member_code' => $authMembership->member_code,
                'name' => $authMembership->name,
                'email' => $authMembership->email,
            ];
        }

        return $this->successResponse('Data berhasil diambil', $membership, [
            'auth_membership' => $authData,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/memberships',
        summary: 'Mendaftarkan member baru',
        security: [['bearerAuth' => []]],
        tags: ['Keanggotaan'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'phone', 'membership_type'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Rani Putri'),
                    new OA\Property(property: 'email', type: 'string', example: 'rani@mail.com'),
                    new OA\Property(property: 'phone', type: 'string', example: '081234567899'),
                    new OA\Property(property: 'membership_type', type: 'string', enum: ['perunggu', 'perak', 'emas', 'platina'], example: 'perak'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Anggota berhasil didaftarkan'),
            new OA\Response(response: 400, description: 'Validasi gagal'),
            new OA\Response(response: 401, description: 'Tidak terotorisasi'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $name = $request->input('name');
        $email = $request->input('email');
        $phone = $request->input('phone');
        $membershipType = $request->input('membership_type');

        if (!$name || !$email || !$phone || !$membershipType) {
            return $this->errorResponse('name, email, phone, dan membership_type wajib diisi', 400);
        }

        $validTypes = array_keys(self::DISCOUNT_MAP);
        if (!in_array($membershipType, $validTypes, true)) {
            return $this->errorResponse(
                'membership_type harus salah satu dari: ' . implode(', ', $validTypes),
                400
            );
        }

        $token = $request->bearerToken() ?? $request->header('X-IAE-KEY');
        if (empty($token)) {
            return $this->errorResponse('Tidak terotorisasi: Token JWT tidak ditemukan', 401);
        }

        try {
            $membership = DB::transaction(function () use ($name, $email, $phone, $membershipType, $token) {
                $lastMembership = Membership::orderByDesc('id')->first();
                $nextNumber = $lastMembership ? ((int) substr($lastMembership->member_code, 3)) + 1 : 1;
                $memberCode = 'MEM' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                $now = now();
                $expiredAt = $now->copy()->addYear();

                $membership = Membership::create([
                    'member_code' => $memberCode,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'membership_type' => $membershipType,
                    'status' => 'aktif',
                    'discount_percent' => self::DISCOUNT_MAP[$membershipType],
                    'registered_at' => $now,
                    'expired_at' => $expiredAt,
                ]);

                $auditService = new AuditSoapService();
                $receiptNumber = $auditService->sendAuditLog($token, $membership->toArray());

                if (empty($receiptNumber)) {
                    throw new \Exception('Gagal melakukan audit SOAP: Tidak dapat memperoleh nomor resi.');
                }

                $membership->update([
                    'receipt_number' => $receiptNumber
                ]);

                return $membership;
            });
        } catch (\Throwable $e) {
            return $this->errorResponse('Gagal mendaftarkan anggota: ' . $e->getMessage(), 500);
        }

        // ==========================================================
        // Modul 3: Memanggil AMQP Publisher (RabbitMQ)
        // Diletakkan SETELAH transaksi DB berhasil agar data valid
        // ==========================================================
        try {
            $rabbitMQService = new RabbitMQService();
            // Format payload RabbitMQ sesuai permintaan
            $payload = [
                'event_name' => 'membership.created',
                'service_name' => 'Keanggotaan-Voucher-Service',
                'occurred_at' => now()->toIso8601String(),
                'team_id' => 'TEAM-06',
                'member' => $membership->toArray()
            ];

            // PENTING: Sisipkan $token sebagai parameter ketiga!
            $rabbitMQService->publish('membership.created', $payload, $token);
        } catch (\Throwable $e) {
            // Kita bungkus try-catch agar jika RabbitMQ sedang mati, 
            // pendaftaran tetap sukses dan API tidak crash
            Log::error('RabbitMQ Error: ' . $e->getMessage());
        }

        return $this->successResponse('Anggota berhasil didaftarkan', $membership, [], 201);
    }

    private function successResponse(string $message, mixed $data, array $extraMeta = [], int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'meta' => array_merge([
                'service_name' => 'Keanggotaan-Voucher-Service',
                'api_version' => 'v1',
            ], $extraMeta),
        ], $statusCode);
    }

    private function errorResponse(string $message, int $statusCode, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }

    private function buildMembershipFromSso(array $user): array
    {
        // $user expected keys from SsoService::getUserFromToken: name, email, sub, role, raw
        $raw = $user['raw'] ?? [];

        // try to derive member_code from known fields: nim, sub
        $memberCode = null;
        if (! empty($raw['profile']['nim'])) {
            $memberCode = 'MEM' . str_pad(substr($raw['profile']['nim'], -3), 3, '0', STR_PAD_LEFT);
        } elseif (! empty($user['sub'])) {
            $memberCode = (string) $user['sub'];
        }

        return [
            'member_code' => $memberCode,
            'name' => $user['name'] ?? ($raw['profile']['name'] ?? null),
            'email' => $user['email'] ?? ($raw['profile']['email'] ?? null),
            'phone' => $raw['profile']['phone'] ?? null,
            'membership_type' => $raw['profile']['membership_type'] ?? null,
            'status' => 'unknown',
            'discount_percent' => null,
            'registered_at' => null,
            'expired_at' => null,
        ];
    }
}