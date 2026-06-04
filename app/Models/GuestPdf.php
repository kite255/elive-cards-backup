<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestPdf extends Model
{
    //relationship btn event-guests and pdf-card
    public function eventguests()
    {
        return $this->belongsTo(EventGuest::class,'event_guests_id');
    }
    
    //relationship btn guest-pdf and sendWhatsappCard
    public function sendWhatsappCards()
    {
        return $this->hasMany(SendWhatsappCard::class, 'guest_pdf_id');
    }
}
