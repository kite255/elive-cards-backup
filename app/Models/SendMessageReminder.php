<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SendMessageReminder extends Model
{
    protected $table = 'send_message_reminders';

    // protected $fillable = [
    //     'event_id',
    //     'event_guests_id',
    //     'request_id',
    //     'sent_status',
    //     'delivery_status',
    //     'reminder_message',
    // ];

    //relationship between send message reminder and event guest
    public function eventguest()
    {
        return $this->belongsTo(EventGuest::class,'event_guests_id');
    }
}
