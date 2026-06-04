<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    // use SoftDeletes;

    protected $casts = [
        'date' => 'date',
    ];

    public function eventCategory()
    {
        return $this->belongsTo(EventCategory::class, 'event_categories_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function eventCard()
    {
        return $this->hasOne(EventCard::class, 'event_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SMS Template Relationships
    |--------------------------------------------------------------------------
    | These names are used to display saved SMS templates in the textareas.
    */

    public function smsCard()
    {
        return $this->hasOne(EventSMSCard::class, 'event_id');
    }

    public function smsReminder()
    {
        return $this->hasOne(EventSMSReminder::class, 'event_id');
    }

    public function smsWelcoming()
    {
        return $this->hasOne(EventSMSWelcoming::class, 'event_id');
    }

    public function smsThankyou()
    {
        return $this->hasOne(EventSMSThankyou::class, 'event_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Old Relationship Names
    |--------------------------------------------------------------------------
    | Keep these so your existing Blade files/controllers do not break.
    */

    public function eventSMScard()
    {
        return $this->smsCard();
    }

    public function remindersms()
    {
        return $this->smsReminder();
    }

    public function welcomingsms()
    {
        return $this->smsWelcoming();
    }

    public function thankyousms()
    {
        return $this->smsThankyou();
    }

    public function eventGuests()
    {
        return $this->hasMany(EventGuest::class, 'event_id');
    }

    public function contributionCardCaption()
    {
        return $this->hasOne(ContributionCardCaption::class, 'event_id');
    }
}