<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\EventGuest;
use App\Models\GuestPdf;
use App\Models\SendWhatsappCard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\PdfToImage\Pdf as SpatiePdf;

class CreateCard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    public int $backoff = 10;

    protected int|string $event_id;

    private int $designWidth = 420;

    private int $designHeight = 620;

    public function __construct($event_id)
    {
        $this->event_id = $event_id;
    }

    public function handle(): void
    {
        try {
            $event = Event::with('eventCard')->findOrFail($this->event_id);

            if (! $event->eventCard) {
                Log::warning('CreateCard skipped: event has no card template.', [
                    'event_id' => $this->event_id,
                ]);

                return;
            }

            $eventCode = $this->getSafeEventCode($event);
            $eventCard = $event->eventCard;

            $cardDirectory = "cards/PDFCards/{$eventCode}";

            if (! Storage::disk('public')->exists($cardDirectory)) {
                Storage::disk('public')->makeDirectory($cardDirectory);
            }

            EventGuest::with(['qrcode'])
                ->where('event_id', $this->event_id)
                ->orderBy('id')
                ->chunkById(100, function ($guests) use ($event, $eventCard, $eventCode, $cardDirectory) {
                    foreach ($guests as $guest) {
                        $this->createCardForGuest($event, $eventCard, $eventCode, $cardDirectory, $guest);
                    }
                });
        } catch (\Throwable $e) {
            Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/create_card_errors.log'),
            ])->error('CreateCard Job Failed: ' . $e->getMessage(), [
                'event_id' => $this->event_id,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function createCardForGuest(Event $event, mixed $eventCard, string $eventCode, string $cardDirectory, EventGuest $guest): void
    {
        try {
            $existingPdf = GuestPdf::where('event_guests_id', $guest->id)->first();

            if ($existingPdf) {
                $existingCardPath = $this->resolveExistingCardPath($existingPdf, $cardDirectory);

                if ($existingCardPath) {
                    /*
                     * Important production fix:
                     * If old DB value was only filename, convert it to stable relative public path.
                     */
                    if ($existingPdf->pdf_name !== $existingCardPath) {
                        $existingPdf->pdf_name = $existingCardPath;
                        $existingPdf->has_pdf = 1;
                        $existingPdf->save();
                    }

                    $this->ensureWhatsappRecord($event, $guest, $existingPdf);

                    return;
                }
            }

            $guestQrCodeName = $guest->qrcode?->qrcode_name;
            $qrRelativePath = $this->resolveQrPath($guestQrCodeName, $event, $eventCode);
            $hasQrCode = $qrRelativePath !== null;

            $guestNameX = method_exists($eventCard, 'getGuestX')
                ? $eventCard->getGuestX($this->designWidth)
                : ($eventCard->guestNameX ?? $eventCard->guest_name_x ?? 210);

            $guestNameY = method_exists($eventCard, 'getGuestY')
                ? $eventCard->getGuestY($this->designHeight)
                : ($eventCard->guestNameY ?? $eventCard->guest_name_y ?? 115);

            $cardTypeX = method_exists($eventCard, 'getCardTypeX')
                ? $eventCard->getCardTypeX($this->designWidth)
                : ($eventCard->cardTypePositionX ?? $eventCard->card_type_position_x ?? 210);

            $cardTypeY = method_exists($eventCard, 'getCardTypeY')
                ? $eventCard->getCardTypeY($this->designHeight)
                : ($eventCard->cardTypePositionY ?? $eventCard->card_type_position_y ?? 540);

            $qrCodeX = method_exists($eventCard, 'getQrCodeX')
                ? $eventCard->getQrCodeX($this->designWidth)
                : ($eventCard->qrCodePositionX ?? $eventCard->qr_code_position_x ?? 105);

            $qrCodeY = method_exists($eventCard, 'getQrCodeY')
                ? $eventCard->getQrCodeY($this->designHeight)
                : ($eventCard->qrCodePositionY ?? $eventCard->qr_code_position_y ?? 500);

            $qrCodeSize = method_exists($eventCard, 'getQrCodeSize')
                ? $eventCard->getQrCodeSize($this->designWidth)
                : ($eventCard->qrCodeSize ?? $eventCard->qr_code_size ?? 72);

            $guestFontSize = method_exists($eventCard, 'getGuestFontSize')
                ? $eventCard->getGuestFontSize(
                    $this->designHeight,
                    $eventCard->guest_name_font_size ?? 12
                )
                : ($eventCard->guest_name_font_size ?? 12);

            $cardTypeFontSize = method_exists($eventCard, 'getCardTypeFontSize')
                ? $eventCard->getCardTypeFontSize(
                    $this->designHeight,
                    $eventCard->guest_cardtype_font_size ?? 8
                )
                : ($eventCard->guest_cardtype_font_size ?? 8);

            $qrForegroundColor = method_exists($eventCard, 'getQrForegroundColor')
                ? $eventCard->getQrForegroundColor()
                : ($eventCard->qrCodeForegroundColor ?? '#000000');

            $qrBackgroundColor = method_exists($eventCard, 'getQrBackgroundColor')
                ? $eventCard->getQrBackgroundColor()
                : ($eventCard->qrCodeBackgroundColor ?? '#ffffff');

            $qrEyeColor = method_exists($eventCard, 'getQrEyeColor')
                ? $eventCard->getQrEyeColor()
                : ($eventCard->qrCodeEyeColor ?? $qrForegroundColor);

            $data = [
                'guest_name' => $guest->guest_name,
                'guest_cardtype' => strtoupper((string) $guest->card_type),
                'guest_qrcode' => $hasQrCode ? $qrRelativePath : null,

                'event_code' => $eventCode,
                'main_card' => $eventCard->card_name,

                'guestnameX' => $guestNameX,
                'guestnameY' => $guestNameY,

                'cardtypeX' => $cardTypeX,
                'cardtypeY' => $cardTypeY,

                'qrcodeX' => $qrCodeX,
                'qrcodeY' => $qrCodeY,

                'canvasWidth' => $this->designWidth,
                'canvasHeight' => $this->designHeight,

                'guestFontSize' => $guestFontSize,
                'cardTypeFontSize' => $cardTypeFontSize,
                'qrCodeSize' => $qrCodeSize,

                'guestNameColor' => $eventCard->guest_name_color ?? '#000000',
                'cardTypeColor' => $eventCard->guest_cardtype_color ?? '#000000',

                'qrCodeForegroundColor' => $qrForegroundColor,
                'qrCodeBackgroundColor' => $qrBackgroundColor,
                'qrCodeEyeColor' => $qrEyeColor,

                'cardTypeBackgroundColor' => 'transparent',
            ];

            $viewName = $hasQrCode
                ? 'venecardDashboard.creatingcardview.card-with-qr-code'
                : 'venecardDashboard.creatingcardview.card-without-qr-code';

            if (! view()->exists($viewName)) {
                Log::error('CreateCard view not found.', [
                    'view' => $viewName,
                    'guest_id' => $guest->id,
                    'event_id' => $event->id,
                ]);

                return;
            }

            $safeGuestName = Str::slug((string) $guest->guest_name, '-');

            if (! $safeGuestName) {
                $safeGuestName = 'guest-' . $guest->id;
            }

            $uniqueName = 'card_' . $guest->id . '_' . $safeGuestName . '_' . now()->format('YmdHis') . '_' . Str::random(8);

            $pdfFileName = $uniqueName . '.pdf';
            $imageFileName = $uniqueName . '.jpg';

            $pdfRelativePath = "{$cardDirectory}/{$pdfFileName}";
            $imageRelativePath = "{$cardDirectory}/{$imageFileName}";

            $pdfPath = Storage::disk('public')->path($pdfRelativePath);
            $imagePath = Storage::disk('public')->path($imageRelativePath);

            $pdf = Pdf::loadView($viewName, $data)
                ->setPaper([0, 0, $this->designWidth, $this->designHeight], 'portrait');

            $pdf->save($pdfPath);

            if (
                ! Storage::disk('public')->exists($pdfRelativePath) ||
                Storage::disk('public')->size($pdfRelativePath) <= 0
            ) {
                Log::error('CreateCard failed: PDF was not created or is empty.', [
                    'guest_id' => $guest->id,
                    'event_id' => $event->id,
                    'pdf_path' => $pdfRelativePath,
                ]);

                return;
            }

            $pdfToImage = new SpatiePdf($pdfPath);
            $pdfToImage->quality(100);
            $pdfToImage->resolution(300);
            $pdfToImage->save($imagePath);

            if (Storage::disk('public')->exists($pdfRelativePath)) {
                Storage::disk('public')->delete($pdfRelativePath);
            }

            if (
                ! Storage::disk('public')->exists($imageRelativePath) ||
                Storage::disk('public')->size($imageRelativePath) <= 0
            ) {
                Log::error('CreateCard failed: image was not created or is empty.', [
                    'guest_id' => $guest->id,
                    'event_id' => $event->id,
                    'image_path' => $imageRelativePath,
                ]);

                return;
            }

            $pdfModel = GuestPdf::updateOrCreate(
                [
                    'event_guests_id' => $guest->id,
                ],
                [
                    'pdf_name' => $imageRelativePath,
                    'has_pdf' => 1,
                ]
            );

            $this->ensureWhatsappRecord($event, $guest, $pdfModel);
        } catch (\Throwable $e) {
            Log::error('CreateCard failed for guest.', [
                'event_id' => $event->id,
                'guest_id' => $guest->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    private function ensureWhatsappRecord(Event $event, EventGuest $guest, GuestPdf $guestPdf): void
    {
        SendWhatsappCard::firstOrCreate(
            [
                'event_id' => $event->id,
                'event_guests_id' => $guest->id,
                'guest_pdf_id' => $guestPdf->id,
            ],
            [
                'sent_status' => 'not sent',
            ]
        );
    }

    private function getSafeEventCode(Event $event): string
    {
        $eventCode = $event->code ?? $event->event_code ?? null;

        if (! $eventCode) {
            $eventCode = 'event-' . $event->id;
        }

        $eventCode = Str::slug((string) $eventCode, '-');

        if (! $eventCode) {
            $eventCode = 'event-' . $event->id;
        }

        return $eventCode;
    }

    private function resolveExistingCardPath(GuestPdf $guestPdf, string $cardDirectory): ?string
    {
        if (! $guestPdf->pdf_name) {
            return null;
        }

        $storedPath = $this->normalizePublicPath((string) $guestPdf->pdf_name);
        $fileName = basename((string) $storedPath);

        $possiblePaths = [
            $storedPath,
            "{$cardDirectory}/{$fileName}",
            "cards/PDFCards/{$fileName}",
        ];

        foreach (array_values(array_unique(array_filter($possiblePaths))) as $path) {
            if (
                Storage::disk('public')->exists($path) &&
                Storage::disk('public')->size($path) > 0
            ) {
                return $path;
            }
        }

        return null;
    }

    private function resolveQrPath(?string $guestQrCodeName, Event $event, string $eventCode): ?string
    {
        if (! $guestQrCodeName) {
            return null;
        }

        $storedPath = $this->normalizePublicPath($guestQrCodeName);

        $candidates = [];

        if ($storedPath) {
            $candidates[] = $storedPath;
            $candidates[] = 'qrcodes/' . trim($eventCode, '/') . '/' . basename($storedPath);
            $candidates[] = 'events/event-' . $event->id . '/qr-codes/' . basename($storedPath);
        }

        foreach (array_values(array_unique(array_filter($candidates))) as $candidate) {
            if (
                Storage::disk('public')->exists($candidate) &&
                Storage::disk('public')->size($candidate) > 0
            ) {
                return $candidate;
            }
        }

        return null;
    }

    private function normalizePublicPath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $path = trim(str_replace('\\', '/', $path));
        $path = ltrim($path, '/');

        if (Str::startsWith($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }

        if (Str::startsWith($path, 'public/')) {
            $path = Str::after($path, 'public/');
        }

        return $path;
    }
}