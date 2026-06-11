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
use Spatie\PdfToImage\Pdf as SpatiePdf;

class CreateCardBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    public int $backoff = 10;

    protected array $guestIds;

    /**
     * Must match preview and other generators.
     */
    private int $designWidth = 420;

    /**
     * Must match preview and other generators.
     */
    private int $designHeight = 620;

    public function __construct(array $guestIds)
    {
        $this->guestIds = $guestIds;
    }

    public function handle(): void
    {
        try {
            foreach ($this->guestIds as $guestId) {
                try {
                    $guest = EventGuest::with(['event.eventCard', 'qrcode'])->find($guestId);

                    if (! $guest) {
                        continue;
                    }

                    $event = $guest->event;

                    if (! $event || ! $event->eventCard) {
                        Log::warning('CreateCardBatch skipped: event or event card missing.', [
                            'guest_id' => $guestId,
                        ]);

                        continue;
                    }

                    $eventCode = $event->code ?? $event->event_code ?? null;

                    if (! $eventCode) {
                        $eventCode = 'event-' . $event->id;
                    }

                    $eventCode = trim((string) $eventCode);

                    $card = $event->eventCard;

                    /*
                    |--------------------------------------------------------------------------
                    | Stable final card directory
                    |--------------------------------------------------------------------------
                    | Real path:
                    | storage/app/public/cards/PDFCards/{eventCode}
                    |
                    | Public URL:
                    | asset('storage/cards/PDFCards/{eventCode}/{file}.jpg')
                    */
                    $cardDirectory = "cards/PDFCards/{$eventCode}";

                    if (! Storage::disk('public')->exists($cardDirectory)) {
                        Storage::disk('public')->makeDirectory($cardDirectory);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Keep existing generated card if the real image still exists
                    |--------------------------------------------------------------------------
                    | This avoids breaking already sent WhatsApp/SMS links.
                    */
                    $existingPdf = GuestPdf::where('event_guests_id', $guestId)->first();

                    if ($existingPdf && $this->guestCardStillExists($existingPdf, $cardDirectory)) {
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Resolve QR safely from storage/app/public
                    |--------------------------------------------------------------------------
                    */
                    $qrRelativePath = $this->resolveQrPath(
                        $guest->qrcode?->qrcode_name,
                        $event,
                        $eventCode
                    );

                    $hasQrCode = $qrRelativePath !== null;

                    /*
                    |--------------------------------------------------------------------------
                    | Get exact output positions from EventCard helper methods
                    |--------------------------------------------------------------------------
                    */
                    $guestNameX = method_exists($card, 'getGuestX')
                        ? $card->getGuestX($this->designWidth)
                        : ($card->guestNameX ?? $card->guest_name_x ?? 210);

                    $guestNameY = method_exists($card, 'getGuestY')
                        ? $card->getGuestY($this->designHeight)
                        : ($card->guestNameY ?? $card->guest_name_y ?? 115);

                    $cardTypeX = method_exists($card, 'getCardTypeX')
                        ? $card->getCardTypeX($this->designWidth)
                        : ($card->cardTypePositionX ?? $card->card_type_position_x ?? 210);

                    $cardTypeY = method_exists($card, 'getCardTypeY')
                        ? $card->getCardTypeY($this->designHeight)
                        : ($card->cardTypePositionY ?? $card->card_type_position_y ?? 540);

                    $qrCodeX = method_exists($card, 'getQrCodeX')
                        ? $card->getQrCodeX($this->designWidth)
                        : ($card->qrCodePositionX ?? $card->qr_code_position_x ?? 105);

                    $qrCodeY = method_exists($card, 'getQrCodeY')
                        ? $card->getQrCodeY($this->designHeight)
                        : ($card->qrCodePositionY ?? $card->qr_code_position_y ?? 500);

                    $qrCodeSize = method_exists($card, 'getQrCodeSize')
                        ? $card->getQrCodeSize($this->designWidth)
                        : ($card->qrCodeSize ?? $card->qr_code_size ?? 72);

                    $guestFontSize = method_exists($card, 'getGuestFontSize')
                        ? $card->getGuestFontSize(
                            $this->designHeight,
                            $card->guest_name_font_size ?? 12
                        )
                        : ($card->guest_name_font_size ?? 12);

                    $cardTypeFontSize = method_exists($card, 'getCardTypeFontSize')
                        ? $card->getCardTypeFontSize(
                            $this->designHeight,
                            $card->guest_cardtype_font_size ?? 8
                        )
                        : ($card->guest_cardtype_font_size ?? 8);

                    $data = [
                        'guest_name' => $guest->guest_name,
                        'guest_cardtype' => strtoupper((string) $guest->card_type),

                        /*
                        |--------------------------------------------------------------------------
                        | Important
                        |--------------------------------------------------------------------------
                        | Pass public-disk relative QR path.
                        */
                        'guest_qrcode' => $hasQrCode ? $qrRelativePath : null,

                        'event_code' => $eventCode,
                        'main_card' => $card->card_name,

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

                        'guestNameColor' => $card->guest_name_color ?? '#000000',
                        'cardTypeColor' => $card->guest_cardtype_color ?? '#000000',

                        'cardTypeBackgroundColor' => 'transparent',
                    ];

                    /*
                    |--------------------------------------------------------------------------
                    | Resolve correct Blade view safely
                    |--------------------------------------------------------------------------
                    */
                    $viewName = $hasQrCode
                        ? $this->resolveCardWithQrView()
                        : 'venecardDashboard.creatingcardview.card-without-qr-code';

                    if (! $viewName || ! view()->exists($viewName)) {
                        Log::error('CreateCardBatch view not found.', [
                            'guest_id' => $guest->id,
                            'event_id' => $event->id,
                            'has_qr_code' => $hasQrCode,
                            'view' => $viewName,
                        ]);

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Unique stable filenames
                    |--------------------------------------------------------------------------
                    */
                    $uniqueName = 'card_' . $guest->id . '_' . now()->format('YmdHis') . '_' . uniqid();

                    $pdfFile = $uniqueName . '.pdf';
                    $imageFile = $uniqueName . '.jpg';

                    $pdfRelativePath = "{$cardDirectory}/{$pdfFile}";
                    $imageRelativePath = "{$cardDirectory}/{$imageFile}";

                    $pdfPath = Storage::disk('public')->path($pdfRelativePath);
                    $imagePath = Storage::disk('public')->path($imageRelativePath);

                    /*
                    |--------------------------------------------------------------------------
                    | Generate temporary PDF
                    |--------------------------------------------------------------------------
                    */
                    $pdf = Pdf::loadView($viewName, $data)
                        ->setPaper([0, 0, $this->designWidth, $this->designHeight], 'portrait');

                    $pdf->save($pdfPath);

                    if (
                        ! Storage::disk('public')->exists($pdfRelativePath) ||
                        Storage::disk('public')->size($pdfRelativePath) <= 0
                    ) {
                        Log::error('CreateCardBatch failed: PDF was not created or is empty.', [
                            'guest_id' => $guest->id,
                            'event_id' => $event->id,
                            'pdf_path' => $pdfRelativePath,
                        ]);

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Convert PDF to final JPG
                    |--------------------------------------------------------------------------
                    */
                    $converter = new SpatiePdf($pdfPath);
                    $converter->quality(100);
                    $converter->resolution(300);
                    $converter->save($imagePath);

                    /*
                    |--------------------------------------------------------------------------
                    | Delete only temporary PDF
                    |--------------------------------------------------------------------------
                    | Never delete final JPG.
                    */
                    if (Storage::disk('public')->exists($pdfRelativePath)) {
                        Storage::disk('public')->delete($pdfRelativePath);
                    }

                    if (
                        ! Storage::disk('public')->exists($imageRelativePath) ||
                        Storage::disk('public')->size($imageRelativePath) <= 0
                    ) {
                        Log::error('CreateCardBatch failed: image was not created or is empty.', [
                            'guest_id' => $guest->id,
                            'event_id' => $event->id,
                            'image_path' => $imageRelativePath,
                        ]);

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Save or update GuestPdf
                    |--------------------------------------------------------------------------
                    | Keep existing DB style:
                    | pdf_name = only filename.
                    */
                    if ($existingPdf) {
                        $existingPdf->pdf_name = $imageFile;
                        $existingPdf->has_pdf = 1;
                        $existingPdf->save();

                        $pdfModel = $existingPdf;
                    } else {
                        $pdfModel = GuestPdf::create([
                            'event_guests_id' => $guestId,
                            'pdf_name' => $imageFile,
                            'has_pdf' => 1,
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Create WhatsApp send record safely
                    |--------------------------------------------------------------------------
                    */
                    SendWhatsappCard::firstOrCreate(
                        [
                            'event_id' => $event->id,
                            'event_guests_id' => $guestId,
                            'guest_pdf_id' => $pdfModel->id,
                        ],
                        [
                            'sent_status' => 'not sent',
                        ]
                    );
                } catch (\Throwable $e) {
                    Log::error('CreateCardBatch failed for guest.', [
                        'guest_id' => $guestId,
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/create_card_batch_errors.log'),
            ])->error('CreateCardBatch Job Failed: ' . $e->getMessage(), [
                'guest_ids' => $this->guestIds,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function resolveCardWithQrView(): ?string
    {
        $views = [
            'venecardDashboard.creatingcardview.card-with-qr-code',
            'venecardDashboard.creatingcardview.card-with-qrcode',
        ];

        foreach ($views as $view) {
            if (view()->exists($view)) {
                return $view;
            }
        }

        return null;
    }

    private function guestCardStillExists(GuestPdf $guestPdf, string $cardDirectory): bool
    {
        if (! $guestPdf->pdf_name) {
            return false;
        }

        $fileName = basename((string) $guestPdf->pdf_name);

        $possiblePaths = [
            "{$cardDirectory}/{$fileName}",
            $this->normalizePublicPath((string) $guestPdf->pdf_name),
        ];

        foreach ($possiblePaths as $path) {
            if (
                $path &&
                Storage::disk('public')->exists($path) &&
                Storage::disk('public')->size($path) > 0
            ) {
                return true;
            }
        }

        return false;
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

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        return $path;
    }
}