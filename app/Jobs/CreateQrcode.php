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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateQrcode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 10;

    protected int|string $event_id;

    /**
     * Permanent public storage base folder.
     *
     * Final real path:
     * storage/app/public/events/event-{id}/qr-codes/
     *
     * Public URL:
     * asset('storage/events/event-{id}/qr-codes/file.png')
     */
    private string $qrCodeBaseFolder = 'events';

    public function __construct($event_id)
    {
        $this->event_id = $event_id;
    }

    public function handle(): void
    {
        try {
            $event = Event::findOrFail($this->event_id);

            EventGuest::where('event_id', $this->event_id)
                ->orderBy('id')
                ->chunkById(200, function ($guests) use ($event) {
                    foreach ($guests as $guest) {
                        try {
                            $existingQrCode = GuestQrcode::where('event_guests_id', $guest->id)->first();

                            /*
                             * Safest production behavior:
                             * If DB record exists and physical QR file exists with real size, keep it.
                             * This prevents breaking already sent cards/links.
                             */
                            if ($existingQrCode && $this->qrFileStillExists($existingQrCode, $event)) {
                                continue;
                            }

                            $this->generateGuestQrCode($event, $guest);
                        } catch (\Throwable $e) {
                            Log::error('CreateQrcode failed for guest.', [
                                'event_id' => $event->id,
                                'guest_id' => $guest->id,
                                'message' => $e->getMessage(),
                                'file' => $e->getFile(),
                                'line' => $e->getLine(),
                            ]);
                        }
                    }
                });
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

    /**
     * Create or recreate one guest QR code in permanent public storage.
     */
    private function generateGuestQrCode(Event $event, EventGuest $guest): GuestQrcode
    {
        $invitationCode = $this->ensureInvitationCode($guest);

        /*
         * Safest QR value for invitees:
         * QR opens private invitation page.
         *
         * Local example:
         * http://127.0.0.1:8002/i/ABC123
         *
         * Live example:
         * https://your-domain.com/i/ABC123
         */
        $qrValue = url('/i/' . $invitationCode);

        /*
         * Permanent event-based directory:
         * storage/app/public/events/event-57/qr-codes
         */
        $directory = $this->getEventQrDirectory($event);

        if (! Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        $safeCode = Str::slug($invitationCode, '-');

        if (! $safeCode) {
            $safeCode = 'guest-' . $guest->id;
        }

        $fileName = $safeCode . '.png';

        /*
         * Relative path saved in DB:
         * events/event-57/qr-codes/abc123.png
         */
        $relativePath = $directory . '/' . $fileName;

        /*
         * Generate QR as PNG.
         * White background is safer for PDF/image conversion and phone scanners.
         */
        $qrCodeBinary = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->backgroundColor(255, 255, 255)
            ->color(0, 0, 0)
            ->generate($qrValue);

        Storage::disk('public')->put($relativePath, $qrCodeBinary);

        if (! Storage::disk('public')->exists($relativePath)) {
            throw new \RuntimeException("Failed to create QR code file for guest ID {$guest->id}");
        }

        if (Storage::disk('public')->size($relativePath) <= 0) {
            Storage::disk('public')->delete($relativePath);

            throw new \RuntimeException("Created QR code file is empty for guest ID {$guest->id}");
        }

        return GuestQrcode::updateOrCreate(
            [
                'event_guests_id' => $guest->id,
            ],
            [
                'qrcode_name' => $relativePath,
                'has_qrcode' => 1,
            ]
        );
    }

    /**
     * Ensure each guest has a real stored invitation code.
     * This prevents QR links from pointing to codes that do not exist in DB.
     */
    private function ensureInvitationCode(EventGuest $guest): string
    {
        $existingCode = trim((string) ($guest->invitation_code ?? ''));

        if ($existingCode !== '') {
            return $existingCode;
        }

        do {
            $generatedCode = strtoupper(Str::random(8));
        } while (
            EventGuest::where('invitation_code', $generatedCode)
                ->where('id', '!=', $guest->id)
                ->exists()
        );

        $guest->invitation_code = $generatedCode;
        $guest->save();

        return $generatedCode;
    }

    /**
     * Check whether an existing QR record still points to a real non-empty file.
     */
    private function qrFileStillExists(GuestQrcode $guestQrCode, Event $event): bool
    {
        $storedValue = $guestQrCode->qrcode_name;

        if (! $storedValue) {
            return false;
        }

        $possiblePaths = $this->possibleQrPaths($storedValue, $event);

        foreach ($possiblePaths as $path) {
            if (
                Storage::disk('public')->exists($path) &&
                Storage::disk('public')->size($path) > 0
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve old and new QR path formats safely.
     */
    private function possibleQrPaths(string $storedValue, Event $event): array
    {
        $storedValue = $this->normalizePublicPath($storedValue);

        $paths = [];

        /*
         * New format or already relative public-disk path.
         */
        if ($storedValue) {
            $paths[] = $storedValue;
        }

        /*
         * Old format:
         * qrcodes/{event-code}/{file}
         */
        $eventCode = $event->code ?? $event->event_code ?? null;

        if ($eventCode && $storedValue) {
            $paths[] = 'qrcodes/' . trim((string) $eventCode, '/') . '/' . basename($storedValue);
        }

        /*
         * Current permanent format:
         * events/event-{id}/qr-codes/{file}
         */
        if ($storedValue) {
            $paths[] = $this->getEventQrDirectory($event) . '/' . basename($storedValue);
        }

        return array_values(array_unique(array_filter($paths)));
    }

    /**
     * Normalize any old/public URL-ish value to Storage::disk('public') relative path.
     */
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

    /**
     * Permanent QR directory for one event.
     */
    private function getEventQrDirectory(Event $event): string
    {
        return $this->qrCodeBaseFolder . '/event-' . $event->id . '/qr-codes';
    }
}