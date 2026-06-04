<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSMSWelcoming extends Model
{
    protected $table = 'event_sms_welcomings';

    protected $fillable = [
        'user_id',
        'event_id',
        'SMS_welcoming',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}