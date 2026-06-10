<?php

namespace App\Imports;

use App\Models\EventGuest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class eventGuestsImport implements ToCollection, WithHeadingRow
{
    protected $user_id;

    protected $event_id;

    public function __construct($user_id, $event_id)
    {
        $this->user_id = $user_id;
        $this->event_id = $event_id;
    }

    /**
     * Import guests from Excel.
     *
     * Accepted headings:
     * invitee | phone | cardtype | note
     * name    | phone | cardtype | note
     * guest_name | guest_phone | card_type | note
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                /*
                 * Accept different heading names.
                 */
                $guestName = $row['invitee']
                    ?? $row['name']
                    ?? $row['guest_name']
                    ?? null;

                $guestPhone = $row['phone']
                    ?? $row['guest_phone']
                    ?? null;

                $cardType = $row['cardtype']
                    ?? $row['card_type']
                    ?? $row['type']
                    ?? null;

                $note = $row['note'] ?? null;

                /*
                 * Skip empty rows.
                 */
                if (empty($guestName) && empty($guestPhone) && empty($cardType)) {
                    continue;
                }

                /*
                 * Skip rows with missing required data.
                 */
                if (empty($guestName) || empty($guestPhone) || empty($cardType)) {
                    Log::warning('Excel guest import skipped row because required data is missing.', [
                        'row_number' => $index + 2,
                        'row' => $row->toArray(),
                    ]);

                    continue;
                }

                /*
                 * Clean phone number.
                 *
                 * Examples:
                 * 0768461644    -> 768461644
                 * 768461644     -> 768461644
                 * 255768461644  -> 768461644
                 * +255768461644 -> 768461644
                 */
                $phone = preg_replace('/\D/', '', (string) $guestPhone);

                if (str_starts_with($phone, '255')) {
                    $phone = substr($phone, 3);
                }

                $phone = ltrim($phone, '0');

                /*
                 * Tanzania local mobile number should remain 9 digits.
                 */
                if (strlen($phone) !== 9) {
                    Log::warning('Excel guest import skipped row because phone number is invalid.', [
                        'row_number' => $index + 2,
                        'original_phone' => $guestPhone,
                        'cleaned_phone' => $phone,
                    ]);

                    continue;
                }

                $cardType = strtoupper(trim((string) $cardType));

                /*
                 * Generate unique invitation code per event.
                 */
                $invitationCode = $this->generateUniqueInvitationCode($this->event_id);

                EventGuest::create([
                    'guest_name' => trim((string) $guestName),
                    'guest_phone' => $phone,
                    'card_type' => $cardType,
                    'user_id' => $this->user_id,
                    'event_id' => $this->event_id,
                    'invitation_code' => $invitationCode,
                    'note' => $note,
                ]);
            } catch (\Throwable $e) {
                Log::error('Excel guest import failed for row.', [
                    'row_number' => $index + 2,
                    'row' => $row->toArray(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                continue;
            }
        }
    }

    /**
     * Generate a unique six-digit invitation code per event.
     */
    protected function generateUniqueInvitationCode($event_id): string
    {
        do {
            $invitationCode = str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (
            EventGuest::where('event_id', $event_id)
                ->where('invitation_code', $invitationCode)
                ->exists()
        );

        return $invitationCode;
    }
}