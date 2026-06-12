<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuditSoapService
{
    // TODO: Ganti KEY-MHS-01 ini dengan API KEY asli milik kelompokmu (misal: KEY-MHS-06)
    private string $apiKey = 'KEY-MHS-45'; 
    private string $ssoBaseUrl = 'https://iae-sso.virtualfri.id';

    /**
     * Meminta M2M (Machine-to-Machine) Token ke server pusat
     */
    private function getM2MToken(): ?string
    {
        try {
            $response = Http::post("{$this->ssoBaseUrl}/api/v1/auth/token", [
                'api_key' => $this->apiKey
            ]);

            if ($response->successful() && $response->json('access_token')) {
                return $response->json('access_token');
            } elseif ($response->successful() && $response->json('token')) {
                return $response->json('token');
            }

            Log::error('Gagal mendapat M2M Token: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Koneksi ke endpoint M2M gagal: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Mengirimkan Log SOAP menggunakan M2M Token
     */
    public function sendAuditLog(string $userToken_diabaikan, array $memberData): ?string
    {
        // 1. Ambil M2M Token terlebih dahulu
        $m2mToken = $this->getM2MToken();
        
        if (!$m2mToken) {
            return null; // Batalkan jika gagal dapat M2M token
        }

        // 2. Siapkan data XML
        $jsonData = json_encode($memberData);
        $xmlBody = '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:iae="http://iae.central/audit">
<soap:Body>
<iae:AuditRequest>
<iae:TeamID>TEAM-06</iae:TeamID>
<iae:ActivityName>MembershipCreated</iae:ActivityName>
<iae:LogContent><![CDATA[' . $jsonData . ']]></iae:LogContent>
</iae:AuditRequest>
</soap:Body>
</soap:Envelope>';

        try {
            // 3. Tembak SOAP MENGGUNAKAN M2M TOKEN, bukan User Token
            $response = Http::withHeaders([
                'Content-Type' => 'text/xml; charset=UTF8',
                'Authorization' => 'Bearer ' . $m2mToken
            ])->send('POST', "{$this->ssoBaseUrl}/soap/v1/audit", [
                'body' => $xmlBody
            ]);

            $responseBody = $response->body();

            // 4. Parsing Receipt Number
            if ($response->successful()) {
                if (preg_match('/<iae:\s*Status\s*>\s*SUCCESS\s*<\/iae:\s*Status\s*>/is', $responseBody)) {
                    if (preg_match('/<iae:\s*ReceiptNumber\s*>\s*(.*?)\s*<\/iae:\s*ReceiptNumber\s*>/is', $responseBody, $matches) || 
                        preg_match('/<iae:\s*Receipt\s*Number\s*>\s*(.*?)\s*<\/iae:\s*Receipt\s*Number\s*>/is', $responseBody, $matches)) {
                        return trim($matches[1]);
                    }
                }
                Log::error('SOAP Ditolak Dosen: ' . $responseBody);
            } else {
                Log::error('SOAP HTTP Error: ' . $responseBody);
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Koneksi HTTP ke SOAP gagal: ' . $e->getMessage());
            return null;
        }
    }
}