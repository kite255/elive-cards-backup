<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class EventGuest extends Model
{
    use SoftDeletes;

    protected $table = 'event_guests';

    protected $fillable = [
        'user_id',
        'event_id',
        'guest_name',
        'guest_phone',
        'card_type',
        'invitation_code',
        'note',
        'scanning_times',
        'payed',
    ];

    protected $casts = [
        'scanning_times' => 'integer',
        'payed' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function qrcode()
    {
        return $this->hasOne(GuestQrcode::class, 'event_guests_id');
    }

    public function pdfcard()
    {
        return $this->hasOne(GuestPdf::class, 'event_guests_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Alias relationship
    |--------------------------------------------------------------------------
    | Use this cleaner name in Blade/controllers:
    | $guest->guestPdf
    */
    public function guestPdf()
    {
        return $this->hasOne(GuestPdf::class, 'event_guests_id');
    }

    public function whatsappcard()
    {
        return $this->hasOne(SendWhatsappCard::class, 'event_guests_id');
    }

    public function sendwhatsappcard()
    {
        return $this->hasOne(SendWhatsappCard::class, 'event_guests_id');
    }

    public function messagecard()
    {
        return $this->hasOne(SendMessageCard::class, 'event_guests_id');
    }

    public function sendmessagecard()
    {
        return $this->hasOne(SendMessageCard::class, 'event_guests_id');
    }

    public function sendmessagereminder()
    {
        return $this->hasOne(SendMessageReminder::class, 'event_guests_id');
    }

    public function sendmessagethankyou()
    {
        return $this->hasOne(SendMessageThankyou::class, 'event_guests_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Generated card helpers
    |--------------------------------------------------------------------------
    */

    public function getHasGeneratedCardAttribute(): bool
    {
        $pdf = $this->relationLoaded('guestPdf')
            ? $this->guestPdf
            : $this->guestPdf()->first();

        return $pdf !== null && (int) $pdf->has_pdf === 1 && filled($pdf->pdf_name);
    }

    public function getGeneratedCardPathAttribute(): ?string
    {
        $pdf = $this->relationLoaded('guestPdf')
            ? $this->guestPdf
            : $this->guestPdf()->first();

        if (! $pdf || blank($pdf->pdf_name)) {
            return null;
        }

        return $pdf->pdf_name;
    }

    public function getGeneratedCardUrlAttribute(): ?string
    {
        if (! $this->generated_card_path) {
            return null;
        }

        return asset('storage/' . $this->generated_card_path);
    }

    public function getGeneratedCardExistsAttribute(): bool
    {
        if (! $this->generated_card_path) {
            return false;
        }

        return Storage::disk('public')->exists($this->generated_card_path);
    }

    public function getCardGenerationStatusAttribute(): string
    {
        if ($this->has_generated_card) {
            return 'generated';
        }

        return 'not_generated';
    }

    public function getCardGenerationStatusLabelAttribute(): string
    {
        return $this->has_generated_card ? 'Generated' : 'Not generated';
    }
}