<?php

namespace App\Http\Controllers;

use App\Models\EventCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;

class EventCardController extends Controller
{
    /**
     * Permanent base storage folder for event-based card template images.
     *
     * Final saved example:
     * event-card-samples/event-57/card_xxxxx.jpg
     */
    private string $cardImageBaseFolder = 'event-card-samples';

    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    /**
     * Store a newly created event card.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate(
                [
                    'event_id' => 'required|exists:events,id',

                    /*
                     * This is the original card/template image.
                     * It is stored permanently in storage/app/public/event-card-samples/event-{event_id}.
                     */
                    'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',

                    // Guest name placeholder - PIXEL positions
                    'guestNameX' => 'nullable|numeric',
                    'guestNameY' => 'nullable|numeric',
                    'guestNameFontSize' => 'nullable|numeric|min:1|max:200',
                    'guestNameColor' => 'nullable|string|max:20',

                    // Card type placeholder - PIXEL positions
                    'cardTypeX' => 'nullable|numeric',
                    'cardTypeY' => 'nullable|numeric',
                    'guestCardtypeFontSize' => 'nullable|numeric|min:1|max:200',
                    'guestCardtypeColor' => 'nullable|string|max:20',
                    'guestCardtypeBackgroundColor' => 'nullable|string|max:20',

                    // QR placeholder - PIXEL positions
                    'qrCodeX' => 'nullable|numeric',
                    'qrCodeY' => 'nullable|numeric',
                    'qrcodePosition' => 'nullable|string|max:50',
                ],
                [
                    'event_id.required' => 'Event is required.',
                    'event_id.exists' => 'Selected event does not exist.',

                    'image.required' => 'Choose card image for selected event.',
                    'image.image' => 'The selected file must be an image.',
                    'image.mimes' => 'Card image must be jpg, jpeg, png, or webp.',
                    'image.max' => 'Card image must not be larger than 5MB.',
                ]
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            Alert::error('Validation Error', $e->validator->errors()->first());

            return redirect()
                ->back()
                ->withErrors($e->validator)
                ->withInput();
        }

        /*
         * Permanent multi-event rule:
         * One event should use its own card template record.
         * This prevents Event A from accidentally loading Event B's latest image.
         */
        $eventCard = EventCard::firstOrNew([
            'event_id' => $validated['event_id'],
        ]);

        if (! $eventCard->exists) {
            $eventCard->user_id = Auth::id();
            $eventCard->event_id = $validated['event_id'];
        }

        if ($request->hasFile('image')) {
            $this->syncPublicStorageLink();

            $path = $this->storeCardImage($request, (int) $validated['event_id']);

            /*
             * Permanent image compatibility:
             * - card_name is used by existing card generation code.
             * - image is used by newer preview/layout code.
             * Both columns must contain the same permanent relative path.
             */
            $eventCard->card_name = $path;
            $eventCard->image = $path;
        }

        $this->fillCardSettings($eventCard, $validated);

        $eventCard->save();

        Alert::success('Card Saved', 'Event card template saved permanently for this event.');

        return $this->successResponse($request, $eventCard);
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified event card.
     */
    public function update(Request $request, string $id)
    {
        $id = decrypt($id);

        $eventCard = EventCard::findOrFail($id);

        try {
            $validated = $request->validate(
                [
                    'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

                    // Guest name placeholder - PIXEL positions
                    'guestNameX' => 'nullable|numeric',
                    'guestNameY' => 'nullable|numeric',
                    'guestNameFontSize' => 'nullable|numeric|min:1|max:200',
                    'guestNameColor' => 'nullable|string|max:20',

                    // Card type placeholder - PIXEL positions
                    'cardTypeX' => 'nullable|numeric',
                    'cardTypeY' => 'nullable|numeric',
                    'guestCardtypeFontSize' => 'nullable|numeric|min:1|max:200',
                    'guestCardtypeColor' => 'nullable|string|max:20',
                    'guestCardtypeBackgroundColor' => 'nullable|string|max:20',

                    // QR placeholder - PIXEL positions
                    'qrCodeX' => 'nullable|numeric',
                    'qrCodeY' => 'nullable|numeric',
                    'qrcodePosition' => 'nullable|string|max:50',
                ],
                [
                    'image.image' => 'The selected file must be an image.',
                    'image.mimes' => 'Card image must be jpg, jpeg, png, or webp.',
                    'image.max' => 'Card image must not be larger than 5MB.',
                ]
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            Alert::error('Validation Error', $e->validator->errors()->first());

            return redirect()
                ->back()
                ->withErrors($e->validator)
                ->withInput();
        }

        if ($request->hasFile('image')) {
            $this->syncPublicStorageLink();

            $path = $this->storeCardImage($request, (int) $eventCard->event_id);

            /*
             * Do NOT delete the old image immediately.
             *
             * Reason:
             * Some browsers/pages may still request the previous filename after saving.
             * Deleting the old file immediately causes 404/broken previews and looks like
             * the image is "corrupted". Keep old images until you run a cleanup command/job.
             */
            $eventCard->card_name = $path;
            $eventCard->image = $path;
        } else {
            /*
             * Important permanent fix:
             * When saving only positions/settings, preserve the existing permanent image path.
             */
            $existingImage = $this->normalizeStoragePath($eventCard->image ?: $eventCard->card_name);

            if ($existingImage) {
                $eventCard->card_name = $existingImage;
                $eventCard->image = $existingImage;
            }
        }

        $this->fillCardSettings($eventCard, $validated);

        $eventCard->save();

        Alert::success('Card Updated', 'Event card settings updated successfully.');

        return $this->successResponse($request, $eventCard);
    }

    public function destroy(string $id)
    {
        //
    }

    /**
     * Store uploaded card image permanently in the public disk.
     *
     * Returned path example:
     * event-card-samples/event-57/1781113988_abcd1234_card-name.jpg
     */
    private function storeCardImage(Request $request, int $eventId): string
    {
        $image = $request->file('image');

        $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = strtolower($image->getClientOriginalExtension() ?: $image->extension() ?: 'jpg');

        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        $safeName = Str::slug($originalName) ?: 'event-card';

        /*
         * Store each event's template image in its own folder.
         * This makes the system safe for many events:
         * Event 57 cannot overwrite or confuse Event 58.
         */
        $eventFolder = $this->eventCardImageFolder($eventId);

        /*
         * Use timestamp + random string to avoid browser/cache filename conflicts.
         */
        $fileName = time() . '_' . Str::random(10) . '_' . $safeName . '.' . $extension;

        $path = $image->storeAs($eventFolder, $fileName, 'public');
        $path = $this->normalizeStoragePath($path);

        if (! $path || ! Storage::disk('public')->exists($path)) {
            throw new \RuntimeException('Card image was not stored successfully.');
        }

        /*
         * Extra safety:
         * Make sure the saved file is not empty.
         */
        if ((int) Storage::disk('public')->size($path) <= 0) {
            Storage::disk('public')->delete($path);

            throw new \RuntimeException('Card image was saved as an empty file.');
        }

        return $path;
    }

    /**
     * Delete old card image if it exists.
     *
     * Kept for future cleanup/manual use, but update() does not call it immediately
     * because that can cause broken previews for cached pages.
     */
    private function deleteOldCardImage(?string $path): void
    {
        $path = $this->normalizeStoragePath($path);

        if (! $path || $this->isTemporaryPath($path)) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            return;
        }

        /*
         * Compatibility for old wrong folder names.
         */
        foreach ($this->legacyPathAlternatives($path) as $legacyPath) {
            if (Storage::disk('public')->exists($legacyPath)) {
                Storage::disk('public')->delete($legacyPath);
                return;
            }
        }
    }

    /**
     * Normalize DB storage paths so they always work with asset('storage/...').
     */
    private function normalizeStoragePath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $path = trim($path);
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#^/+#', '', $path);

        /*
         * Remove wrong prefixes if older code accidentally saved them.
         */
        $path = preg_replace('#^(storage/|public/|app/public/)+#', '', $path);

        /*
         * Compatibility for old typo folder.
         * Do not force old eventCardSamples into the new event-based folder here,
         * because old records may still physically exist in the old location.
         */
        $path = str_replace('eventsCardSamples/', 'eventCardSamples/', $path);

        if ($this->isTemporaryPath($path)) {
            return null;
        }

        return $path ?: null;
    }


    /**
     * Event-specific permanent folder for card template images.
     */
    private function eventCardImageFolder(int $eventId): string
    {
        return $this->cardImageBaseFolder . '/event-' . $eventId;
    }

    /**
     * Possible legacy path alternatives used by older versions.
     */
    private function legacyPathAlternatives(string $path): array
    {
        return array_values(array_unique([
            str_replace('eventsCardSamples/', 'eventCardSamples/', $path),
            str_replace('eventCardSamples/', 'eventsCardSamples/', $path),
        ]));
    }

    /**
     * Reject temporary paths that may disappear later.
     */
    private function isTemporaryPath(?string $path): bool
    {
        if (! $path) {
            return false;
        }

        return str_contains($path, 'livewire-tmp')
            || str_contains($path, '/tmp/')
            || str_starts_with($path, 'tmp/')
            || str_contains($path, 'temp/');
    }

    /**
     * Ensure Laravel public storage symlink exists where supported.
     */
    private function syncPublicStorageLink(): void
    {
        try {
            $publicStorage = public_path('storage');

            if (is_link($publicStorage) || File::exists($publicStorage)) {
                return;
            }

            if (function_exists('symlink')) {
                @symlink(storage_path('app/public'), $publicStorage);
            }
        } catch (\Throwable $e) {
            /*
             * Do not block saving card settings if symlink creation is not allowed.
             * Production servers can run: php artisan storage:link
             */
        }
    }

    /**
     * Save all card placeholder settings.
     *
     * IMPORTANT:
     * X/Y values are treated as PIXELS on the same design canvas used by preview/download.
     */
    private function fillCardSettings(EventCard $eventCard, array $data): void
    {
        // Guest name
        $eventCard->guestPositionX = $this->numberValue($data['guestNameX'] ?? null, 210);
        $eventCard->guestPositionY = $this->numberValue($data['guestNameY'] ?? null, 70);
        $eventCard->guest_name_font_size = $this->numberValue($data['guestNameFontSize'] ?? null, 12);
        $eventCard->guest_name_color = $data['guestNameColor'] ?? '#000000';

        // Card type
        $eventCard->cardTypePositionX = $this->numberValue($data['cardTypeX'] ?? null, 210);
        $eventCard->cardTypePositionY = $this->numberValue($data['cardTypeY'] ?? null, 395);
        $eventCard->guest_cardtype_font_size = $this->numberValue($data['guestCardtypeFontSize'] ?? null, 8);
        $eventCard->guest_cardtype_color = $data['guestCardtypeColor'] ?? '#000000';

        // Card Type background has been removed permanently.
        $eventCard->guest_cardtype_background_color = 'transparent';

        // QR code
        $eventCard->qrCodePositionX = $this->numberValue($data['qrCodeX'] ?? null, 315);
        $eventCard->qrCodePositionY = $this->numberValue($data['qrCodeY'] ?? null, 420);
        $eventCard->qrcode_cardtype_position = $data['qrcodePosition'] ?? 'custom';
    }

    private function numberValue(mixed $value, float $default): float
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return round((float) $value, 2);
    }

    /**
     * Supports normal browser saves and AJAX Save & Download.
     */
    private function successResponse(Request $request, ?EventCard $eventCard = null)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $imagePath = $eventCard
                ? $this->normalizeStoragePath($eventCard->image ?: $eventCard->card_name)
                : null;

            return response()->json([
                'success' => true,
                'message' => 'Card settings saved successfully for this event.',
                'image_path' => $imagePath,
                'image_url' => $imagePath
                    ? asset('storage/' . $imagePath) . '?v=' . optional($eventCard->updated_at)->timestamp
                    : null,
            ]);
        }

        return redirect()->back();
    }
}
