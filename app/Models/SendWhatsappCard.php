<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SendWhatsappCard extends Model
{
    /**
     * Allow mass assignment when creating WhatsApp/SMS card sending records.
     *
     * This fixes:
     * Add [event_id] to fillable property...
     */
    protected $guarded = [];

    /**
     * Relationship between sending record and guest PDF/card.
     */
    public function guestPdf()
    {
        return $this->belongsTo(GuestPdf::class, 'guest_pdf_id');
    }

    /**
     * Relationship between sending record and event.
     */
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    /**
     * Relationship between sending record and guest.
     */
    public function eventGuest()
    {
        return $this->belongsTo(EventGuest::class, 'event_guests_id');
    }

    /**
     * Relationship between sending record and user.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}