<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SendMessageCard extends Model
{
    protected $fillable = [
        'event_id',
        'event_guests_id',
        'request_id',
        'sent_status',
        'delivery_status',
        'card_message'
    ];

    //relationship btn event-guest and sendMessageCard
    public function eventguest()
    {
        return $this->belongsTo(EventGuest::class,'event_guests_id');
    }
    
    //relationship btn guest-pdf and sendMessageCard
    public function guestpdfs()
    {
        return $this->belongsTo(GuestPdf::class,'guest_pdf_id');
    }
}
