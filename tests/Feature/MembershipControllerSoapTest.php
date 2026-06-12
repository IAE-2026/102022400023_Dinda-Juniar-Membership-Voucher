<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Membership;
use App\Services\SsoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class MembershipControllerSoapTest extends TestCase
{
    use RefreshDatabase;

    public function test_membership_creation_updates_receipt_number_via_soap()
    {
        // Mock SSO Verification
        $ssoMock = $this->createMock(SsoService::class);
        $ssoMock->method('verifyToken')->willReturn(new \stdClass());
        $ssoMock->method('getUserFromToken')->willReturn([
            'name' => 'Rizka Amelia',
            'email' => 'warga18@ktp.iae.id',
            'sub' => 'warga18@ktp.iae.id',
            'role' => 'user',
            'raw' => [
                'profile' => [
                    'name' => 'Rizka Amelia',
                    'email' => 'warga18@ktp.iae.id',
                    'nim' => '2026000018'
                ]
            ]
        ]);
        $this->app->instance(SsoService::class, $ssoMock);

        // Mock SOAP API audit endpoint response
        $mockXmlResponse = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:iae="http://iae.central/audit">
            <soap:Body>
                <iae:AuditResponse>
                    <iae:Status>SUCCESS</iae:Status>
                    <iae:ReceiptNumber>IAE-LOG-2026-8891A7BC</iae:ReceiptNumber>
                </iae:AuditResponse>
            </soap:Body>
        </soap:Envelope>';

        Http::fake([
            'https://iae-sso.virtualfri.id/soap/v1/audit' => Http::response($mockXmlResponse, 200, [
                'Content-Type' => 'text/xml'
            ])
        ]);

        // POST request to create membership
        $response = $this->withToken('mock-jwt-token')->postJson('/api/v1/memberships', [
            'name' => 'Rani Putri',
            'email' => 'rani@mail.com',
            'phone' => '081234567899',
            'membership_type' => 'perak',
        ]);

        // Assert response status is 201 (Created)
        $response->assertStatus(201);

        // Assert response contains success status and receipt_number
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.receipt_number', 'IAE-LOG-2026-8891A7BC');

        // Assert database record exists and has the receipt number
        $this->assertDatabaseHas('memberships', [
            'email' => 'rani@mail.com',
            'receipt_number' => 'IAE-LOG-2026-8891A7BC',
            'membership_type' => 'perak',
        ]);
    }
}
