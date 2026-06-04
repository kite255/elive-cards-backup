<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestQrcode extends Model
{
     //relationship between event guests and qrcode
     public function eventguests()
     {
         return $this->belongsTo(EventGuest::class,'event_guests_id');
     }
}
