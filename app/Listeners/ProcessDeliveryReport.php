<?php

namespace App\Listeners;

use App\Models\SendMessageCard;
use Bryceandy\Beem\Events\SmsDeliveryReportReceived;
use Illuminate\Support\Facades\Log;

class ProcessDeliveryReport
{
    public function handle(SmsDeliveryReportReceived $event)
    {
        $payload = $event->request ?? [];

        $requestId = $payload['request_id'] ?? null;
        $recipientId = $payload['recipient_id'] ?? null;
        $mobileNumber = $payload['dest_addr'] ?? null;
        $status = $payload['status'] ?? null;

        Log::info('Beem SMS delivery report received.', [
            'request_id' => $requestId,
            'recipient_id' => $recipientId,
            'mobile_number' => $mobileNumber,
            'status' => $status,
        ]);

        if (! $requestId) {
            return response()->json(['success' => false, 'message' => 'Missing request_id'], 200);
        }

        $deliveryReport = SendMessageCard::where('request_id', $requestId)->first();

        if (! $deliveryReport) {
            Log::warning('No SendMessageCard record found for Beem request_id.', [
                'request_id' => $requestId,
                'recipient_id' => $recipientId,
                'mobile_number' => $mobileNumber,
                'status' => $status,
            ]);

            return response()->json(['success' => false, 'message' => 'Record not found'], 200);
        }

        $deliveryReport->delivery_status = $status;
        $deliveryReport->save();

        return response()->json(['success' => true]);
    }
}