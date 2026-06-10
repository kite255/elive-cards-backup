<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventGuest extends Model
{
    protected $table = 'event_guests';

    protected $fillable = [
        'user_id',
        'event_id',
        'guest_name',
        'guest_phone',
        'card_type',
        'invitation_code',
        'note',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function qrcode()
    {
        return $this->hasOne(GuestQrcode::class, 'event_guests_id');
    }

    public function pdfcard()
    {
        return $this->hasOne(GuestPdf::class, 'event_guests_id');
    }

    public function whatsappcard()
    {
        return $this->hasOne(SendWhatsappCard::class, 'event_guests_id');
    }

    public function sendwhatsappcard()
    {
        return $this->hasOne(SendWhatsappCard::class, 'event_guests_id');
    }

    public function messagecard()
    {
        return $this->hasOne(SendMessageCard::class, 'event_guests_id');
    }

    public function sendmessagecard()
    {
        return $this->hasOne(SendMessageCard::class, 'event_guests_id');
    }

    public function sendmessagereminder()
    {
        return $this->hasOne(SendMessageReminder::class, 'event_guests_id');
    }

    public function sendmessagethankyou()
    {
        return $this->hasOne(SendMessageThankyou::class, 'event_guests_id');
    }
}