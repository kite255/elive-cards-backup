<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventCategory extends Model
{

    use SoftDeletes;


    // create relation ship between event categories and user of a system
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //relationship between event and event categories
    public function event()
    {
        return $this->hasMany(Event::class,'event_categories_id');
    }
  
}
