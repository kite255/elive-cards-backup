<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventGuest;
use App\Models\GuestPdf;
use App\Models\GuestQrcode;
use App\Models\SendWhatsappCard;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CreateCardController extends Controller
{
    /**
     * Must match card.blade.php designer canvas exactly.
     */
    private int $designWidth = 420;
    private int $designHeight = 620;
    private int $defaultQrSize = 72;

    public function index()
    {
        try {
            $guests = EventGuest::whereDoesntHave('pdfcard')
                ->whereHas('event', function ($query) {
                    $query->whereDate('date', '>=', Carbon::today());
                })
                ->take(10)
                ->get();

            if ($guests->isEmpty()) {
                return response()->json(['error' => 'No guests found for upcoming events.'], 404);
            }

            foreach ($guests as $guest) {
                $event = Event::with('eventCard')->find($guest->event_id);

                if (! $event || ! $event->eventCard) {
                    continue;
                }

                $qrcodeName = null;

                if ($event->event_type === 'invitation') {
                    $qrcodeName = $this->ensureGuestQrcode($guest, $event);

                    if (! $qrcodeName) {
                        continue;
                    }
                }

                $hasCard = GuestPdf::where('event_guests_id', $guest->id)->first();

                if ($hasCard) {
                    continue;
                }

                $cardDirectory = public_path("storage/cards/PDFCards/{$event->code}");

                if (! File::exists($cardDirectory)) {
                    File::makeDirectory($cardDirectory, 0755, true);
                }

                $imageFileName = 'card_yako_' . uniqid() . '.jpg';
                $imagePath = $cardDirectory . DIRECTORY_SEPARATOR . $imageFileName;

                $qrPath = $qrcodeName
                    ? public_path("storage/qrcodes/{$event->code}/{$qrcodeName}")
                    : null;

                $this->generateCardImage(
                    event: $event,
                    guestName: $guest->guest_name,
                    guestCardType: $guest->card_type,
                    outputPath: $imagePath,
                    qrPath: $qrPath
                );

                $pdfModel = new GuestPdf();
                $pdfModel->event_guests_id = $guest->id;
                $pdfModel->pdf_name = $imageFileName;
                $pdfModel->has_pdf = '1';
                $ifPdfSaved = $pdfModel->save();

                if ($ifPdfSaved && $event->event_type === 'invitation') {
                    $sendWhatsappCardInfo = new SendWhatsappCard();
                    $sendWhatsappCardInfo->event_id = $event->id;
                    $sendWhatsappCardInfo->event_guests_id = $guest->id;
                    $sendWhatsappCardInfo->guest_pdf_id = $pdfModel->id;
                    $sendWhatsappCardInfo->sent_status = 'not sent';
                    $sendWhatsappCardInfo->save();
                }
            }

            return response()->json(['message' => 'QR codes and cards created successfully.']);
        } catch (\Throwable $e) {
            Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/create_card_controller_errors.log'),
            ])->error('CreateCardController Failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Failed to create cards or QR codes.'], 500);
        }
    }

    public function downloadSampleCard($id)
    {
        $id = decrypt($id);
        $event = Event::with('eventCard')->findOrFail($id);

        if (! $event->eventCard) {
            abort(404, 'Event card template not found.');
        }

        $sampleDirectory = public_path('storage/guestsamplecard');

        if (! File::exists($sampleDirectory)) {
            File::makeDirectory($sampleDirectory, 0755, true);
        }

        $outputPath = $sampleDirectory . DIRECTORY_SEPARATOR . $event->code . '.jpg';

        if (File::exists($outputPath)) {
            unlink($outputPath);
        }

        // Always show QR on sample download so testing is accurate.
        $sampleQrBinary = QrCode::format('png')
            ->size(500)
            ->margin(1)
            ->generate('ELIVECARD-SAMPLE-' . $event->code);

        $sampleQrPath = $sampleDirectory . DIRECTORY_SEPARATOR . $event->code . '_sample_qr.png';
        file_put_contents($sampleQrPath, $sampleQrBinary);

        $this->generateCardImage(
            event: $event,
            guestName: 'Mr & Mrs John Doe',
            guestCardType: 'DOUBLE',
            outputPath: $outputPath,
            qrPath: $sampleQrPath
        );

        if (File::exists($sampleQrPath)) {
            unlink($sampleQrPath);
        }

        return response()->download($outputPath, $event->name . '_sample_card.jpg');
    }

    private function ensureGuestQrcode(EventGuest $guest, Event $event): ?string
    {
        $hasQrCode = GuestQrcode::where('event_guests_id', $guest->id)->first();

        if ($hasQrCode) {
            return $hasQrCode->qrcode_name;
        }

        $qrCodeValue = $guest->invitation_code;
        $qrCodeDirectory = public_path("storage/qrcodes/{$event->code}");

        if (! File::exists($qrCodeDirectory)) {
            File::makeDirectory($qrCodeDirectory, 0755, true);
        }

        $qrCode = QrCode::format('png')
            ->size(500)
            ->margin(1)
            ->generate($qrCodeValue);

        $qrCodeFileName = uniqid() . '.png';
        $qrCodePath = $qrCodeDirectory . DIRECTORY_SEPARATOR . $qrCodeFileName;

        file_put_contents($qrCodePath, $qrCode);

        $qrCodeDetails = new GuestQrcode();
        $qrCodeDetails->event_guests_id = $guest->id;
        $qrCodeDetails->qrcode_name = $qrCodeFileName;
        $qrCodeDetails->has_qrcode = '1';
        $qrCodeDetails->save();

        return $qrCodeFileName;
    }

    private function generateCardImage(Event $event, string $guestName, string $guestCardType, string $outputPath, ?string $qrPath = null): void
    {
        $eventCard = $event->eventCard;
        $templatePath = $this->resolvePublicStoragePath($eventCard->card_name);

        if (! $templatePath || ! File::exists($templatePath)) {
            throw new \RuntimeException('Card template image was not found: ' . $eventCard->card_name);
        }

        $template = $this->createImageFromFile($templatePath);

        if (! $template) {
            throw new \RuntimeException('Could not read card template image: ' . $templatePath);
        }

        $originalWidth = imagesx($template);
        $originalHeight = imagesy($template);

        // Permanent fix: download canvas must be exactly the same as the preview canvas.
        $width = $this->designWidth;
        $height = $this->designHeight;

        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $white);

        // Match Blade preview object-fit: fill; 420x620.
        imagecopyresampled($canvas, $template, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);
        imagedestroy($template);

        // Positions are exact pixels, not percentages.
        $guestNameX = $this->pixelValue($eventCard->guestPositionX ?? 210, $width);
        $guestNameY = $this->pixelValue($eventCard->guestPositionY ?? 115, $height);

        $cardTypeX = $this->pixelValue($eventCard->cardTypePositionX ?? 210, $width);
        $cardTypeY = $this->pixelValue($eventCard->cardTypePositionY ?? 500, $height);

        [$defaultQrX, $defaultQrY] = $this->defaultQrPosition($eventCard->qrcode_cardtype_position ?? 'right');
        $qrX = $this->pixelValue($eventCard->qrCodePositionX ?? $defaultQrX, $width);
        $qrY = $this->pixelValue($eventCard->qrCodePositionY ?? $defaultQrY, $height);
        $qrSize = $this->pixelSize($eventCard->qrCodeSize ?? $this->defaultQrSize, 30, min($width, $height));

        $this->drawCenteredText(
            image: $canvas,
            text: $guestName,
            centerX: $guestNameX,
            centerY: $guestNameY,
            fontSize: $this->exactFontSize($eventCard->guest_name_font_size ?? 12, 1),
            colorHex: $eventCard->guest_name_color ?? '#000000'
        );

        $this->drawCardTypeText(
            image: $canvas,
            text: strtoupper($guestCardType),
            centerX: $cardTypeX,
            centerY: $cardTypeY,
            fontSize: $this->exactFontSize($eventCard->guest_cardtype_font_size ?? 8, 1),
            colorHex: $eventCard->guest_cardtype_color ?? '#000000'
        );

        if ($qrPath && File::exists($qrPath)) {
            $this->placeQrCode($canvas, $qrPath, $qrX, $qrY, $qrSize);
        }

        if (! File::exists(dirname($outputPath))) {
            File::makeDirectory(dirname($outputPath), 0755, true);
        }

        imagejpeg($canvas, $outputPath, 95);
        imagedestroy($canvas);
    }

    private function resolvePublicStoragePath(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        $cleanPath = ltrim(str_replace('storage/', '', $relativePath), '/\\');
        $path = public_path('storage/' . $cleanPath);

        if (File::exists($path)) {
            return $path;
        }

        $correctedPath = str_replace('eventsCardSamples/', 'eventCardSamples/', $cleanPath);
        $path = public_path('storage/' . $correctedPath);

        if (File::exists($path)) {
            return $path;
        }

        return null;
    }

    private function createImageFromFile(string $path)
    {
        $mimeType = File::mimeType($path);

        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : null,
            default => imagecreatefromstring(file_get_contents($path)),
        };
    }

    private function drawCenteredText($image, string $text, int $centerX, int $centerY, int $fontSize, string $colorHex): void
    {
        $font = $this->fontPath();
        [$r, $g, $b] = $this->hexToRgb($colorHex);
        $color = imagecolorallocate($image, $r, $g, $b);

        if ($font) {
            $bbox = imagettfbbox($fontSize, 0, $font, $text);
            $textWidth = abs($bbox[2] - $bbox[0]);
            $textHeight = abs($bbox[7] - $bbox[1]);

            $x = (int) round($centerX - ($textWidth / 2) - $bbox[0]);
            $y = (int) round($centerY + ($textHeight / 2));

            imagettftext($image, $fontSize, 0, $x, $y, $color, $font, $text);
            return;
        }

        imagestring($image, 5, max(0, $centerX - 60), max(0, $centerY - 8), $text, $color);
    }

    private function drawCardTypeText($image, string $text, int $centerX, int $centerY, int $fontSize, string $colorHex): void
    {
        $font = $this->fontPath();
        [$r, $g, $b] = $this->hexToRgb($colorHex);
        $color = imagecolorallocate($image, $r, $g, $b);

        if ($font) {
            $bbox = imagettfbbox($fontSize, 0, $font, $text);
            $textWidth = abs($bbox[2] - $bbox[0]);
            $textHeight = abs($bbox[7] - $bbox[1]);

            $x = (int) round($centerX - ($textWidth / 2) - $bbox[0]);
            $y = (int) round($centerY + ($textHeight / 2));

            imagettftext($image, $fontSize, 0, $x, $y, $color, $font, $text);
            return;
        }

        imagestring($image, 5, max(0, $centerX - 45), max(0, $centerY - 8), $text, $color);
    }

    private function placeQrCode($image, string $qrPath, int $centerX, int $centerY, int $qrSize): void
    {
        $qrImage = imagecreatefrompng($qrPath);

        if (! $qrImage) {
            return;
        }

        $x = (int) round($centerX - ($qrSize / 2));
        $y = (int) round($centerY - ($qrSize / 2));

        $white = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, $x, $y, $x + $qrSize, $y + $qrSize, $white);

        imagecopyresampled(
            $image,
            $qrImage,
            $x,
            $y,
            0,
            0,
            $qrSize,
            $qrSize,
            imagesx($qrImage),
            imagesy($qrImage)
        );

        imagedestroy($qrImage);
    }

    private function pixelValue(float|int|string $value, int $max): int
    {
        $pixel = (int) round((float) $value);
        return max(0, min($pixel, $max));
    }

    private function pixelSize(float|int|string $value, int $min, int $max): int
    {
        $size = (int) round((float) $value);
        return max($min, min($size, $max));
    }

    private function exactFontSize(float|int|string $fontSize, int $min = 1): int
    {
        return max($min, (int) round((float) $fontSize));
    }

    private function defaultQrPosition(string $position): array
    {
        return match ($position) {
            'left' => [105, 500],
            'center' => [210, 500],
            default => [315, 500],
        };
    }

    private function hexToRgb(string $hex): array
    {
        if ($hex === 'transparent') {
            return [0, 0, 0];
        }

        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (strlen($hex) !== 6) {
            return [0, 0, 0];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function fontPath(): ?string
    {
        $possibleFonts = [
            public_path('fonts/arial.ttf'),
            storage_path('app/fonts/arial.ttf'),
            'C:\\Windows\\Fonts\\arial.ttf',
            'C:\\Windows\\Fonts\\calibri.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
        ];

        foreach ($possibleFonts as $font) {
            if ($font && File::exists($font)) {
                return $font;
            }
        }

        return null;
    }
}
