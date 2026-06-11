<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\EventGuest;
use App\Models\GuestQrcode;
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

class CreateQrcodeBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $guest_ids;

    /**
     * Base public storage folder:
     * storage/app/public/events/event-{id}/qr-codes/
     */
    private string $qrCodeBaseFolder = 'events';

    public function __construct(array $guest_ids)
    {
        $this->guest_ids = $guest_ids;
    }

    public function handle(): void
    {
        try {
            foreach ($this->guest_ids as $guest_id) {
                try {
                    $guest = EventGuest::with('event')->find($guest_id);

                    if (! $guest) {
                        Log::warning("Guest not found with ID: {$guest_id}");
                        continue;
                    }

                    $event = $guest->event;

                    if (! $event) {
                        Log::error("Event not found for guest ID: {$guest_id}");
                        continue;
                    }

                    $existing = GuestQrcode::where('event_guests_id', $guest->id)->first();

                    /**
                     * If QR already exists and file still exists, keep it.
                     */
                    if ($existing && $this->qrFileStillExists($existing, $event)) {
                        Log::info("QR code already exists for guest ID: {$guest_id}");
                        continue;
                    }

                    $this->generateGuestQrCode($event, $guest, $existing);

                    Log::info("Successfully created QR code for guest ID: {$guest_id}");
                } catch (\Throwable $e) {
                    Log::error("Error processing guest ID {$guest_id}: " . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                    ]);
                    continue;
                }
            }
        } catch (\Throwable $e) {
            Log::error("CreateQrcodeBatch job failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Create or recreate one guest QR code in permanent event-based storage.
     */
    private function generateGuestQrCode(Event $event, EventGuest $guest, ?GuestQrcode $existing = null): GuestQrcode
    {
        $qrValue = $guest->invitation_code ?: ('GUEST-' . $guest->id);

        /**
         * Permanent event-based directory:
         * events/event-57/qr-codes
         */
        $directory = $this->getEventQrDirectory($event);

        $safeCode = $guest->invitation_code
            ? Str::slug($guest->invitation_code, '-')
            : 'guest-' . $guest->id;

        $fileName = $safeCode . '.png';

        /**
         * Relative path saved in DB:
         * events/event-57/qr-codes/guest-101.png
         */
        $relativePath = $directory . '/' . $fileName;

        $qrImage = QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->generate($qrValue);

        Storage::disk('public')->put($relativePath, $qrImage);

        if (! Storage::disk('public')->exists($relativePath)) {
            throw new \RuntimeException("Failed to create QR code file for guest ID {$guest->id}");
        }

        if ($existing) {
            $existing->qrcode_name = $relativePath;
            $existing->has_qrcode = 1;
            $existing->save();

            return $existing;
        }

        return GuestQrcode::create([
            'event_guests_id' => $guest->id,
            'qrcode_name' => $relativePath,
            'has_qrcode' => 1,
        ]);
    }

    /**
     * Check if an existing QR record still points to a real file.
     * Supports both:
     * - new path format: events/event-57/qr-codes/guest-101.png
     * - old filename-only format: abc123.png in qrcodes/{event-code}/
     */
    private function qrFileStillExists(GuestQrcode $guestQrCode, Event $event): bool
    {
        $storedValue = $guestQrCode->qrcode_name;

        if (! $storedValue) {
            return false;
        }

        foreach ($this->possibleQrPaths($storedValue, $event) as $path) {
            if (Storage::disk('public')->exists($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve old and new QR path formats.
     */
    private function possibleQrPaths(string $storedValue, Event $event): array
    {
        $storedValue = str_replace('\\', '/', trim($storedValue));

        $paths = [];

        /**
         * New format already contains folder path.
         */
        if (
            Str::startsWith($storedValue, 'events/') ||
            Str::startsWith($storedValue, 'qrcodes/')
        ) {
            $paths[] = ltrim($storedValue, '/');
        }

        /**
         * Old format stored only filename.
         */
        $paths[] = 'qrcodes/' . $event->code . '/' . basename($storedValue);

        /**
         * New permanent location if only filename exists in DB.
         */
        $paths[] = $this->getEventQrDirectory($event) . '/' . basename($storedValue);

        return array_values(array_unique($paths));
    }

    /**
     * Permanent QR directory for one event.
     */
    private function getEventQrDirectory(Event $event): string
    {
        return $this->qrCodeBaseFolder . '/event-' . $event->id . '/qr-codes';
    }
}