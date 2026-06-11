<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\EventGuest;
use App\Models\GuestPdf;
use App\Models\GuestQrcode;
use App\Models\SendWhatsappCard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\PdfToImage\Pdf as SpatiePdf;

class ProcessGuestBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    public int $backoff = 10;

    protected array $guestIds;

    private int $designWidth = 420;

    private int $designHeight = 620;

    /**
     * Permanent event-based storage:
     * storage/app/public/events/event-{id}/qr-codes/
     * storage/app/public/events/event-{id}/generated-cards/
     */
    private string $eventBaseFolder = 'events';

    public function __construct(array $guestIds)
    {
        $this->guestIds = $guestIds;
    }

    public function handle(): void
    {
        try {
            foreach ($this->guestIds as $guestId) {
                if ($this->batch()?->cancelled()) {
                    return;
                }

                try {
                    $guest = EventGuest::with(['event.eventCard', 'qrcode'])->find($guestId);

                    if (! $guest) {
                        continue;
                    }

                    $event = $guest->event;

                    if (! $event || ! $event->eventCard) {
                        Log::warning('ProcessGuestBatch skipped: event or event card missing.', [
                            'guest_id' => $guestId,
                        ]);

                        continue;
                    }

                    $guestQrCode = $guest->qrcode;

                    if (! $guestQrCode || ! $this->qrFileStillExists($guestQrCode, $event)) {
                        $guestQrCode = $this->generateGuestQrCode($event, $guest, $guestQrCode);
                        $guest->load('qrcode');
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Keep existing generated card if the real image still exists
                    |--------------------------------------------------------------------------
                    */
                    $existingPdf = GuestPdf::where('event_guests_id', $guestId)->latest()->first();

                    if ($existingPdf && $this->generatedCardStillExists($existingPdf, $event)) {
                        continue;
                    }

                    $card = $event->eventCard;

                    $mainCardPath = $this->cleanPublicPath($card->image ?: $card->card_name);

                    if (
                        ! $mainCardPath ||
                        ! Storage::disk('public')->exists($mainCardPath) ||
                        Storage::disk('public')->size($mainCardPath) <= 0
                    ) {
                        Log::warning('ProcessGuestBatch skipped: card template image missing or empty.', [
                            'guest_id' => $guestId,
                            'event_id' => $event->id,
                            'main_card' => $mainCardPath,
                        ]);

                        continue;
                    }

                    $qrPath = $guestQrCode?->qrcode_name
                        ? $this->resolveQrPath($guestQrCode->qrcode_name, $event)
                        : null;

                    $hasQrCode = $qrPath !== null;

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
                        | Public-disk relative QR path
                        |--------------------------------------------------------------------------
                        */
                        'guest_qrcode' => $hasQrCode ? $qrPath : null,
                        'guest_qrcode_path' => $hasQrCode ? $qrPath : null,
                        'guest_qrcode_url' => $hasQrCode ? asset('storage/' . $qrPath) : null,

                        'event_code' => $event->code ?? $event->event_code ?? ('event-' . $event->id),

                        /*
                        |--------------------------------------------------------------------------
                        | Public-disk relative main card path
                        |--------------------------------------------------------------------------
                        */
                        'main_card' => $mainCardPath,
                        'main_card_path' => $mainCardPath,
                        'main_card_url' => asset('storage/' . $mainCardPath),

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
                    | Permanent generated card directory
                    |--------------------------------------------------------------------------
                    */
                    $generatedDirectory = $this->getGeneratedCardsDirectory($event);

                    if (! Storage::disk('public')->exists($generatedDirectory)) {
                        Storage::disk('public')->makeDirectory($generatedDirectory);
                    }

                    $viewName = $hasQrCode
                        ? $this->resolveCardWithQrView()
                        : 'venecardDashboard.creatingcardview.card-without-qr-code';

                    if (! $viewName || ! view()->exists($viewName)) {
                        Log::error('ProcessGuestBatch view not found.', [
                            'guest_id' => $guest->id,
                            'event_id' => $event->id,
                            'has_qr_code' => $hasQrCode,
                            'view' => $viewName,
                        ]);

                        continue;
                    }

                    $safeCode = $guest->invitation_code
                        ? Str::slug($guest->invitation_code, '-')
                        : 'guest-' . $guest->id;

                    if (! $safeCode) {
                        $safeCode = 'guest-' . $guest->id;
                    }

                    $uniqueName = 'card-' . $safeCode . '-' . now()->format('YmdHis') . '-' . uniqid();

                    $pdfFileName = $uniqueName . '.pdf';
                    $imageFileName = $uniqueName . '.jpg';

                    $pdfRelativePath = $generatedDirectory . '/' . $pdfFileName;
                    $imageRelativePath = $generatedDirectory . '/' . $imageFileName;

                    $pdfAbsolutePath = Storage::disk('public')->path($pdfRelativePath);
                    $imageAbsolutePath = Storage::disk('public')->path($imageRelativePath);

                    /*
                    |--------------------------------------------------------------------------
                    | Generate temporary PDF
                    |--------------------------------------------------------------------------
                    */
                    $pdf = Pdf::loadView($viewName, $data)
                        ->setPaper([0, 0, $this->designWidth, $this->designHeight], 'portrait');

                    $pdf->save($pdfAbsolutePath);

                    if (
                        ! Storage::disk('public')->exists($pdfRelativePath) ||
                        Storage::disk('public')->size($pdfRelativePath) <= 0
                    ) {
                        Log::error('ProcessGuestBatch failed: PDF was not created or is empty.', [
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
                    $converter = new SpatiePdf($pdfAbsolutePath);
                    $converter->quality(100);
                    $converter->resolution(300);
                    $converter->save($imageAbsolutePath);

                    /*
                    |--------------------------------------------------------------------------
                    | Delete only temporary PDF
                    |--------------------------------------------------------------------------
                    */
                    if (Storage::disk('public')->exists($pdfRelativePath)) {
                        Storage::disk('public')->delete($pdfRelativePath);
                    }

                    if (
                        ! Storage::disk('public')->exists($imageRelativePath) ||
                        Storage::disk('public')->size($imageRelativePath) <= 0
                    ) {
                        Log::error('ProcessGuestBatch failed: generated card image missing or empty.', [
                            'guest_id' => $guest->id,
                            'event_id' => $event->id,
                            'image_path' => $imageRelativePath,
                        ]);

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Save generated card path
                    |--------------------------------------------------------------------------
                    | This uses the safest new full relative path:
                    | events/event-57/generated-cards/card-xxx.jpg
                    */
                    if ($existingPdf) {
                        $existingPdf->pdf_name = $imageRelativePath;
                        $existingPdf->has_pdf = 1;
                        $existingPdf->save();

                        $pdfModel = $existingPdf;
                    } else {
                        $pdfModel = GuestPdf::create([
                            'event_guests_id' => $guest->id,
                            'pdf_name' => $imageRelativePath,
                            'has_pdf' => 1,
                        ]);
                    }

                    SendWhatsappCard::firstOrCreate(
                        [
                            'event_id' => $event->id,
                            'event_guests_id' => $guest->id,
                            'guest_pdf_id' => $pdfModel->id,
                        ],
                        [
                            'sent_status' => 'not sent',
                        ]
                    );
                } catch (\Throwable $e) {
                    Log::error('ProcessGuestBatch failed for guest.', [
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
                'path' => storage_path('logs/process_guest_batch_errors.log'),
            ])->error('ProcessGuestBatch Job Failed: ' . $e->getMessage(), [
                'guest_ids' => $this->guestIds,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Create or recreate one guest QR code in permanent event-based storage.
     */
    private function generateGuestQrCode(Event $event, EventGuest $guest, ?GuestQrcode $existingQrCode = null): GuestQrcode
    {
        $invitationCode = trim((string) ($guest->invitation_code ?: ('GUEST-' . $guest->id)));

        /*
        |--------------------------------------------------------------------------
        | QR opens the private invitee page
        |--------------------------------------------------------------------------
        */
        $qrValue = url('/i/' . $invitationCode);

        $directory = $this->getEventQrDirectory($event);

        if (! Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        $safeCode = $guest->invitation_code
            ? Str::slug($guest->invitation_code, '-')
            : 'guest-' . $guest->id;

        if (! $safeCode) {
            $safeCode = 'guest-' . $guest->id;
        }

        $fileName = $safeCode . '.png';
        $relativePath = $directory . '/' . $fileName;

        $qrImage = QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->backgroundColor(255, 255, 255)
            ->color(0, 0, 0)
            ->generate($qrValue);

        Storage::disk('public')->put($relativePath, $qrImage);

        if (
            ! Storage::disk('public')->exists($relativePath) ||
            Storage::disk('public')->size($relativePath) <= 0
        ) {
            throw new \RuntimeException("Failed to create QR code file for guest ID {$guest->id}");
        }

        if ($existingQrCode) {
            $existingQrCode->qrcode_name = $relativePath;
            $existingQrCode->has_qrcode = 1;
            $existingQrCode->save();

            return $existingQrCode;
        }

        return GuestQrcode::create([
            'event_guests_id' => $guest->id,
            'qrcode_name' => $relativePath,
            'has_qrcode' => 1,
        ]);
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

    private function qrFileStillExists(GuestQrcode $guestQrCode, Event $event): bool
    {
        if (! $guestQrCode->qrcode_name) {
            return false;
        }

        return $this->resolveQrPath($guestQrCode->qrcode_name, $event) !== null;
    }

    private function resolveQrPath(string $storedValue, Event $event): ?string
    {
        foreach ($this->possibleQrPaths($storedValue, $event) as $path) {
            if (
                Storage::disk('public')->exists($path) &&
                Storage::disk('public')->size($path) > 0
            ) {
                return $path;
            }
        }

        return null;
    }

    private function possibleQrPaths(string $storedValue, Event $event): array
    {
        $storedValue = $this->cleanPublicPath($storedValue);

        if (! $storedValue) {
            return [];
        }

        $paths = [];

        if (
            Str::startsWith($storedValue, 'events/') ||
            Str::startsWith($storedValue, 'qrcodes/')
        ) {
            $paths[] = $storedValue;
        }

        $eventCode = $event->code ?? $event->event_code ?? null;

        if ($eventCode) {
            $paths[] = 'qrcodes/' . trim((string) $eventCode, '/') . '/' . basename($storedValue);
        }

        $paths[] = $this->getEventQrDirectory($event) . '/' . basename($storedValue);

        return array_values(array_unique(array_filter($paths)));
    }

    private function generatedCardStillExists(GuestPdf $guestPdf, Event $event): bool
    {
        if (! $guestPdf->pdf_name) {
            return false;
        }

        foreach ($this->possibleGeneratedCardPaths($guestPdf->pdf_name, $event) as $path) {
            if (
                Storage::disk('public')->exists($path) &&
                Storage::disk('public')->size($path) > 0
            ) {
                return true;
            }
        }

        return false;
    }

    private function possibleGeneratedCardPaths(string $storedValue, Event $event): array
    {
        $storedValue = $this->cleanPublicPath($storedValue);

        if (! $storedValue) {
            return [];
        }

        $paths = [];

        if (
            Str::startsWith($storedValue, 'events/') ||
            Str::startsWith($storedValue, 'cards/')
        ) {
            $paths[] = $storedValue;
        }

        $eventCode = $event->code ?? $event->event_code ?? null;

        if ($eventCode) {
            $paths[] = 'cards/PDFCards/' . trim((string) $eventCode, '/') . '/' . basename($storedValue);
        }

        $paths[] = $this->getGeneratedCardsDirectory($event) . '/' . basename($storedValue);

        return array_values(array_unique(array_filter($paths)));
    }

    private function cleanPublicPath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');

        $path = preg_replace('#^(storage/|public/|app/public/)+#', '', $path);

        return $path ?: null;
    }

    private function getEventQrDirectory(Event $event): string
    {
        return $this->eventBaseFolder . '/event-' . $event->id . '/qr-codes';
    }

    private function getGeneratedCardsDirectory(Event $event): string
    {
        return $this->eventBaseFolder . '/event-' . $event->id . '/generated-cards';
    }
}