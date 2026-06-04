<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SendWhatsappCard extends Model
{
    //relationship btn event-guest and sendWhatsapp Card
    public function eventguests()
    {
        return $this->belongsTo(EventGuest::class,'event_guests_id');
    }

    //relationship btn guest-pdf and sendWhatsappCard
    public function guestpdfs()
    {
        return $this->belongsTo(GuestPdf::class,'guest_pdf_id');
    }
}
