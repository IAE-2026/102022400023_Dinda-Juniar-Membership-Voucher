<?php

namespace Database\Seeders;

use App\Models\Membership;
use Illuminate\Database\Seeder;

class MembershipSeeder extends Seeder
{
    public function run(): void
    {
        $memberships = [
            [
                'member_code' => 'MEM001',
                'name' => 'Budi Santoso',
                'email' => 'budi@mail.com',
                'phone' => '081234567890',
                'membership_type' => 'emas',
                'status' => 'aktif',
                'discount_percent' => 20,
                'registered_at' => '2026-01-10 08:00:00',
                'expired_at' => '2027-01-10 08:00:00',
            ],
            [
                'member_code' => 'MEM002',
                'name' => 'Siti Rahma',
                'email' => 'siti@mail.com',
                'phone' => '081234567891',
                'membership_type' => 'perak',
                'status' => 'aktif',
                'discount_percent' => 15,
                'registered_at' => '2026-02-15 10:00:00',
                'expired_at' => '2027-02-15 10:00:00',
            ],
            [
                'member_code' => 'MEM003',
                'name' => 'Ahmad Hidayat',
                'email' => 'ahmad@mail.com',
                'phone' => '081234567892',
                'membership_type' => 'perunggu',
                'status' => 'kedaluwarsa',
                'discount_percent' => 0,
                'registered_at' => '2025-03-01 09:00:00',
                'expired_at' => '2026-03-01 09:00:00',
            ],
            [
                'member_code' => 'MEM004',
                'name' => 'Dewi Lestari',
                'email' => 'dewi@mail.com',
                'phone' => '081234567893',
                'membership_type' => 'emas',
                'status' => 'aktif',
                'discount_percent' => 20,
                'registered_at' => '2026-04-20 07:00:00',
                'expired_at' => '2027-04-20 07:00:00',
            ],
            [
                'member_code' => 'MEM005',
                'name' => 'Rizki Pratama',
                'email' => 'rizki@mail.com',
                'phone' => '081234567894',
                'membership_type' => 'platina',
                'status' => 'aktif',
                'discount_percent' => 50,
                'registered_at' => '2026-05-01 06:00:00',
                'expired_at' => '2027-05-01 06:00:00',
            ],
        ];

        foreach ($memberships as $membership) {
            Membership::firstOrCreate(
                ['member_code' => $membership['member_code']],
                $membership
            );
        }
    }
}
