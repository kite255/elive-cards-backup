<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestQrcode extends Model
{
    /**
     * Allow mass assignment for QR code creation.
     *
     * This fixes:
     * Add [event_guests_id] to fillable property...
     */
    protected $guarded = [];

    /**
     * Relationship between guest QR code and event guest.
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
}