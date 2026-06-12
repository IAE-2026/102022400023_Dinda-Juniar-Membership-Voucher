<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\AuditSoapService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuditSoapServiceTest extends TestCase
{
    public function test_send_audit_log_success()
    {
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

        $service = new AuditSoapService();
        $receipt = $service->sendAuditLog('dummy-token', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $this->assertEquals('IAE-LOG-2026-8891A7BC', $receipt);

        Http::assertSent(function ($request) {
            $this->assertEquals('https://iae-sso.virtualfri.id/soap/v1/audit', $request->url());
            $this->assertEquals('POST', $request->method());
            $this->assertEquals('text/xml; charset=UTF8', $request->header('Content-Type')[0]);
            $this->assertEquals('Bearer dummy-token', $request->header('Authorization')[0]);
            
            $body = $request->body();
            $this->assertStringContainsString('<iae:TeamID>TEAM-06</iae:TeamID>', $body);
            $this->assertStringContainsString('<iae:ActivityName>MembershipCreated</iae:ActivityName>', $body);
            $this->assertStringContainsString('john@example.com', $body);
            return true;
        });
    }

    public function test_send_audit_log_failure()
    {
        Http::fake([
            'https://iae-sso.virtualfri.id/soap/v1/audit' => Http::response('Error', 500)
        ]);

        Log::shouldReceive('error')
            ->once();

        $service = new AuditSoapService();
        $receipt = $service->sendAuditLog('dummy-token', []);

        $this->assertNull($receipt);
    }
}
