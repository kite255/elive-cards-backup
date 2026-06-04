<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EliveSmsService
{
    public function send(string $phone, string $message, ?string $senderId = null): array
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'api-key' => config('services.elive_sms.api_key'),
            'secret-key' => config('services.elive_sms.secret_key'),
        ])->post(config('services.elive_sms.base_url') . '/message/send', [
            'phone' => $phone,
            'message' => $message,
            'sender_id' => $senderId ?? config('services.elive_sms.sender_id'),
        ]);

        return [
            'successful' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ];
    }

    public function balance(): array
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'api-key' => config('services.elive_sms.api_key'),
            'secret-key' => config('services.elive_sms.secret_key'),
        ])->get(config('services.elive_sms.base_url') . '/message/balance');

        return [
            'successful' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ];
    }
}