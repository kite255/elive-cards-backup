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

            $guests = EventGuest::with('qrcode')
                ->where('event_id', $this->event_id)
                ->get();

            foreach ($guests as $guest) {
                if (GuestPdf::where('event_guests_id', $guest->id)->exists()) {
                    continue;
                }

                $eventCard = $event->eventCard;

                $hasQrCode = $guest->qrcode && ! empty($guest->qrcode->qrcode_name);

                $guestNameX = $eventCard->getGuestX($this->designWidth);
                $guestNameY = $eventCard->getGuestY($this->designHeight);

                $cardTypeX = $eventCard->getCardTypeX($this->designWidth);
                $cardTypeY = $eventCard->getCardTypeY($this->designHeight);

                $qrCodeX = $eventCard->getQrCodeX($this->designWidth);
                $qrCodeY = $eventCard->getQrCodeY($this->designHeight);
                $qrCodeSize = $eventCard->getQrCodeSize($this->designWidth);

                $guestFontSize = $eventCard->getGuestFontSize(
                    $this->designHeight,
                    $eventCard->guest_name_font_size ?? 12
                );

                $cardTypeFontSize = $eventCard->getCardTypeFontSize(
                    $this->designHeight,
                    $eventCard->guest_cardtype_font_size ?? 8
                );

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
                    'guest_qrcode' => $hasQrCode ? $guest->qrcode->qrcode_name : null,

                    'event_code' => $event->code,
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

                $directoryPath = public_path("storage/cards/PDFCards/{$event->code}");

                if (! File::exists($directoryPath)) {
                    File::makeDirectory($directoryPath, 0755, true);
                }

                $viewName = $hasQrCode
                    ? 'venecardDashboard.creatingcardview.card-with-qrcode'
                    : 'venecardDashboard.creatingcardview.card-without-qr-code';

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

                if ($pdfToImage->save($imagePath) && File::exists($pdfPath)) {
                    unlink($pdfPath);
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