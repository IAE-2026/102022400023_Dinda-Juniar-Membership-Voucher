<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

/**
 * Base controller.
 */
#[OA\Schema(
    schema: 'Membership',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'member_code', type: 'string', example: 'MEM001'),
        new OA\Property(property: 'name', type: 'string', example: 'Budi Santoso'),
        new OA\Property(property: 'email', type: 'string', example: 'budi@mail.com'),
        new OA\Property(property: 'phone', type: 'string', example: '081234567890'),
        new OA\Property(property: 'membership_type', type: 'string', enum: ['perunggu', 'perak', 'emas', 'platina'], example: 'emas'),
        new OA\Property(property: 'status', type: 'string', enum: ['aktif', 'kedaluwarsa'], example: 'aktif'),
        new OA\Property(property: 'discount_percent', type: 'integer', example: 20),
        new OA\Property(property: 'registered_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'expired_at', type: 'string', format: 'date-time'),
    ]
)]
abstract class Controller
{
    //
}
