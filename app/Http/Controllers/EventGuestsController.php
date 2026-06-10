<?php

namespace App\Http\Controllers;

use App\Imports\eventGuestsImport;
use App\Jobs\CreateCard;
use App\Jobs\CreateQrcode;
use App\Models\Event;
use App\Models\EventCard;
use App\Models\EventGuest;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use RealRashid\SweetAlert\Facades\Alert;

class EventGuestsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store guests from Excel file and generate cards automatically.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate(
                [
                    'guestExcelFile' => 'required|file|mimes:xlsx,xls,csv',
                    'event_id' => 'required',
                ],
                [
                    'guestExcelFile.required' => 'Please choose an Excel file.',
                    'guestExcelFile.file' => 'The uploaded file is invalid.',
                    'guestExcelFile.mimes' => 'The file must be xlsx, xls, or csv.',
                    'event_id.required' => 'Event is required.',
                ]
            );

            $guestExcelData = $request->file('guestExcelFile');
            $userId = Auth::id();
            $eventId = $this->resolveId($validated['event_id']);

            if (! $eventId) {
                Alert::error('Error', 'Invalid event ID.');

                return redirect()->back();
            }

            Excel::import(new eventGuestsImport($userId, $eventId), $guestExcelData);

            $this->generateCardsForEvent((int) $eventId);

            Alert::success(
                'Excel file uploaded successfully',
                'Invitees uploaded. QR codes and cards generation has started.'
            );

            return redirect()->back();
        } catch (\Illuminate\Validation\ValidationException $e) {
            Alert::error('Validation Error', $e->validator->errors()->first());

            return redirect()
                ->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Throwable $e) {
            Log::error('Excel guest upload failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            Alert::error('Error', 'Make sure the Excel file is in the correct format.');

            return redirect()->back();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show guest edit form.
     */
    public function edit(string $id)
    {
        $guestId = $this->resolveId($id);

        if (! $guestId) {
            Alert::error('Error', 'Invalid guest ID.');

            return redirect()->back();
        }

        $guest = EventGuest::findOrFail($guestId);

        return view('venecardDashboard.eventCard.layoutSections.editguest', compact('guest'));
    }

    /**
     * Update guest details and regenerate card.
     */
    public function update(Request $request, string $id)
    {
        $guestId = $this->resolveId($id);

        if (! $guestId) {
            Alert::error('Error', 'Invalid guest ID.');

            return redirect()->back();
        }

        $validated = $request->validate(
            [
                'guest_name' => 'required|string|max:255',
                'guest_phone' => 'required|numeric|digits:10',
                'card_type' => 'required|string|max:255',
                'group_size' => 'required_if:card_type,GROUP|nullable|numeric|min:1|max:100',
                'note' => 'nullable|string|max:1000',
            ],
            [
                'guest_name.required' => 'Guest name is required.',
                'guest_phone.required' => 'Guest phone is required.',
                'guest_phone.numeric' => 'Guest phone must contain numbers only.',
                'guest_phone.digits' => 'Guest phone must be exactly 10 digits.',
                'card_type.required' => 'Card type is required.',
                'group_size.required_if' => 'Group size is required for group card.',
                'group_size.numeric' => 'Group size must be a number.',
                'group_size.min' => 'Group size must be at least 1.',
                'group_size.max' => 'Group size must not be more than 100.',
                'note.max' => 'Note must not exceed 1000 characters.',
            ]
        );

        try {
            $guest = EventGuest::findOrFail($guestId);

            $guest->guest_name = $validated['guest_name'];
            $guest->guest_phone = $this->normalizePhone($validated['guest_phone']);
            $guest->card_type = $this->formatCardType(
                $validated['card_type'],
                $validated['group_size'] ?? null
            );
            $guest->note = $validated['note'] ?? null;
            $guest->save();

            $this->generateCardsForEvent((int) $guest->event_id);

            Alert::success(
                $guest->guest_name,
                'has been updated successfully. Card regeneration has started.'
            );

            return redirect()->route('events.show', encrypt($guest->event_id));
        } catch (\Throwable $e) {
            Log::error('Guest update failed', [
                'guest_id' => $guestId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            Alert::error('Error', 'Guest was not updated. Please check the logs.');

            return redirect()->back()->withInput();
        }
    }

    /**
     * Delete guest.
     */
    public function destroy(string $id)
    {
        $guestId = $this->resolveId($id);

        if (! $guestId) {
            Alert::error('Error', 'Invalid guest ID.');

            return redirect()->back();
        }

        try {
            $guest = EventGuest::findOrFail($guestId);
            $eventId = $guest->event_id;
            $guestName = $guest->guest_name;

            $guest->delete();

            Alert::success($guestName, 'has been deleted successfully.');

            return redirect()->route('events.show', encrypt($eventId));
        } catch (\Throwable $e) {
            Log::error('Guest delete failed', [
                'guest_id' => $guestId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            Alert::error('Error', 'Guest was not deleted. Please check the logs.');

            return redirect()->back();
        }
    }

    /**
     * Add one guest manually and generate card automatically.
     */
    public function addSingleGuest(Request $request, $eventId)
    {
        try {
            $validated = $request->validate(
                [
                    'guestName' => 'required|string|max:255',
                    'guestPhone' => 'required|numeric|digits:10',
                    'cardType' => 'required|string|max:50',
                    'groupSize' => 'required_if:cardType,GROUP|nullable|numeric|min:1|max:100',
                    'note' => 'nullable|string|max:1000',
                ],
                [
                    'guestName.required' => 'Guest name is required.',
                    'guestPhone.required' => 'Guest phone is required.',
                    'guestPhone.numeric' => 'Guest phone must contain numbers only.',
                    'guestPhone.digits' => 'Guest phone must be exactly 10 digits.',
                    'cardType.required' => 'Card type is required.',
                    'groupSize.required_if' => 'Group size is required for group card.',
                    'groupSize.numeric' => 'Group size must be a number.',
                    'groupSize.min' => 'Group size must be at least 1.',
                    'groupSize.max' => 'Group size must not be more than 100.',
                    'note.max' => 'Note must not exceed 1000 characters.',
                ]
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            Alert::error('Validation Error', $e->validator->errors()->first());

            return redirect()
                ->back()
                ->withErrors($e->validator)
                ->withInput();
        }

        try {
            $userId = Auth::id();
            $eventId = $this->resolveId($eventId);

            if (! $eventId) {
                Alert::error('Error', 'Invalid event ID.');

                return redirect()->back();
            }

            $event = Event::find($eventId);

            if (! $event) {
                Alert::error('Error', 'Event not found.');

                return redirect()->back();
            }

            $guest = new EventGuest();
            $guest->user_id = $userId;
            $guest->event_id = $eventId;
            $guest->guest_name = $validated['guestName'];
            $guest->guest_phone = $this->normalizePhone($validated['guestPhone']);
            $guest->card_type = $this->formatCardType(
                $validated['cardType'],
                $validated['groupSize'] ?? null
            );
            $guest->note = $validated['note'] ?? null;
            $guest->invitation_code = $this->generateUniqueInvitationCode((int) $eventId);
            $guest->save();

            $this->generateCardsForEvent((int) $eventId);

            Alert::success(
                $validated['guestName'],
                'has been added successfully. QR code and card generation has started.'
            );

            return redirect()->back();
        } catch (\Throwable $e) {
            Log::error('Manual guest add failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            Alert::error('Error', 'Guest was not added. Please check the logs.');

            return redirect()->back()->withInput();
        }
    }

    /**
     * Generate QR codes and cards for an event.
     *
     * CreateQrcode and CreateCard use EVENT ID, not guest ID.
     */
    private function generateCardsForEvent(int $eventId): void
    {
        $eventHasCard = EventCard::where('event_id', $eventId)->exists();

        if (! $eventHasCard) {
            Log::warning('Card generation skipped because this event has no card template.', [
                'event_id' => $eventId,
            ]);

            return;
        }

        try {
            CreateQrcode::dispatch($eventId);
            CreateCard::dispatch($eventId);
        } catch (\Throwable $e) {
            Log::error('QR/card generation failed', [
                'event_id' => $eventId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    /**
     * Accept encrypted IDs and plain numeric IDs safely.
     */
    private function resolveId($id): ?int
    {
        if (is_numeric($id)) {
            return (int) $id;
        }

        try {
            $decrypted = decrypt($id);

            return is_numeric($decrypted) ? (int) $decrypted : null;
        } catch (DecryptException $e) {
            Log::warning('Invalid encrypted ID received', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::warning('ID resolution failed', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Convert local number into stored format without leading zero.
     *
     * Example:
     * 0768461644 becomes 768461644
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone);

        return ltrim($phone, '0');
    }

    /**
     * Format the card type before saving.
     *
     * Example:
     * GROUP + 5 becomes WATU 5
     */
    private function formatCardType(string $cardType, $groupSize = null): string
    {
        $cardType = strtoupper(trim($cardType));

        if ($cardType === 'GROUP') {
            return 'WATU ' . ((int) $groupSize ?: 1);
        }

        return $cardType;
    }

    /**
     * Generate unique invitation code per event.
     */
    private function generateUniqueInvitationCode(int $eventId): int
    {
        do {
            $generatedCode = rand(100000, 999999);

            $exists = EventGuest::where('event_id', $eventId)
                ->where('invitation_code', $generatedCode)
                ->exists();
        } while ($exists);

        return $generatedCode;
    }
}
