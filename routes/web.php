<?php

use App\Http\Controllers\ContributionCardCaptionController;
use App\Http\Controllers\DownloadCardController;
use App\Http\Controllers\EventCardController;
use App\Http\Controllers\EventcategoriesController;
use App\Http\Controllers\EventGuestTrackingController;
use App\Http\Controllers\EventGuestsController;
use App\Http\Controllers\EventsController;
use App\Http\Controllers\EventSMSCardController;
use App\Http\Controllers\EventSMSReminderController;
use App\Http\Controllers\EventSMSThankyouController;
use App\Http\Controllers\EventSMSWelcomingController;
use App\Http\Controllers\ExelSampleDownloadController;
use App\Http\Controllers\InviteesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResendcardController;
use App\Http\Controllers\ScancardController;
use App\Http\Controllers\SendCardController;
use App\Http\Controllers\SendMessageController;
use App\Http\Controllers\VenecardstaffController;
use App\Http\Controllers\WhatsappWebhookController;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\GuestPdf;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('auth.login');
});

/*
|--------------------------------------------------------------------------
| Public invitee private invitation link
|--------------------------------------------------------------------------
| QR opens this URL:
| /i/{invitation_code}
|
| Example:
| http://127.0.0.1:8010/i/200397
*/
Route::get('/i/{code}', function (string $code) {
    $code = trim($code);

    $guest = EventGuest::with(['event', 'qrcode'])
        ->where('invitation_code', $code)
        ->firstOrFail();

    $event = $guest->event;

    $guestPdf = GuestPdf::where('event_guests_id', $guest->id)
        ->latest()
        ->first();

    $cardUrl = null;

    if ($guestPdf && $guestPdf->pdf_name) {
        $cardPath = trim(str_replace('\\', '/', (string) $guestPdf->pdf_name));
        $cardPath = ltrim($cardPath, '/');

        if (str_starts_with($cardPath, 'storage/')) {
            $cardPath = substr($cardPath, strlen('storage/'));
        }

        if (str_starts_with($cardPath, 'public/')) {
            $cardPath = substr($cardPath, strlen('public/'));
        }

        if (
            Storage::disk('public')->exists($cardPath) &&
            Storage::disk('public')->size($cardPath) > 0
        ) {
            $cardUrl = asset('storage/' . $cardPath);
        }
    }

    return view('invitees.private-invitation', [
        'guest' => $guest,
        'event' => $event,
        'guestPdf' => $guestPdf,
        'cardUrl' => $cardUrl,
    ]);
})->name('invitees.private');

/*
|--------------------------------------------------------------------------
| Helper: Get SMS Balance
|--------------------------------------------------------------------------
*/
if (! function_exists('getEliveSmsBalance')) {
    function getEliveSmsBalance(): array
    {
        $baseUrl = rtrim(config('services.elive_sms.base_url'), '/');
        $balanceEndpoint = trim(env('ELIVE_SMS_BALANCE_ENDPOINT', 'message/balance'), '/');

        $apiKey = config('services.elive_sms.api_key');
        $apiSecret = config('services.elive_sms.secret_key');

        if (empty($apiKey) || empty($apiSecret)) {
            return [
                'successful' => false,
                'status' => 422,
                'message' => 'Missing ELIVE_SMS_API_KEY or ELIVE_SMS_SECRET_KEY in .env',
                'balance' => 'pending',
            ];
        }

        $url = $baseUrl . '/' . $balanceEndpoint;

        try {
            $response = Http::withHeaders([
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->get($url);

            $body = $response->json() ?? [];

            $balance =
                $body['data']['totalSms']
                ?? $body['data']['balance']
                ?? $body['data']['remaining_balance']
                ?? $body['data']['remainingBalance']
                ?? $body['totalSms']
                ?? $body['balance']
                ?? $body['remaining_balance']
                ?? $body['remainingBalance']
                ?? 'pending';

            return [
                'url_called' => $url,
                'successful' => $response->successful(),
                'status' => $response->status(),
                'body' => $body,
                'balance' => $balance,
            ];
        } catch (\Throwable $e) {
            return [
                'url_called' => $url,
                'successful' => false,
                'status' => 500,
                'message' => $e->getMessage(),
                'balance' => 'pending',
            ];
        }
    }
}

Route::get('/dashboard', function () {
    $stats = [
        'total_events' => Event::where('user_id', Auth::id())->count(),
        'total_cards' => EventGuest::where('user_id', Auth::id())->count(),
        'recent_events' => Event::with(['eventCategory', 'eventGuests'])
            ->where('user_id', Auth::id())
            ->latest()
            ->take(5)
            ->get(),
    ];

    $smsResult = getEliveSmsBalance();
    $message_balance = $smsResult['balance'] ?? 'pending';

    return view('venecardDashboard.dashboard', compact('stats', 'message_balance'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('eventcategories/trashed', [EventcategoriesController::class, 'trashed'])
        ->name('eventcategories.trashed');

    Route::delete('eventcategories/{id}/force-delete', [EventcategoriesController::class, 'forceDelete'])
        ->name('eventcategories.force-delete');

    Route::get('eventcategories/{id}/restore', [EventcategoriesController::class, 'restore'])
        ->name('eventcategories.restore');

    Route::resource('eventcategories', EventcategoriesController::class);

    Route::resource('venecardstaff', VenecardstaffController::class);

    Route::resource('events', EventsController::class);

    Route::resource('card', EventCardController::class);
    Route::resource('smscard', EventSMSCardController::class);

    Route::get('create-card', [App\Http\Controllers\CreateCardController::class, 'index'])
        ->name('create-card');

    Route::get('download-sample-card/{id}', [App\Http\Controllers\CreateCardController::class, 'downloadSampleCard'])
        ->name('samplecard.download');

    Route::get('/download-Excel', [ExelSampleDownloadController::class, 'downloadExcelSample'])
        ->name('downloadexcelsample');

    Route::get('/bulk-sms-download-Excel', [ExelSampleDownloadController::class, 'bulksmsdownloadExcelSample'])
        ->name('bulksms.downloadexcelsample');

    Route::resource('guests', EventGuestsController::class);

    Route::get('add-guest/{eventId}', function ($eventId) {
        return redirect()
            ->route('guests.index')
            ->with('error', 'Please add a guest using the guest form, not by opening the save URL directly.');
    })->name('guests.addsingleguest.get');

    Route::post('add-guest/{eventId}', [EventGuestsController::class, 'addSingleGuest'])
        ->name('guests.addsingleguest');

    Route::get('/createqrcode/{event_id}', [EventGuestsController::class, 'createqrcode'])
        ->name('guests.createqrcode');

    Route::get('/createpdfcard/{event_id}', [EventGuestsController::class, 'createpdfcard'])
        ->name('guests.createpdfcard');

    Route::post('sendcard/{eventId}', [SendCardController::class, 'sendWhatsappCard'])
        ->name('sendcard.sendwhatsappcard');

    Route::post('send-card/{eventId}', [SendCardController::class, 'sendMessageCard'])
        ->name('sendcard.sendMessagecard');

    Route::post('send-reminder-sms/{eventId}', [SendCardController::class, 'sendremindersms'])
        ->name('sendcard.sendremindersms');

    Route::post('send-thank-you-sms/{eventId}', [SendCardController::class, 'sendthankyousms'])
        ->name('sendcard.sendthankyousms');

    Route::get('sendmessage', [SendMessageController::class, 'index'])
        ->name('sendmessage.index');

    Route::post('send-single-message', [SendMessageController::class, 'sendsinglemessage'])
        ->name('sendmessage.sendsinglemessage');

    Route::post('send-batch-message', [SendMessageController::class, 'sendbatchMessage'])
        ->name('sendmessage.sendbatchmessage');

    Route::get('sms-balance-test', function () {
        return response()->json(getEliveSmsBalance());
    })->name('sms.balance.test');

    Route::get('resend-whatsapp-card/{guestId}', [ResendcardController::class, 'resendwhatsappcard'])
        ->name('resendcard.resendwhatsappcard');

    Route::get('resend-sms-card/{guestId}', [ResendcardController::class, 'resendSMScard'])
        ->name('resendcard.resendsmscard');

    Route::resource('smsreminder', EventSMSReminderController::class);
    Route::resource('smswelcoming', EventSMSWelcomingController::class);
    Route::resource('smsthankyou', EventSMSThankyouController::class);

    Route::resource('contribution-caption', ContributionCardCaptionController::class);
});

/*
|--------------------------------------------------------------------------
| WhatsApp webhook
|--------------------------------------------------------------------------
*/
Route::match(['get', 'post'], '/whatsapp-webhook', [WhatsappWebhookController::class, 'handleWebhook'])
    ->name('whatsapp-webhook');

/*
|--------------------------------------------------------------------------
| Scanner routes
|--------------------------------------------------------------------------
*/
Route::get('/scan-cards/{id}', [ScancardController::class, 'index'])
    ->name('scan-cards.index');

Route::get('/verify-card/{id}', [ScancardController::class, 'verifycard'])
    ->name('verifycard');

Route::get('/verify-invitee/{id}', [ScancardController::class, 'verifyinvitee'])
    ->name('verifyinvitee');

/*
|--------------------------------------------------------------------------
| Invitees routes
|--------------------------------------------------------------------------
*/
Route::get('/invitees-statistics/{id}', [InviteesController::class, 'inviteesStatistics'])
    ->name('invitees-statistics');

Route::get('/invitees-list/{id}', [InviteesController::class, 'inviteesList'])
    ->name('invitees-list');

/*
|--------------------------------------------------------------------------
| Event guest tracking routes
|--------------------------------------------------------------------------
*/
Route::get('/invitation-updates/{id}', [EventGuestTrackingController::class, 'index'])
    ->name('event-guest-tracking.index');

/*
|--------------------------------------------------------------------------
| Download invitee card
|--------------------------------------------------------------------------
*/
Route::get('invitee/download/event-card/{id}', [DownloadCardController::class, 'index'])
    ->name('downloadinviteecard');

/*
|--------------------------------------------------------------------------
| Storage link route
|--------------------------------------------------------------------------
*/
Route::get('/storage-link', function () {
    Artisan::call('storage:link');

    return 'Storage link created successfully!';
})->name('storage-link');

require __DIR__ . '/auth.php';