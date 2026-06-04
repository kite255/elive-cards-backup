<?php

namespace Bryceandy\Beem\Events;

use App\Models\SendMessageCard;

class SmsDeliveryReportReceived extends CallbackReceived
{
    /**
     * Handle the event.
     *
     * @param  SmsDeliveryReportReceived $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(SmsDeliveryReportReceived $event)
    {
        $requestId = $event->request['request_id'];
        $recipientId = $event->request['recipient_id'];
        $mobileNumber = $event->request['dest_addr'];
        $status = $event->request['status'];
        
        $deliveryReport = SendMessageCard::where('request_id',$requestId);
        $deliveryReport->delivery_status = $status;
        $deliveryReport->save();

        // After processing this report, send back an OK response to Beem
        return response()->json([]);
    }
}