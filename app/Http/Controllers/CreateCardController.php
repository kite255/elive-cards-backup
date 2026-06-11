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
                    Log::warning('Card generation skipped because event or event card is missing.', [
                        'guest_id' => $guest->id,
                        'event_id' => $guest->event_id,
                    ]);
                    continue;
                }

                $qrcodeName = null;

                if ($event->event_type === 'invitation') {
                    $qrcodeName = $this->ensureGuestQrcode($guest, $event);

                    if (! $qrcodeName) {
                        Log::warning('Card generation skipped because QR code could not be created.', [
                            'guest_id' => $guest->id,
                            'event_id' => $event->id,
                        ]);
                        continue;
                    }
                }

                $hasCard = GuestPdf::where('event_guests_id', $guest->id)->first();

                if ($hasCard) {
                    continue;
                }

                $cardDirectory = storage_path("app/public/cards/PDFCards/{$event->code}");

                if (! File::exists($cardDirectory)) {
                    File::makeDirectory($cardDirectory, 0775, true);
                }

                $imageFileName = 'card_yako_' . uniqid('', true) . '.jpg';
                $imagePath = $cardDirectory . DIRECTORY_SEPARATOR . $imageFileName;

                $qrPath = $qrcodeName
                    ? storage_path("app/public/qrcodes/{$event->code}/{$qrcodeName}")
                    : null;

                $this->generateCardImage(
                    event: $event,
                    guestName: (string) $guest->guest_name,
                    guestCardType: (string) $guest->card_type,
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
            $this->logError('CreateCardController index failed.', $e);

            return response()->json(['error' => 'Failed to create cards or QR codes.'], 500);
        }
    }

    public function downloadSampleCard($id)
    {
        try {
            $id = decrypt($id);
            $event = Event::with('eventCard')->findOrFail($id);

            if (! $event->eventCard) {
                abort(404, 'Event card template not found.');
            }

            $sampleDirectory = storage_path('app/public/guestsamplecard');

            if (! File::exists($sampleDirectory)) {
                File::makeDirectory($sampleDirectory, 0775, true);
            }

            $outputPath = $sampleDirectory . DIRECTORY_SEPARATOR . $event->code . '.jpg';

            if (File::exists($outputPath)) {
                File::delete($outputPath);
            }

            // Always show QR on sample download so testing is accurate.
            $sampleQrPath = $sampleDirectory . DIRECTORY_SEPARATOR . $event->code . '_sample_qr.png';
            $sampleQrBinary = QrCode::format('png')
                ->size(500)
                ->margin(1)
                ->generate('ELIVECARD-SAMPLE-' . $event->code);

            File::put($sampleQrPath, $sampleQrBinary);

            $this->generateCardImage(
                event: $event,
                guestName: 'Mr & Mrs John Doe',
                guestCardType: 'DOUBLE',
                outputPath: $outputPath,
                qrPath: $sampleQrPath
            );

            if (File::exists($sampleQrPath)) {
                File::delete($sampleQrPath);
            }

            return response()->download($outputPath, $event->name . '_sample_card.jpg');
        } catch (\Throwable $e) {
            $this->logError('Sample card download failed.', $e, ['encrypted_event_id' => $id]);

            abort(500, 'Failed to generate sample card. Check storage/logs/create_card_controller_errors.log');
        }
    }

    private function ensureGuestQrcode(EventGuest $guest, Event $event): ?string
    {
        $hasQrCode = GuestQrcode::where('event_guests_id', $guest->id)->first();

        if ($hasQrCode) {
            $existingPath = storage_path("app/public/qrcodes/{$event->code}/{$hasQrCode->qrcode_name}");

            if (File::exists($existingPath) && File::size($existingPath) > 0) {
                return $hasQrCode->qrcode_name;
            }
        }

        $qrCodeValue = $guest->invitation_code;

        if (! $qrCodeValue) {
            Log::warning('Guest QR code skipped because invitation_code is empty.', [
                'guest_id' => $guest->id,
                'event_id' => $event->id,
            ]);
            return null;
        }

        $qrCodeDirectory = storage_path("app/public/qrcodes/{$event->code}");

        if (! File::exists($qrCodeDirectory)) {
            File::makeDirectory($qrCodeDirectory, 0775, true);
        }

        $qrCode = QrCode::format('png')
            ->size(500)
            ->margin(1)
            ->generate($qrCodeValue);

        $qrCodeFileName = uniqid('', true) . '.png';
        $qrCodePath = $qrCodeDirectory . DIRECTORY_SEPARATOR . $qrCodeFileName;

        File::put($qrCodePath, $qrCode);

        if (! File::exists($qrCodePath) || File::size($qrCodePath) <= 0) {
            return null;
        }

        if ($hasQrCode) {
            $hasQrCode->qrcode_name = $qrCodeFileName;
            $hasQrCode->has_qrcode = '1';
            $hasQrCode->save();

            return $qrCodeFileName;
        }

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

        if (! $eventCard) {
            throw new \RuntimeException('Event card relation is missing.');
        }

        // Prefer image field, then fallback to card_name.
        $templateRelativePath = $eventCard->image ?: $eventCard->card_name;
        $templatePath = $this->resolvePublicStoragePath($templateRelativePath);

        if (! $templatePath || ! File::exists($templatePath) || File::size($templatePath) <= 0) {
            throw new \RuntimeException('Card template image was not found or is empty: ' . ($templateRelativePath ?: 'NULL'));
        }

        $template = $this->createImageFromFile($templatePath);

        if (! $template) {
            throw new \RuntimeException('Could not read card template image: ' . $templatePath);
        }

        $originalWidth = imagesx($template);
        $originalHeight = imagesy($template);

        $width = $this->designWidth;
        $height = $this->designHeight;

        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $white);

        // Match Blade preview object-fit: fill; 420x620.
        imagecopyresampled($canvas, $template, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);
        imagedestroy($template);

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

        if ($qrPath && File::exists($qrPath) && File::size($qrPath) > 0) {
            $this->placeQrCode($canvas, $qrPath, $qrX, $qrY, $qrSize);
        }

        if (! File::exists(dirname($outputPath))) {
            File::makeDirectory(dirname($outputPath), 0775, true);
        }

        // Atomic write prevents half-written/corrupt images if the request stops mid-save.
        $temporaryOutputPath = $outputPath . '.tmp';

        if (File::exists($temporaryOutputPath)) {
            File::delete($temporaryOutputPath);
        }

        imagejpeg($canvas, $temporaryOutputPath, 95);
        imagedestroy($canvas);

        if (! File::exists($temporaryOutputPath) || File::size($temporaryOutputPath) <= 0) {
            throw new \RuntimeException('Generated card image could not be saved: ' . $temporaryOutputPath);
        }

        if (File::exists($outputPath)) {
            File::delete($outputPath);
        }

        File::move($temporaryOutputPath, $outputPath);
    }

    private function resolvePublicStoragePath(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        $cleanPath = str_replace('\\', '/', $relativePath);
        $cleanPath = trim($cleanPath);
        $cleanPath = preg_replace('#^/+#', '', $cleanPath);
        $cleanPath = preg_replace('#^(storage/|public/|app/public/)+#', '', $cleanPath);

        // Never use temporary upload paths for permanent card generation.
        if (
            str_contains($cleanPath, 'livewire-tmp/') ||
            str_starts_with($cleanPath, 'tmp/') ||
            str_contains($cleanPath, '/tmp/')
        ) {
            return null;
        }

        $possiblePaths = [
            storage_path('app/public/' . $cleanPath),
            public_path('storage/' . $cleanPath),
        ];

        $correctedPath = str_replace('eventsCardSamples/', 'eventCardSamples/', $cleanPath);

        if ($correctedPath !== $cleanPath) {
            $possiblePaths[] = storage_path('app/public/' . $correctedPath);
            $possiblePaths[] = public_path('storage/' . $correctedPath);
        }

        foreach ($possiblePaths as $path) {
            if (File::exists($path) && File::size($path) > 0) {
                return $path;
            }
        }

        return null;
    }

    private function createImageFromFile(string $path)
    {
        $mimeType = File::mimeType($path);

        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => @imagecreatefromstring(File::get($path)),
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
        $qrImage = @imagecreatefrompng($qrPath);

        if (! $qrImage) {
            return;
        }

        $x = (int) round($centerX - ($qrSize / 2));
        $y = (int) round($centerY - ($qrSize / 2));

        // Keep QR visible and scannable.
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

    private function pixelValue(float|int|string|null $value, int $max): int
    {
        $pixel = (int) round((float) ($value ?? 0));
        return max(0, min($pixel, $max));
    }

    private function pixelSize(float|int|string|null $value, int $min, int $max): int
    {
        $size = (int) round((float) ($value ?? $min));
        return max($min, min($size, $max));
    }

    private function exactFontSize(float|int|string|null $fontSize, int $min = 1): int
    {
        return max($min, (int) round((float) ($fontSize ?? $min)));
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

    private function logError(string $message, \Throwable $e, array $context = []): void
    {
        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/create_card_controller_errors.log'),
        ])->error($message . ' ' . $e->getMessage(), array_merge($context, [
            'trace' => $e->getTraceAsString(),
        ]));
    }
}
