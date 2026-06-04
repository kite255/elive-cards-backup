<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContributionCardCaption extends Model
{
    protected $table = 'contribution_card_captions';

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
