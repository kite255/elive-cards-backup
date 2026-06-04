<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventCard extends Model
{
    protected $guarded = [];

    protected $casts = [
        /*
        |--------------------------------------------------------------------------
        | Old pixel-based positions
        |--------------------------------------------------------------------------
        */
        'guestPositionX' => 'decimal:2',
        'guestPositionY' => 'decimal:2',

        'cardTypePositionX' => 'decimal:2',
        'cardTypePositionY' => 'decimal:2',

        'qrCodePositionX' => 'decimal:2',
        'qrCodePositionY' => 'decimal:2',

        /*
        |--------------------------------------------------------------------------
        | Percentage-based positions
        |--------------------------------------------------------------------------
        */
        'guestPositionXPercent' => 'decimal:4',
        'guestPositionYPercent' => 'decimal:4',
        'guestFontSizePercent' => 'decimal:4',

        'cardTypePositionXPercent' => 'decimal:4',
        'cardTypePositionYPercent' => 'decimal:4',
        'cardTypeWidthPercent' => 'decimal:4',
        'cardTypeHeightPercent' => 'decimal:4',
        'cardTypeFontSizePercent' => 'decimal:4',

        'qrCodePositionXPercent' => 'decimal:4',
        'qrCodePositionYPercent' => 'decimal:4',
        'qrCodeSizePercent' => 'decimal:4',

        /*
        |--------------------------------------------------------------------------
        | QR color settings
        |--------------------------------------------------------------------------
        */
        'qrCodeForegroundColor' => 'string',
        'qrCodeBackgroundColor' => 'string',
        'qrCodeEyeColor' => 'string',
    ];

    /*
    |--------------------------------------------------------------------------
    | Guest name helpers
    |--------------------------------------------------------------------------
    */
    public function getGuestX(int|float $cardWidth): float
    {
        return $this->guestPositionXPercent !== null
            ? ((float) $this->guestPositionXPercent / 100) * $cardWidth
            : (float) ($this->guestPositionX ?? 0);
    }

    public function getGuestY(int|float $cardHeight): float
    {
        return $this->guestPositionYPercent !== null
            ? ((float) $this->guestPositionYPercent / 100) * $cardHeight
            : (float) ($this->guestPositionY ?? 0);
    }

    public function getGuestFontSize(int|float $cardHeight, int|float $default = 12): float
    {
        return $this->guestFontSizePercent !== null
            ? ((float) $this->guestFontSizePercent / 100) * $cardHeight
            : (float) $default;
    }

    /*
    |--------------------------------------------------------------------------
    | Card type helpers
    |--------------------------------------------------------------------------
    */
    public function getCardTypeX(int|float $cardWidth): float
    {
        return $this->cardTypePositionXPercent !== null
            ? ((float) $this->cardTypePositionXPercent / 100) * $cardWidth
            : (float) ($this->cardTypePositionX ?? 0);
    }

    public function getCardTypeY(int|float $cardHeight): float
    {
        return $this->cardTypePositionYPercent !== null
            ? ((float) $this->cardTypePositionYPercent / 100) * $cardHeight
            : (float) ($this->cardTypePositionY ?? 0);
    }

    public function getCardTypeWidth(int|float $cardWidth, int|float $default = 95): float
    {
        return $this->cardTypeWidthPercent !== null
            ? ((float) $this->cardTypeWidthPercent / 100) * $cardWidth
            : (float) $default;
    }

    public function getCardTypeHeight(int|float $cardHeight, int|float $default = 22): float
    {
        return $this->cardTypeHeightPercent !== null
            ? ((float) $this->cardTypeHeightPercent / 100) * $cardHeight
            : (float) $default;
    }

    public function getCardTypeFontSize(int|float $cardHeight, int|float $default = 11): float
    {
        return $this->cardTypeFontSizePercent !== null
            ? ((float) $this->cardTypeFontSizePercent / 100) * $cardHeight
            : (float) $default;
    }

    /*
    |--------------------------------------------------------------------------
    | QR position and size helpers
    |--------------------------------------------------------------------------
    */
    public function getQrCodeX(int|float $cardWidth): float
    {
        return $this->qrCodePositionXPercent !== null
            ? ((float) $this->qrCodePositionXPercent / 100) * $cardWidth
            : (float) ($this->qrCodePositionX ?? 0);
    }

    public function getQrCodeY(int|float $cardHeight): float
    {
        return $this->qrCodePositionYPercent !== null
            ? ((float) $this->qrCodePositionYPercent / 100) * $cardHeight
            : (float) ($this->qrCodePositionY ?? 0);
    }

    public function getQrCodeSize(int|float $cardWidth, int|float $default = 75): float
    {
        return $this->qrCodeSizePercent !== null
            ? ((float) $this->qrCodeSizePercent / 100) * $cardWidth
            : (float) $default;
    }

    /*
    |--------------------------------------------------------------------------
    | QR color helpers
    |--------------------------------------------------------------------------
    */
    public function getQrForegroundColor(): string
    {
        return $this->normalizeHexColor($this->qrCodeForegroundColor ?? '#000000', '#000000');
    }

    public function getQrBackgroundColor(): string
    {
        return $this->normalizeHexColor($this->qrCodeBackgroundColor ?? '#ffffff', '#ffffff');
    }

    public function getQrEyeColor(): string
    {
        return $this->normalizeHexColor(
            $this->qrCodeEyeColor ?? $this->getQrForegroundColor(),
            $this->getQrForegroundColor()
        );
    }

    public function getQrForegroundRgb(): array
    {
        return $this->hexToRgb($this->getQrForegroundColor());
    }

    public function getQrBackgroundRgb(): array
    {
        return $this->hexToRgb($this->getQrBackgroundColor());
    }

    public function getQrEyeRgb(): array
    {
        return $this->hexToRgb($this->getQrEyeColor());
    }

    /*
    |--------------------------------------------------------------------------
    | Internal color helpers
    |--------------------------------------------------------------------------
    */
    private function normalizeHexColor(?string $color, string $default): string
    {
        if (! $color) {
            return $default;
        }

        $color = trim($color);

        if (! str_starts_with($color, '#')) {
            $color = '#' . $color;
        }

        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            return strtolower($color);
        }

        if (preg_match('/^#[0-9A-Fa-f]{3}$/', $color)) {
            return strtolower(
                '#' .
                $color[1] . $color[1] .
                $color[2] . $color[2] .
                $color[3] . $color[3]
            );
        }

        return $default;
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}