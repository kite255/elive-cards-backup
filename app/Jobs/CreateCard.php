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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Spatie\PdfToImage\Pdf as SpatiePdf;

class CreateCard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

            $eventCode = $event->code ?? $event->event_code ?? null;

            if (! $eventCode) {
                Log::warning('CreateCard skipped: event code is missing.', [
                    'event_id' => $event->id,
                ]);

                return;
            }

            $eventCard = $event->eventCard;

            $directoryPath = public_path("storage/cards/PDFCards/{$eventCode}");

            if (! File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0755, true);
            }

            $guests = EventGuest::with('qrcode')
                ->where('event_id', $this->event_id)
                ->get();

            foreach ($guests as $guest) {
                try {
                    /*
                     * Skip guests that already have a generated card.
                     * To regenerate, delete GuestPdf first.
                     */
                    if (GuestPdf::where('event_guests_id', $guest->id)->exists()) {
                        continue;
                    }

                    $guestQrCodeName = $guest->qrcode?->qrcode_name;

                    $qrPublicPath = $guestQrCodeName
                        ? public_path("storage/qrcodes/{$eventCode}/{$guestQrCodeName}")
                        : null;

                    $hasQrCode = $guestQrCodeName && $qrPublicPath && File::exists($qrPublicPath);

                    $guestNameX = method_exists($eventCard, 'getGuestX')
                        ? $eventCard->getGuestX($this->designWidth)
                        : ($eventCard->guestNameX ?? $eventCard->guest_name_x ?? 210);

                    $guestNameY = method_exists($eventCard, 'getGuestY')
                        ? $eventCard->getGuestY($this->designHeight)
                        : ($eventCard->guestNameY ?? $eventCard->guest_name_y ?? 115);

                    $cardTypeX = method_exists($eventCard, 'getCardTypeX')
                        ? $eventCard->getCardTypeX($this->designWidth)
                        : ($eventCard->cardTypePositionX ?? 210);

                    $cardTypeY = method_exists($eventCard, 'getCardTypeY')
                        ? $eventCard->getCardTypeY($this->designHeight)
                        : ($eventCard->cardTypePositionY ?? 540);

                    $qrCodeX = method_exists($eventCard, 'getQrCodeX')
                        ? $eventCard->getQrCodeX($this->designWidth)
                        : ($eventCard->qrCodePositionX ?? 105);

                    $qrCodeY = method_exists($eventCard, 'getQrCodeY')
                        ? $eventCard->getQrCodeY($this->designHeight)
                        : ($eventCard->qrCodePositionY ?? 500);

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
                        'guest_qrcode' => $hasQrCode ? $guestQrCodeName : null,

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

                    /*
                     * Important:
                     * Use the fixed hyphenated view names.
                     */
                    $viewName = $hasQrCode
                        ? 'venecardDashboard.creatingcardview.card-with-qr-code'
                        : 'venecardDashboard.creatingcardview.card-without-qr-code';

                    if (! view()->exists($viewName)) {
                        Log::error('CreateCard view not found.', [
                            'view' => $viewName,
                            'guest_id' => $guest->id,
                            'event_id' => $event->id,
                        ]);

                        continue;
                    }

                    $pdf = Pdf::loadView($viewName, $data)
                        ->setPaper([0, 0, $this->designWidth, $this->designHeight], 'portrait');

                    $pdfFileName = 'card_yako_' . uniqid() . '.pdf';
                    $pdfPath = $directoryPath . DIRECTORY_SEPARATOR . $pdfFileName;

                    $pdf->save($pdfPath);

                    $imageFileName = pathinfo($pdfFileName, PATHINFO_FILENAME) . '.jpg';
                    $imagePath = $directoryPath . DIRECTORY_SEPARATOR . $imageFileName;

                    $pdfToImage = new SpatiePdf($pdfPath);
                    $pdfToImage->quality(100);
                    $pdfToImage->resolution(300);

                    $saved = $pdfToImage->save($imagePath);

                    if ($saved && File::exists($pdfPath)) {
                        File::delete($pdfPath);
                    }

                    if (! File::exists($imagePath)) {
                        Log::error('CreateCard failed: image was not created.', [
                            'guest_id' => $guest->id,
                            'event_id' => $event->id,
                            'image_path' => $imagePath,
                        ]);

                        continue;
                    }

                    $pdfModel = GuestPdf::create([
                        'event_guests_id' => $guest->id,
                        'pdf_name' => $imageFileName,
                        'has_pdf' => 1,
                    ]);

                    SendWhatsappCard::create([
                        'event_id' => $event->id,
                        'event_guests_id' => $guest->id,
                        'guest_pdf_id' => $pdfModel->id,
                        'sent_status' => 'not sent',
                    ]);
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
}