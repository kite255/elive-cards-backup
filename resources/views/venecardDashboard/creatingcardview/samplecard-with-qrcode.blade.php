<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sample Card With QR Code</title>

    @php
        /*
        |--------------------------------------------------------------------------
        | Fixed canvas
        |--------------------------------------------------------------------------
        | Must match preview, CreateCard job, and CreateCardController.
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
        $cardtypeY = $cardtypeY ?? 395;

        $qrcodeX = $qrcodeX ?? 75;
        $qrcodeY = $qrcodeY ?? 420;

        $qrCodeSize = $qrCodeSize ?? 72;

        $guestFontSize = $guestFontSize ?? ($guestNameFontSize ?? 12);
        $cardTypeFontSize = $cardTypeFontSize ?? ($guestCardtypeFontSize ?? 8);

        $guestNameColor = $guestNameColor ?? '#000000';
        $cardTypeColor = $cardTypeColor ?? ($guestCardtypeColor ?? '#000000');

        /*
        |--------------------------------------------------------------------------
        | Paths
        |--------------------------------------------------------------------------
        */
        $mainCardPath = public_path('storage/' . ltrim($main_card, '/'));

        /*
        |--------------------------------------------------------------------------
        | QR source
        |--------------------------------------------------------------------------
        | For sample card view, $guest_qrcode is usually raw PNG binary.
        | If it already looks like base64, use it directly; otherwise encode it.
        */
        $qrBase64 = null;

        if (! empty($guest_qrcode)) {
            $qrBase64 = base64_encode($guest_qrcode);
        }

        /*
        |--------------------------------------------------------------------------
        | Convert center positions to top-left positions
        |--------------------------------------------------------------------------
        */
        $guestLeft = $guestnameX;
        $guestTop = $guestnameY;

        $cardTypeLeft = $cardtypeX;
        $cardTypeTop = $cardtypeY;

        $qrLeft = $qrcodeX - ($qrCodeSize / 2);
        $qrTop = $qrcodeY - ($qrCodeSize / 2);
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
            background: #ffffff;
            padding: 4px;
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
        @if(file_exists($mainCardPath))
            <img
                src="data:image/jpeg;base64,{{ base64_encode(file_get_contents($mainCardPath)) }}"
                alt="Main Card"
                class="main-card"
            >
        @endif

        <div class="guest-name">
            {{ $guest_name }}
        </div>

        @if(!empty($guest_cardtype))
            <div class="card-type">
                {{ strtoupper($guest_cardtype) }}
            </div>
        @endif

        @if(!empty($qrBase64))
            <div class="qr-wrap">
                <img
                    src="data:image/png;base64,{{ $qrBase64 }}"
                    alt="QR Code"
                    class="qr-code"
                >
            </div>
        @endif
    </div>
</body>
</html>