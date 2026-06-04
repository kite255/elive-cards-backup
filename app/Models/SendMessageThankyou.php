<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SendMessageThankyou extends Model
{
    protected $table = 'send_message_thankyous';

    // protected $fillable = [
    //     'event_id',
    //     'event_guests_id',
    //     'request_id',
    //     'sent_status',
    // ];

    //relationship between send message thank you and event guest
    public function eventguest()
    {
        return $this->belongsTo(EventGuest::class,'event_guests_id');
    }
}
