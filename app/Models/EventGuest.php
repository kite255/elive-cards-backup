<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class EventGuest extends Model
{
   // use SoftDeletes;
    protected $table = 'event_guests';
    protected $fillable = [
        'user_id',
        'event_id',
        'guest_name',
        'guest_phone',
        'card_type',
        'invitation_code',
        'note'
    ];

    //relationship between event and qrcode
    public function qrcode()
    {
        return $this->hasOne(GuestQrcode::class,'event_guests_id');
    }

    //relationship between event and pdf card
    public function pdfcard()
    {
        return $this->hasOne(GuestPdf::class,'event_guests_id');
    }

    //relationship between event and pdf card
    public function whatsappcard()
    {
        return $this->hasOne(SendWhatsappCard::class,'event_guests_id');
    }

    //relationship between event and message card
    public function messagecard()
    {
        return $this->hasOne(SendMessageCard::class,'event_guests_id');
    }

    //relationship between event and event guests
    public function event()
    {
        return $this->belongsTo(Event::class,'event_id');
    }

    //relationship between event guest and send whatsapp card
    public function sendwhatsappcard()
    {
        return $this->hasOne(SendWhatsappCard::class,'event_guests_id');
    }

    //relationship between event guest and send message card
    public function sendmessagecard()
    {
        return $this->hasOne(SendMessageCard::class,'event_guests_id');
    }

    //relationship between event guest and send message reminder
    public function sendmessagereminder()
    {
        return $this->hasOne(SendMessageReminder::class,'event_guests_id');
    }

    //relationship between event guest and send message thank you
    public function sendmessagethankyou()
    {
        return $this->hasOne(SendMessageThankyou::class,'event_guests_id');
    }
}
