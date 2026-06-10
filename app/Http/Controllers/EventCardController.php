<?php

namespace App\Http\Controllers;

use App\Models\EventCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;

class EventCardController extends Controller
{
    /**
     * Storage folder for event card sample images.
     */
    private string $cardImageFolder = 'eventCardSamples';

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

        $eventCard = new EventCard();
        $eventCard->user_id = Auth::id();
        $eventCard->event_id = $validated['event_id'];

        if ($request->hasFile('image')) {
            $path = $this->storeCardImage($request);

            /*
             * Permanent image compatibility:
             * - card_name is used by old generation code
             * - image is used by the new preview/layout code
             */
            $eventCard->card_name = $path;
            $eventCard->image = $path;
        }

        $this->fillCardSettings($eventCard, $validated);

        $eventCard->save();

        Alert::success('Card Imported', 'Event card imported successfully.');

        return $this->successResponse($request);
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
            /*
             * Delete old image using either image or card_name.
             */
            $oldImage = $eventCard->image ?: $eventCard->card_name;

            $this->deleteOldCardImage($oldImage);

            $path = $this->storeCardImage($request);

            /*
             * Save new upload to both fields permanently.
             */
            $eventCard->card_name = $path;
            $eventCard->image = $path;
        } else {
            /*
             * Important permanent fix:
             * When saving positions/settings without uploading a new image,
             * do not allow image/card_name to become null.
             */
            $existingImage = $eventCard->image ?: $eventCard->card_name;

            if ($existingImage) {
                $eventCard->card_name = $existingImage;
                $eventCard->image = $existingImage;
            }
        }

        $this->fillCardSettings($eventCard, $validated);

        $eventCard->save();

        Alert::success('Card Updated', 'Event card updated successfully.');

        return $this->successResponse($request);
    }

    public function destroy(string $id)
    {
        //
    }

    /**
     * Store uploaded card image in public disk.
     */
    private function storeCardImage(Request $request): string
    {
        $image = $request->file('image');

        $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = strtolower($image->getClientOriginalExtension());

        $safeName = Str::slug($originalName) ?: 'event-card';
        $fileName = time() . '_' . $safeName . '.' . $extension;

        return $image->storeAs($this->cardImageFolder, $fileName, 'public');
    }

    /**
     * Delete old card image if it exists.
     */
    private function deleteOldCardImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        $path = ltrim(str_replace('storage/', '', $path), '/\\');

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            return;
        }

        /*
         * Compatibility for old wrong folder name:
         * eventsCardSamples → eventCardSamples
         */
        $correctedPath = str_replace('eventsCardSamples/', 'eventCardSamples/', $path);

        if (Storage::disk('public')->exists($correctedPath)) {
            Storage::disk('public')->delete($correctedPath);
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
    private function successResponse(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Card settings saved successfully.',
            ]);
        }

        return redirect()->back();
    }
}