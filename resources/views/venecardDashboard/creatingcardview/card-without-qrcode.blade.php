<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Card Without QR Code</title>

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

        $guestFontSize = $guestFontSize ?? ($guestNameFontSize ?? 12);
        $guestNameColor = $guestNameColor ?? '#000000';

        /*
        |--------------------------------------------------------------------------
        | Paths
        |--------------------------------------------------------------------------
        */
        $mainCardPath = public_path('storage/' . ltrim($main_card, '/'));

        /*
        |--------------------------------------------------------------------------
        | Positioning
        |--------------------------------------------------------------------------
        | X/Y are already real pixels from 420 x 620 canvas.
        | We use center alignment to match preview behavior.
        */
        $guestLeft = $guestnameX;
        $guestTop = $guestnameY;
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
    </div>
</body>
</html>