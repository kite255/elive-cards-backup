<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkSMS extends Model
{
    protected $table = 'bulk_sms';

    protected $fillable = [
        'user_id',
        'phone',
        'message',
        'sender_id',
        'request_id',
        'sent_status',
        'delivery_status',
        'response',
        'sent_at',
        'delivered_at',
    ];

    protected $casts = [
        'response' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}