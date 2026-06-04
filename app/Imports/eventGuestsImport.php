<?php
 
namespace App\Imports;

use App\Models\EventGuest;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class eventGuestsImport implements ToCollection, WithHeadingRow
{
    protected $user_id;
    protected $event_id;

    // Constructor to accept user_id and event_id
    public function __construct($user_id, $event_id)
    {
        $this->user_id = $user_id;
        $this->event_id = $event_id;
    }

    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Generate a unique invitation code for each guest
            $invitation_code = $this->generateUniqueInvitationCode($this->event_id);

            // Create a new guest record with a unique invitation code
            EventGuest::create([
                'guest_name' => $row['name'],
                'guest_phone' => $row['phone'],
                'card_type' => $row['cardtype'],
                'user_id' => $this->user_id,  // Store the user_id
                'event_id' => $this->event_id,  // Store the event_id
                'invitation_code' => $invitation_code,  // Store the unique invitation_code
                'note' => $row['note'],
            ]);
        }
    }

    // Function to generate a unique six-digit invitation code
    protected function generateUniqueInvitationCode($event_id)
    {
        do {
            $invitation_code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (EventGuest::where('event_id', $event_id)->where('invitation_code', $invitation_code)->exists());

        return $invitation_code;
    }
}
