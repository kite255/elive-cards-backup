<?php

namespace App\Jobs;

use App\Models\EventGuest;
use App\Models\GuestPdf;
use App\Models\SendWhatsappCard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Spatie\PdfToImage\Pdf as SpatiePdf;

class CreateCardBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
                $guest = EventGuest::with(['event.eventCard', 'qrcode'])->find($guestId);

                if (! $guest) {
                    continue;
                }

                if (GuestPdf::where('event_guests_id', $guestId)->exists()) {
                    continue;
                }

                $event = $guest->event;

                if (! $event || ! $event->eventCard) {
                    Log::warning('CreateCardBatch skipped: event or event card missing.', [
                        'guest_id' => $guestId,
                    ]);
                    continue;
                }

                $card = $event->eventCard;

                $hasQrCode = $guest->qrcode && ! empty($guest->qrcode->qrcode_name);

                /*
                |--------------------------------------------------------------------------
                | Get exact output positions from EventCard helper methods
                |--------------------------------------------------------------------------
                | These methods use the new percentage fields when available,
                | and fall back to old values if needed.
                */
                $guestNameX = $card->getGuestX($this->designWidth);
                $guestNameY = $card->getGuestY($this->designHeight);

                $cardTypeX = $card->getCardTypeX($this->designWidth);
                $cardTypeY = $card->getCardTypeY($this->designHeight);

                $qrCodeX = $card->getQrCodeX($this->designWidth);
                $qrCodeY = $card->getQrCodeY($this->designHeight);
                $qrCodeSize = $card->getQrCodeSize($this->designWidth);

                $guestFontSize = $card->getGuestFontSize(
                    $this->designHeight,
                    $card->guest_name_font_size ?? 12
                );

                $cardTypeFontSize = $card->getCardTypeFontSize(
                    $this->designHeight,
                    $card->guest_cardtype_font_size ?? 8
                );

                $data = [
                    'guest_name' => $guest->guest_name,
                    'guest_cardtype' => strtoupper((string) $guest->card_type),
                    'guest_qrcode' => $hasQrCode ? $guest->qrcode->qrcode_name : null,
                    'event_code' => $event->code,
                    'main_card' => $card->card_name,

                    /*
                    |--------------------------------------------------------------------------
                    | Blade compatibility
                    |--------------------------------------------------------------------------
                    */
                    'guestnameX' => $guestNameX,
                    'guestnameY' => $guestNameY,

                    'cardtypeX' => $cardTypeX,
                    'cardtypeY' => $cardTypeY,

                    'qrcodeX' => $qrCodeX,
                    'qrcodeY' => $qrCodeY,

                    /*
                    |--------------------------------------------------------------------------
                    | Extra rendering values
                    |--------------------------------------------------------------------------
                    */
                    'canvasWidth' => $this->designWidth,
                    'canvasHeight' => $this->designHeight,

                    'guestFontSize' => $guestFontSize,
                    'cardTypeFontSize' => $cardTypeFontSize,
                    'qrCodeSize' => $qrCodeSize,

                    'guestNameColor' => $card->guest_name_color ?? '#000000',
                    'cardTypeColor' => $card->guest_cardtype_color ?? '#000000',
                    'cardTypeBackgroundColor' => 'transparent',
                ];

                $directory = public_path("storage/cards/PDFCards/{$event->code}");

                if (! File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }

                /*
                |--------------------------------------------------------------------------
                | Use the correct existing Blade views
                |--------------------------------------------------------------------------
                */
                $viewName = $hasQrCode
                    ? 'venecardDashboard.creatingcardview.card-with-qrcode'
                    : 'venecardDashboard.creatingcardview.card-without-qr-code';

                $pdf = Pdf::loadView($viewName, $data)
                    ->setPaper([0, 0, $this->designWidth, $this->designHeight], 'portrait');

                $pdfFile = 'card_' . uniqid() . '.pdf';
                $pdfPath = $directory . DIRECTORY_SEPARATOR . $pdfFile;

                $pdf->save($pdfPath);

                $imageFile = pathinfo($pdfFile, PATHINFO_FILENAME) . '.jpg';
                $imagePath = $directory . DIRECTORY_SEPARATOR . $imageFile;

                $converter = new SpatiePdf($pdfPath);
                $converter->quality(100);
                $converter->resolution(300);

                if ($converter->save($imagePath) && File::exists($pdfPath)) {
                    unlink($pdfPath);
                }

                $pdfModel = GuestPdf::create([
                    'event_guests_id' => $guestId,
                    'pdf_name' => $imageFile,
                    'has_pdf' => 1,
                ]);

                SendWhatsappCard::create([
                    'event_id' => $event->id,
                    'event_guests_id' => $guestId,
                    'guest_pdf_id' => $pdfModel->id,
                    'sent_status' => 'not sent',
                ]);
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
}