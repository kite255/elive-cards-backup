<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\EventGuest;
use App\Models\GuestQrcode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Bus\Batchable;

class CreateQrcodeBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $guest_ids;

    public function __construct(array $guest_ids)
    {
        $this->guest_ids = $guest_ids;
    }

    public function handle()
    {
        try {
            foreach ($this->guest_ids as $guest_id) {
                try {
                    $guest = EventGuest::find($guest_id);
                    if (!$guest) {
                        Log::warning("Guest not found with ID: {$guest_id}");
                        continue;
                    }

                    $existing = GuestQrcode::where('event_guests_id', $guest->id)->first();
                    if ($existing) {
                        Log::info("QR code already exists for guest ID: {$guest_id}");
                        continue;
                    }

                    $event = $guest->event;
                    if (!$event) {
                        Log::error("Event not found for guest ID: {$guest_id}");
                        continue;
                    }

                    $folder = public_path("storage/qrcodes/{$event->code}");

                    if (!File::exists($folder)) {
                        File::makeDirectory($folder, 0755, true);
                    }

                    $filename = uniqid("qr_") . '.png';
                    $path = $folder . '/' . $filename;

                    $qrImage = QrCode::format('png')->size(100)->generate($guest->invitation_code);
                    file_put_contents($path, $qrImage);

                    GuestQrcode::create([
                        'event_guests_id' => $guest->id,
                        'qrcode_name' => $filename,
                        'has_qrcode' => 1,
                    ]);

                    Log::info("Successfully created QR code for guest ID: {$guest_id}");
                } catch (\Exception $e) {
                    Log::error("Error processing guest ID {$guest_id}: " . $e->getMessage());
                    continue;
                }
            }
        } catch (\Exception $e) {
            Log::error("CreateQrcodeBatch job failed: " . $e->getMessage());
            throw $e;
        }
    }
}
