<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Guest Card With QR Code</title>

    @php
        use Illuminate\Support\Facades\Storage;

        /*
        |--------------------------------------------------------------------------
        | Fixed canvas
        |--------------------------------------------------------------------------
        */
        $canvasWidth = (int) ($canvasWidth ?? 420);
        $canvasHeight = (int) ($canvasHeight ?? 620);

        if ($canvasWidth <= 0) {
            $canvasWidth = 420;
        }

        if ($canvasHeight <= 0) {
            $canvasHeight = 620;
        }

        /*
        |--------------------------------------------------------------------------
        | Safe positions - center based
        |--------------------------------------------------------------------------
        */
        $guestnameX = (float) ($guestnameX ?? 210);
        $guestnameY = (float) ($guestnameY ?? 115);

        $cardtypeX = (float) ($cardtypeX ?? 210);
        $cardtypeY = (float) ($cardtypeY ?? 540);

        $qrcodeX = (float) ($qrcodeX ?? 105);
        $qrcodeY = (float) ($qrcodeY ?? 500);

        $qrCodeSize = (float) ($qrCodeSize ?? 72);

        if ($qrCodeSize <= 0) {
            $qrCodeSize = 72;
        }

        /*
        |--------------------------------------------------------------------------
        | Safe font values
        |--------------------------------------------------------------------------
        */
        $guestFontSize = (float) ($guestFontSize ?? ($guestNameFontSize ?? 12));
        $cardTypeFontSize = (float) ($cardTypeFontSize ?? ($guestCardtypeFontSize ?? 11));

        if ($guestFontSize <= 0) {
            $guestFontSize = 12;
        }

        if ($cardTypeFontSize <= 0) {
            $cardTypeFontSize = 11;
        }

        /*
        |--------------------------------------------------------------------------
        | Safe color values
        |--------------------------------------------------------------------------
        */
        $guestNameColor = (string) ($guestNameColor ?? '#000000');
        $cardTypeColor = (string) ($cardTypeColor ?? ($guestCardtypeColor ?? '#000000'));

        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $guestNameColor)) {
            $guestNameColor = '#000000';
        }

        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $cardTypeColor)) {
            $cardTypeColor = '#000000';
        }

        /*
        |--------------------------------------------------------------------------
        | Safe data values
        |--------------------------------------------------------------------------
        */
        $mainCard = $main_card ?? null;
        $eventCode = $event_code ?? null;
        $eventId = $event_id ?? null;
        $guestQrCode = $guest_qrcode ?? null;

        $guestName = trim((string) ($guest_name ?? ''));
        $guestCardType = trim((string) ($guest_cardtype ?? ''));

        /*
        |--------------------------------------------------------------------------
        | Helper: normalize public disk path
        |--------------------------------------------------------------------------
        */
        $normalizePublicPath = function ($path) {
            if (! $path) {
                return null;
            }

            $path = trim(str_replace('\\', '/', (string) $path));
            $path = ltrim($path, '/');

            if (str_starts_with($path, 'storage/')) {
                $path = substr($path, strlen('storage/'));
            }

            if (str_starts_with($path, 'public/')) {
                $path = substr($path, strlen('public/'));
            }

            return $path;
        };

        /*
        |--------------------------------------------------------------------------
        | Resolve main card image from storage/app/public
        |--------------------------------------------------------------------------
        */
        $mainCardRelativePath = $normalizePublicPath($mainCard);
        $mainCardPath = null;

        if (
            $mainCardRelativePath &&
            Storage::disk('public')->exists($mainCardRelativePath) &&
            Storage::disk('public')->size($mainCardRelativePath) > 0
        ) {
            $mainCardPath = Storage::disk('public')->path($mainCardRelativePath);
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve QR image from storage/app/public
        |--------------------------------------------------------------------------
        */
        $qrRelativePath = null;

        if ($guestQrCode) {
            $candidateQrPath = $normalizePublicPath($guestQrCode);
            $qrCandidates = [];

            if ($candidateQrPath) {
                $qrCandidates[] = $candidateQrPath;
            }

            if ($eventCode && $candidateQrPath) {
                $qrCandidates[] = 'qrcodes/' . trim((string) $eventCode, '/') . '/' . basename($candidateQrPath);
                $qrCandidates[] = 'qr-codes/' . trim((string) $eventCode, '/') . '/' . basename($candidateQrPath);
                $qrCandidates[] = 'events/' . trim((string) $eventCode, '/') . '/qr-codes/' . basename($candidateQrPath);
            }

            if ($eventId && $candidateQrPath) {
                $qrCandidates[] = 'events/event-' . (int) $eventId . '/qr-codes/' . basename($candidateQrPath);
            }

            foreach (array_values(array_unique(array_filter($qrCandidates))) as $qrCandidate) {
                if (
                    $qrCandidate &&
                    Storage::disk('public')->exists($qrCandidate) &&
                    Storage::disk('public')->size($qrCandidate) > 0
                ) {
                    $qrRelativePath = $qrCandidate;
                    break;
                }
            }
        }

        $qrPath = $qrRelativePath
            ? Storage::disk('public')->path($qrRelativePath)
            : null;

        /*
        |--------------------------------------------------------------------------
        | Positioning
        |--------------------------------------------------------------------------
        | Do not use CSS transform. Some PDF/image renderers shift transform values.
        */
        $guestBlockWidth = $canvasWidth;
        $guestLeft = $guestnameX - ($guestBlockWidth / 2);
        $guestTop = $guestnameY - ($guestFontSize / 1.4);

        $cardTypeBlockWidth = $canvasWidth;
        $cardTypeLeft = $cardtypeX - ($cardTypeBlockWidth / 2);
        $cardTypeTop = $cardtypeY - ($cardTypeFontSize / 1.4);

        $qrLeft = $qrcodeX - ($qrCodeSize / 2);
        $qrTop = $qrcodeY - ($qrCodeSize / 2);

        /*
        |--------------------------------------------------------------------------
        | Main card base64
        |--------------------------------------------------------------------------
        */
        $mainCardBase64 = null;
        $mainCardMime = 'image/jpeg';

        if ($mainCardPath && is_file($mainCardPath) && filesize($mainCardPath) > 0) {
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
        | Critical:
        | QR transparency is flattened to white. This prevents black QR background
        | corruption when Dompdf / Imagick / Browsers render transparent pixels.
        */
        $qrBase64 = null;

        if ($qrPath && is_file($qrPath) && filesize($qrPath) > 0) {
            try {
                if (function_exists('imagecreatefrompng')) {
                    $qrSource = @imagecreatefrompng($qrPath);

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

                        if ($qrPng) {
                            $qrBase64 = base64_encode($qrPng);
                        }

                        imagedestroy($qrSource);
                        imagedestroy($qrImage);
                    }
                }

                if (! $qrBase64) {
                    $qrBase64 = base64_encode(file_get_contents($qrPath));
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
            background: #ffffff;
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
            display: block;
            border: none;
        }

        .guest-name {
            position: absolute;
            left: {{ $guestLeft }}px;
            top: {{ $guestTop }}px;
            width: {{ $guestBlockWidth }}px;
            z-index: 3;
            color: {{ $guestNameColor }};
            font-size: {{ $guestFontSize }}px;
            font-weight: 700;
            line-height: 1.2;
            white-space: nowrap;
            text-align: center;
            background: transparent;
            border: none;
            padding: 0;
        }

        .card-type {
            position: absolute;
            left: {{ $cardTypeLeft }}px;
            top: {{ $cardTypeTop }}px;
            width: {{ $cardTypeBlockWidth }}px;
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
            box-shadow: none !important;
        }

        .qr-wrap {
            position: absolute;
            left: {{ $qrLeft }}px;
            top: {{ $qrTop }}px;
            width: {{ $qrCodeSize }}px;
            height: {{ $qrCodeSize }}px;
            z-index: 5;
            background: #ffffff;
            padding: 0;
            border: none;
            border-radius: 0;
            box-shadow: none;
            overflow: hidden;
        }

        .qr-code {
            width: {{ $qrCodeSize }}px;
            height: {{ $qrCodeSize }}px;
            display: block;
            background: #ffffff;
            border: none;
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

        @if($guestName !== '')
            <div class="guest-name">{{ $guestName }}</div>
        @endif

        @if($guestCardType !== '')
            <div class="card-type">{{ strtoupper($guestCardType) }}</div>
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