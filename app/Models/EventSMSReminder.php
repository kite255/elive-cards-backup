<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSMSReminder extends Model
{
    protected $table = 'event_sms_reminders';

    protected $fillable = [
        'user_id',
        'event_id',
        'SMS_reminder',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}