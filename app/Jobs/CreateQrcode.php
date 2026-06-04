<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\EventGuest;
use App\Models\GuestQrcode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CreateQrcode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int|string $event_id;

    public function __construct($event_id)
    {
        $this->event_id = $event_id;
    }

    public function handle(): void
    {
        try {
            $event = Event::findOrFail($this->event_id);

            $guests = EventGuest::where('event_id', $this->event_id)->get();

            foreach ($guests as $guest) {
                $existingQrCode = GuestQrcode::where('event_guests_id', $guest->id)->first();

                if ($existingQrCode) {
                    continue;
                }

                $this->generateGuestQrCode($event, $guest);
            }
        } catch (\Throwable $e) {
            Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/create_qrcode_errors.log'),
            ])->error('CreateQrcode Job Failed: ' . $e->getMessage(), [
                'event_id' => $this->event_id,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function generateGuestQrCode(Event $event, EventGuest $guest): GuestQrcode
    {
        $directoryPath = public_path("storage/qrcodes/{$event->code}");

        if (! File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0755, true);
        }

        $qrValue = $guest->invitation_code ?: ('GUEST-' . $guest->id);

        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->generate($qrValue);

        $fileName = uniqid("event_{$event->id}_qr_") . '.png';
        $filePath = $directoryPath . DIRECTORY_SEPARATOR . $fileName;

        file_put_contents($filePath, $qrCode);

        return GuestQrcode::create([
            'event_guests_id' => $guest->id,
            'qrcode_name' => $fileName,
            'has_qrcode' => 1,
        ]);
    }
}