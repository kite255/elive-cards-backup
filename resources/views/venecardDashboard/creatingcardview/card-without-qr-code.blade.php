<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Guest Card</title>

    @php
        /*
        |--------------------------------------------------------------------------
        | Fixed canvas
        |--------------------------------------------------------------------------
        */
        $canvasWidth = $canvasWidth ?? 420;
        $canvasHeight = $canvasHeight ?? 620;

        /*
        |--------------------------------------------------------------------------
        | Safe values
        |--------------------------------------------------------------------------
        */
        $guestnameX = $guestnameX ?? 210;
        $guestnameY = $guestnameY ?? 70;

        $cardtypeX = $cardtypeX ?? 210;
        $cardtypeY = $cardtypeY ?? 540;

        $qrcodeX = $qrcodeX ?? 105;
        $qrcodeY = $qrcodeY ?? 500;

        $qrCodeSize = $qrCodeSize ?? 72;

        $guestFontSize = $guestFontSize ?? ($guestNameFontSize ?? 12);
        $cardTypeFontSize = $cardTypeFontSize ?? ($guestCardtypeFontSize ?? 11);

        $guestNameColor = $guestNameColor ?? '#000000';
        $cardTypeColor = $cardTypeColor ?? ($guestCardtypeColor ?? '#000000');

        $qrCodeForegroundColor = $qrCodeForegroundColor ?? '#000000';
        $qrCodeBackgroundColor = $qrCodeBackgroundColor ?? '#ffffff';
        $qrCodeEyeColor = $qrCodeEyeColor ?? $qrCodeForegroundColor;

        $mainCard = $main_card ?? null;
        $eventCode = $event_code ?? null;
        $guestQrCode = $guest_qrcode ?? null;

        $guestName = $guest_name ?? '';
        $guestCardType = $guest_cardtype ?? '';

        /*
        |--------------------------------------------------------------------------
        | Color helpers
        |--------------------------------------------------------------------------
        */
        $normalizeHex = function ($color, $default = '#000000') {
            $color = trim((string) $color);

            if ($color === '') {
                return $default;
            }

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
        };

        $hexToRgb = function ($hex) {
            $hex = ltrim($hex, '#');

            return [
                hexdec(substr($hex, 0, 2)),
                hexdec(substr($hex, 2, 2)),
                hexdec(substr($hex, 4, 2)),
            ];
        };

        $qrCodeForegroundColor = $normalizeHex($qrCodeForegroundColor, '#000000');
        $qrCodeBackgroundColor = $normalizeHex($qrCodeBackgroundColor, '#ffffff');
        $qrCodeEyeColor = $normalizeHex($qrCodeEyeColor, $qrCodeForegroundColor);

        /*
        |--------------------------------------------------------------------------
        | Paths
        |--------------------------------------------------------------------------
        */
        $mainCardPath = $mainCard
            ? public_path('storage/' . ltrim($mainCard, '/'))
            : null;

        $qrPath = ($eventCode && $guestQrCode)
            ? public_path('storage/qrcodes/' . $eventCode . '/' . $guestQrCode)
            : null;

        /*
        |--------------------------------------------------------------------------
        | Positioning
        |--------------------------------------------------------------------------
        */
        $guestLeft = $guestnameX;
        $guestTop = $guestnameY;

        $cardTypeLeft = $cardtypeX;
        $cardTypeTop = $cardtypeY;

        $qrLeft = $qrcodeX - ($qrCodeSize / 2);
        $qrTop = $qrcodeY - ($qrCodeSize / 2);

        /*
        |--------------------------------------------------------------------------
        | Main card image as base64
        |--------------------------------------------------------------------------
        */
        $mainCardBase64 = null;
        $mainCardMime = 'image/jpeg';

        if ($mainCardPath && file_exists($mainCardPath)) {
            $extension = strtolower(pathinfo($mainCardPath, PATHINFO_EXTENSION));

            $mainCardMime = match ($extension) {
                'png' => 'image/png',
                'webp' => 'image/webp',
                'jpg', 'jpeg' => 'image/jpeg',
                default => 'image/jpeg',
            };

            $mainCardBase64 = base64_encode(file_get_contents($mainCardPath));
        }

        /*
        |--------------------------------------------------------------------------
        | QR code as base64 - safe direct render first
        |--------------------------------------------------------------------------
        | Direct embedding avoids the black-box issue caused by transparent PNG pixels.
        */
        $qrBase64 = null;
        $qrMime = 'image/png';

        if ($qrPath && file_exists($qrPath)) {
            $qrBase64 = base64_encode(file_get_contents($qrPath));
        }
    @endphp

    <style>
        @page {
            margin: 0;
            size: {{ $canvasWidth }}px {{ $canvasHeight }}px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: {{ $canvasWidth }}px;
            height: {{ $canvasHeight }}px;
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: Arial, DejaVu Sans, sans-serif;
        }

        .card-canvas {
            position: relative;
            width: {{ $canvasWidth }}px;
            height: {{ $canvasHeight }}px;
            overflow: hidden;
            background: #ffffff;
        }

        .main-card {
            position: absolute;
            left: 0;
            top: 0;
            width: {{ $canvasWidth }}px;
            height: {{ $canvasHeight }}px;
            z-index: 1;
        }

        .guest-name {
            position: absolute;
            left: {{ $guestLeft }}px;
            top: {{ $guestTop }}px;
            transform: translate(-50%, -50%);
            z-index: 3;
            color: {{ $guestNameColor }};
            font-size: {{ $guestFontSize }}px;
            font-weight: 700;
            line-height: 1.2;
            white-space: nowrap;
            text-align: center;
        }

        .card-type {
            position: absolute;
            left: {{ $cardTypeLeft }}px;
            top: {{ $cardTypeTop }}px;
            transform: translate(-50%, -50%);
            z-index: 3;
            color: {{ $cardTypeColor }};
            font-size: {{ $cardTypeFontSize }}px;
            font-weight: 700;
            line-height: 1.2;
            white-space: nowrap;
            text-align: center;
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
        }

        .qr-wrap {
            position: absolute;
            left: {{ $qrLeft }}px;
            top: {{ $qrTop }}px;
            width: {{ $qrCodeSize }}px;
            height: {{ $qrCodeSize }}px;
            z-index: 3;
            background: {{ $qrCodeBackgroundColor }};
            padding: 6px;
            border: none;
            border-radius: 0;
            box-shadow: none;
        }

        .qr-code {
            width: 100%;
            height: 100%;
            display: block;
        }
    </style>
</head>

<body>
    <div class="card-canvas">
        @if($mainCardBase64)
            <img
                src="data:{{ $mainCardMime }};base64,{{ $mainCardBase64 }}"
                alt="Main Card"
                class="main-card"
            >
        @endif

        @if(!empty($guestName))
            <div class="guest-name">
                {{ $guestName }}
            </div>
        @endif

        @if(!empty($guestCardType))
            <div class="card-type">
                {{ strtoupper($guestCardType) }}
            </div>
        @endif

        @if(!empty($qrBase64))
            <div class="qr-wrap">
                <img
                    src="data:{{ $qrMime }};base64,{{ $qrBase64 }}"
                    alt="QR Code"
                    class="qr-code"
                >
            </div>
        @endif
    </div>
</body>
</html>
