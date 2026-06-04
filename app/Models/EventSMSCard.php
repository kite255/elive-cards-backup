<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSMSCard extends Model
{
    protected $table = 'event_sms_cards';

    protected $fillable = [
        'user_id',
        'event_id',
        'SMS_card',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}