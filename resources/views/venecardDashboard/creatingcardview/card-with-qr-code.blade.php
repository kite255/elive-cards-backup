<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Guest Card With QR Code</title>

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
        | Safe positions
        |--------------------------------------------------------------------------
        */
        $guestnameX = $guestnameX ?? 210;
        $guestnameY = $guestnameY ?? 115;

        $cardtypeX = $cardtypeX ?? 210;
        $cardtypeY = $cardtypeY ?? 540;

        $qrcodeX = $qrcodeX ?? 105;
        $qrcodeY = $qrcodeY ?? 500;

        $qrCodeSize = $qrCodeSize ?? 72;

        /*
        |--------------------------------------------------------------------------
        | Safe font/color values
        |--------------------------------------------------------------------------
        */
        $guestFontSize = $guestFontSize ?? ($guestNameFontSize ?? 12);
        $cardTypeFontSize = $cardTypeFontSize ?? ($guestCardtypeFontSize ?? 11);

        $guestNameColor = $guestNameColor ?? '#000000';
        $cardTypeColor = $cardTypeColor ?? ($guestCardtypeColor ?? '#000000');

        /*
        |--------------------------------------------------------------------------
        | Safe data values
        |--------------------------------------------------------------------------
        */
        $mainCard = $main_card ?? null;
        $eventCode = $event_code ?? null;
        $guestQrCode = $guest_qrcode ?? null;

        $guestName = $guest_name ?? '';
        $guestCardType = $guest_cardtype ?? '';

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
        | Center-based positioning
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
        | Main card base64
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
        | QR base64
        |--------------------------------------------------------------------------
        | Important:
        | Do not recolor QR pixels here.
        | We only flatten transparency to white to prevent Imagick/Dompdf
        | from rendering transparent pixels as black.
        */
        $qrBase64 = null;

        if ($qrPath && file_exists($qrPath)) {
            try {
                $qrSource = imagecreatefrompng($qrPath);

                if ($qrSource) {
                    $qrWidth = imagesx($qrSource);
                    $qrHeight = imagesy($qrSource);

                    $qrImage = imagecreatetruecolor($qrWidth, $qrHeight);

                    $white = imagecolorallocate($qrImage, 255, 255, 255);

                    imagefill($qrImage, 0, 0, $white);
                    imagealphablending($qrImage, true);
                    imagesavealpha($qrImage, false);

                    imagecopy($qrImage, $qrSource, 0, 0, 0, 0, $qrWidth, $qrHeight);

                    ob_start();
                    imagepng($qrImage);
                    $qrPng = ob_get_clean();

                    $qrBase64 = base64_encode($qrPng);

                    imagedestroy($qrSource);
                    imagedestroy($qrImage);
                }
            } catch (\Throwable $e) {
                $qrBase64 = base64_encode(file_get_contents($qrPath));
            }
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
            z-index: 5;
            background: #ffffff;
            padding: 4px;
            border: none;
            border-radius: 0;
            box-shadow: none;
            overflow: hidden;
        }

        .qr-code {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: contain;
            background: #ffffff;
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

        @if($qrBase64)
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