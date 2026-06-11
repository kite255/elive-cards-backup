<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EliveSmsService
{
    private function baseUrl(): string
    {
        return rtrim((string) config('services.elive_sms.base_url'), '/');
    }

    private function apiKey(): ?string
    {
        return config('services.elive_sms.api_key')
            ?: config('services.elive_sms.api-key')
            ?: env('ELIVE_SMS_API_KEY')
            ?: env('ELIVE_SMS_APIKEY');
    }

    private function apiSecret(): ?string
    {
        return config('services.elive_sms.api_secret')
            ?: config('services.elive_sms.secret_key')
            ?: config('services.elive_sms.api-secret')
            ?: env('ELIVE_SMS_API_SECRET')
            ?: env('ELIVE_SMS_SECRET_KEY')
            ?: env('ELIVE_SMS_SECRET');
    }

    private function senderId(): ?string
    {
        return config('services.elive_sms.sender_id')
            ?: env('ELIVE_SMS_SENDER_ID');
    }

    private function headers(): array
    {
        $apiKey = $this->apiKey();
        $apiSecret = $this->apiSecret();

        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',

            /*
            |--------------------------------------------------------------------------
            | Provider documented headers
            |--------------------------------------------------------------------------
            */
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,

            /*
            |--------------------------------------------------------------------------
            | Keep old working headers too
            |--------------------------------------------------------------------------
            */
            'api-key' => $apiKey,
            'secret-key' => $apiSecret,
        ];
    }

    public function send(string $phone, string $message, ?string $senderId = null): array
    {
        $this->ensureCredentialsExist();

        $payload = [
            'phone' => $phone,
            'message' => $message,
            'sender_id' => $senderId ?? $this->senderId(),
        ];

        $response = Http::timeout(30)
            ->acceptJson()
            ->withHeaders($this->headers())
            ->post($this->baseUrl() . '/message/send', $payload);

        $body = $this->responseBody($response);
        $shootId = $this->extractShootId($body);

        Log::info('Elive SMS send response.', [
            'phone' => $phone,
            'http_status' => $response->status(),
            'successful' => $response->successful(),
            'shoot_id' => $shootId,
            'body' => $body,
        ]);

        return [
            'successful' => $response->successful(),
            'status' => $response->status(),
            'body' => $body,
            'shoot_id' => $shootId,
        ];
    }

    public function balance(): array
    {
        $this->ensureCredentialsExist();

        $response = Http::timeout(30)
            ->acceptJson()
            ->withHeaders($this->headers())
            ->get($this->baseUrl() . '/message/balance');

        $body = $this->responseBody($response);

        Log::info('Elive SMS balance response.', [
            'http_status' => $response->status(),
            'successful' => $response->successful(),
            'body' => $body,
        ]);

        return [
            'successful' => $response->successful(),
            'status' => $response->status(),
            'body' => $body,
        ];
    }

    public function deliveryReport(string|int|null $shootId): array
    {
        $this->ensureCredentialsExist();

        $shootId = trim((string) $shootId);

        if ($shootId === '') {
            return [
                'successful' => false,
                'status' => 422,
                'body' => [
                    'message' => 'Missing shootId.',
                ],
                'reports' => [],
            ];
        }

        $response = Http::timeout(30)
            ->acceptJson()
            ->withHeaders($this->headers())
            ->get($this->baseUrl() . '/message/deliver/' . urlencode($shootId));

        $body = $this->responseBody($response);
        $reports = $this->extractDeliveryReports($body);

        Log::info('Elive SMS delivery report response.', [
            'shoot_id' => $shootId,
            'http_status' => $response->status(),
            'successful' => $response->successful(),
            'reports' => $reports,
            'body' => $body,
        ]);

        return [
            'successful' => $response->successful(),
            'status' => $response->status(),
            'body' => $body,
            'reports' => $reports,
        ];
    }

    public function firstDeliveryStatus(string|int|null $shootId): array
    {
        $report = $this->deliveryReport($shootId);

        $firstReport = $report['reports'][0] ?? null;

        return [
            'successful' => $report['successful'],
            'status' => $report['status'],
            'shoot_id' => $shootId,
            'delivery_status' => $firstReport['status'] ?? null,
            'delivery_status_code' => $firstReport['status_code'] ?? null,
            'mobile' => $firstReport['mobile'] ?? null,
            'sent_at' => $firstReport['sent_at'] ?? null,
            'raw' => $report['body'],
        ];
    }

    private function ensureCredentialsExist(): void
    {
        if (! $this->apiKey() || ! $this->apiSecret()) {
            Log::error('Elive SMS credentials missing.', [
                'has_api_key' => (bool) $this->apiKey(),
                'has_api_secret' => (bool) $this->apiSecret(),
                'base_url' => $this->baseUrl(),
            ]);

            throw new \RuntimeException('Elive SMS API credentials are missing. Check config/services.php and .env.');
        }
    }

    private function responseBody($response): mixed
    {
        try {
            return $response->json() ?? $response->body();
        } catch (\Throwable $e) {
            return $response->body();
        }
    }

    private function extractShootId(mixed $body): ?string
    {
        if (! is_array($body)) {
            return null;
        }

        $possibleKeys = [
            'shootId',
            'shoot_id',
            'shootid',
            'shootID',
            'messageId',
            'message_id',
            'requestId',
            'request_id',
            'id',
        ];

        foreach ($possibleKeys as $key) {
            if (! empty($body[$key])) {
                return (string) $body[$key];
            }
        }

        if (! empty($body['data']) && is_array($body['data'])) {
            foreach ($possibleKeys as $key) {
                if (! empty($body['data'][$key])) {
                    return (string) $body['data'][$key];
                }
            }

            if (isset($body['data'][0]) && is_array($body['data'][0])) {
                foreach ($possibleKeys as $key) {
                    if (! empty($body['data'][0][$key])) {
                        return (string) $body['data'][0][$key];
                    }
                }
            }
        }

        return null;
    }

    private function extractDeliveryReports(mixed $body): array
    {
        if (! is_array($body)) {
            return [];
        }

        $rows = [];

        if (! empty($body['data']) && is_array($body['data'])) {
            $rows = $body['data'];
        } elseif (isset($body[0]) && is_array($body[0])) {
            $rows = $body;
        }

        $reports = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $reports[] = [
                'message' => $row['message'] ?? null,
                'mobile' => $row['mobile'] ?? $row['phone'] ?? null,
                'sender_id' => $row['senderId'] ?? $row['sender_id'] ?? null,
                'status' => $row['status'] ?? null,
                'status_code' => $row['statusCode'] ?? $row['status_code'] ?? null,
                'explanation' => $row['explanation'] ?? null,
                'message_type' => $row['messageType'] ?? $row['message_type'] ?? null,
                'sent_at' => $row['sentAt'] ?? $row['sent_at'] ?? null,
                'raw' => $row,
            ];
        }

        return $reports;
    }
}