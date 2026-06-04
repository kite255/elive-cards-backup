<?php

namespace App\Jobs;

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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\PdfToImage\Pdf as SpatiePdf;

class ProcessGuestBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $guestIds;

    private int $designWidth = 420;

    private int $designHeight = 620;

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

                if (! $guestQrCode) {
                    $guestQrCode = $this->generateGuestQrCode($guest);

                    $guest->load('qrcode');
                }

                if (GuestPdf::where('event_guests_id', $guestId)->exists()) {
                    continue;
                }

                $card = $event->eventCard;
                $hasQrCode = $guestQrCode && ! empty($guestQrCode->qrcode_name);

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
                    'guest_qrcode' => $hasQrCode ? $guestQrCode->qrcode_name : null,

                    'event_code' => $event->code,
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

                $directory = public_path("storage/cards/PDFCards/{$event->code}");

                if (! File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }

                $viewName = $hasQrCode
                    ? 'venecardDashboard.creatingcardview.card-with-qrcode'
                    : 'venecardDashboard.creatingcardview.card-without-qr-code';

                $pdf = Pdf::loadView($viewName, $data)
                    ->setPaper([0, 0, $this->designWidth, $this->designHeight], 'portrait');

                $pdfFileName = 'card_' . uniqid() . '.pdf';
                $pdfPath = $directory . DIRECTORY_SEPARATOR . $pdfFileName;

                $pdf->save($pdfPath);

                $imageFileName = pathinfo($pdfFileName, PATHINFO_FILENAME) . '.jpg';
                $imagePath = $directory . DIRECTORY_SEPARATOR . $imageFileName;

                $converter = new SpatiePdf($pdfPath);
                $converter->quality(100);
                $converter->resolution(300);

                if ($converter->save($imagePath) && File::exists($pdfPath)) {
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
                'path' => storage_path('logs/process_guest_batch_errors.log'),
            ])->error('ProcessGuestBatch Job Failed: ' . $e->getMessage(), [
                'guest_ids' => $this->guestIds,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function generateGuestQrCode(EventGuest $guest): GuestQrcode
    {
        $event = $guest->event;

        $folder = public_path("storage/qrcodes/{$event->code}");

        if (! File::exists($folder)) {
            File::makeDirectory($folder, 0755, true);
        }

        $filename = uniqid('qr_') . '.png';
        $path = $folder . DIRECTORY_SEPARATOR . $filename;

        $qrValue = $guest->invitation_code ?: ('GUEST-' . $guest->id);

        $qrImage = QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->generate($qrValue);

        file_put_contents($path, $qrImage);

        return GuestQrcode::create([
            'event_guests_id' => $guest->id,
            'qrcode_name' => $filename,
            'has_qrcode' => 1,
        ]);
    }
}