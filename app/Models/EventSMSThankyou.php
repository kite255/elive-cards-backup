<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSMSThankyou extends Model
{
    protected $table = 'event_sms_thankyous';

    protected $fillable = [
        'user_id',
        'event_id',
        'SMS_thankyou',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}