<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RabbitMQService
{
    private string $ssoBaseUrl = 'https://iae-sso.virtualfri.id';

    public function publish(string $routingKey, array $data, ?string $token = null): void
    {
        try {
            // Kita wajib menggunakan HTTP API karena host AMQP native (iae-mq) sedang tidak bisa diakses / down dari network Anda,
            // dan port 5672 di iae-sso menolak kredensial mahasiswa/rahasia.
            
            // Format disesuaikan agar API Gateway Dosen meneruskannya
            // ke RabbitMQ dengan atribut properties AMQP yang benar (app_id dan exchange)
            $payload = [
                'routing_key' => $routingKey,
                'exchange' => 'iae.central.exchange', // Target exchange
                'properties' => [
                    'app_id' => 'TEAM-06' // Agar muncul "Dari: TEAM-06" di Dashboard
                ],
                'message' => $data
            ];

            // Tembak API Dosen menggunakan Token M2M
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$this->ssoBaseUrl}/api/v1/messages/publish", $payload);

            if ($response->successful()) {
                Log::info("RabbitMQ API Publish Sukses: Event {$routingKey}");
            } else {
                Log::error("RabbitMQ API Gagal. Status: " . $response->status() . " Response: " . $response->body());
            }

        } catch (\Exception $e) {
            Log::error('Koneksi ke API Publisher gagal: ' . $e->getMessage());
        }
    }
}