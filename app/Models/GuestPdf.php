<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestPdf extends Model
{
    /**
     * Allow mass assignment when creating generated guest card records.
     *
     * This fixes:
     * Add [event_guests_id] to fillable property...
     */
    protected $guarded = [];

    /**
     * Relationship between event guest and generated PDF/card.
     */
    public function eventguests()
    {
        return $this->belongsTo(EventGuest::class, 'event_guests_id');
    }

    /**
     * Optional cleaner relationship name.
     */
    public function guest()
    {
        return $this->belongsTo(EventGuest::class, 'event_guests_id');
    }

    /**
     * Relationship between guest PDF/card and WhatsApp sending records.
     */
    public function sendWhatsappCards()
    {
        return $this->hasMany(SendWhatsappCard::class, 'guest_pdf_id');
    }
}