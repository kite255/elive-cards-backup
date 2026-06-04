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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

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
    /*
    |--------------------------------------------------------------------------
    | Profile routes
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Event categories routes
    |--------------------------------------------------------------------------
    */
    Route::get('eventcategories/trashed', [EventcategoriesController::class, 'trashed'])
        ->name('eventcategories.trashed');

    Route::delete('eventcategories/{id}/force-delete', [EventcategoriesController::class, 'forceDelete'])
        ->name('eventcategories.force-delete');

    Route::get('eventcategories/{id}/restore', [EventcategoriesController::class, 'restore'])
        ->name('eventcategories.restore');

    Route::resource('eventcategories', EventcategoriesController::class);

    /*
    |--------------------------------------------------------------------------
    | Staff routes
    |--------------------------------------------------------------------------
    */
    Route::resource('venecardstaff', VenecardstaffController::class);

    /*
    |--------------------------------------------------------------------------
    | Events routes
    |--------------------------------------------------------------------------
    */
    Route::resource('events', EventsController::class);

    /*
    |--------------------------------------------------------------------------
    | Card and SMS card routes
    |--------------------------------------------------------------------------
    */
    Route::resource('card', EventCardController::class);
    Route::resource('smscard', EventSMSCardController::class);

    /*
    |--------------------------------------------------------------------------
    | Card generation and sample card download routes
    |--------------------------------------------------------------------------
    */
    Route::get('create-card', [App\Http\Controllers\CreateCardController::class, 'index'])
        ->name('create-card');

    Route::get('download-sample-card/{id}', [App\Http\Controllers\CreateCardController::class, 'downloadSampleCard'])
        ->name('samplecard.download');

    /*
    |--------------------------------------------------------------------------
    | Excel sample download routes
    |--------------------------------------------------------------------------
    */
    Route::get('/download-Excel', [ExelSampleDownloadController::class, 'downloadExcelSample'])
        ->name('downloadexcelsample');

    Route::get('/bulk-sms-download-Excel', [ExelSampleDownloadController::class, 'bulksmsdownloadExcelSample'])
        ->name('bulksms.downloadexcelsample');

    /*
    |--------------------------------------------------------------------------
    | Guest routes
    |--------------------------------------------------------------------------
    */
    Route::resource('guests', EventGuestsController::class);

    /*
    |--------------------------------------------------------------------------
    | Add single guest routes
    |--------------------------------------------------------------------------
    */
    Route::get('add-guest/{eventId}', function ($eventId) {
        return redirect()
            ->route('guests.index')
            ->with('error', 'Please add a guest using the guest form, not by opening the save URL directly.');
    })->name('guests.addsingleguest.get');

    Route::post('add-guest/{eventId}', [EventGuestsController::class, 'addSingleGuest'])
        ->name('guests.addsingleguest');

    /*
    |--------------------------------------------------------------------------
    | QR code and PDF card creation routes
    |--------------------------------------------------------------------------
    */
    Route::get('/createqrcode/{event_id}', [EventGuestsController::class, 'createqrcode'])
        ->name('guests.createqrcode');

    Route::get('/createpdfcard/{event_id}', [EventGuestsController::class, 'createpdfcard'])
        ->name('guests.createpdfcard');

    /*
    |--------------------------------------------------------------------------
    | Sending routes
    |--------------------------------------------------------------------------
    */
    Route::post('sendcard/{eventId}', [SendCardController::class, 'sendWhatsappCard'])
        ->name('sendcard.sendwhatsappcard');

    Route::post('send-card/{eventId}', [SendCardController::class, 'sendMessageCard'])
        ->name('sendcard.sendMessagecard');

    Route::post('send-reminder-sms/{eventId}', [SendCardController::class, 'sendremindersms'])
        ->name('sendcard.sendremindersms');

    Route::post('send-thank-you-sms/{eventId}', [SendCardController::class, 'sendthankyousms'])
        ->name('sendcard.sendthankyousms');

    /*
    |--------------------------------------------------------------------------
    | General message routes
    |--------------------------------------------------------------------------
    */
    Route::get('sendmessage', [SendMessageController::class, 'index'])
        ->name('sendmessage.index');

    Route::post('send-single-message', [SendMessageController::class, 'sendsinglemessage'])
        ->name('sendmessage.sendsinglemessage');

    Route::post('send-batch-message', [SendMessageController::class, 'sendbatchMessage'])
        ->name('sendmessage.sendbatchmessage');

    /*
    |--------------------------------------------------------------------------
    | SMS balance test route
    |--------------------------------------------------------------------------
    */
    Route::get('sms-balance-test', function () {
        return response()->json(getEliveSmsBalance());
    })->name('sms.balance.test');

    /*
    |--------------------------------------------------------------------------
    | Resend card routes
    |--------------------------------------------------------------------------
    */
    Route::get('resend-whatsapp-card/{guestId}', [ResendcardController::class, 'resendwhatsappcard'])
        ->name('resendcard.resendwhatsappcard');

    Route::get('resend-sms-card/{guestId}', [ResendcardController::class, 'resendSMScard'])
        ->name('resendcard.resendsmscard');

    /*
    |--------------------------------------------------------------------------
    | SMS setup routes
    |--------------------------------------------------------------------------
    */
    Route::resource('smsreminder', EventSMSReminderController::class);
    Route::resource('smswelcoming', EventSMSWelcomingController::class);
    Route::resource('smsthankyou', EventSMSThankyouController::class);

    /*
    |--------------------------------------------------------------------------
    | Contribution caption routes
    |--------------------------------------------------------------------------
    */
    Route::resource('contribution-caption', ContributionCardCaptionController::class);
});

/*
|--------------------------------------------------------------------------
| WhatsApp webhook
|--------------------------------------------------------------------------
*/
Route::get('/whatsapp-webhook', [WhatsappWebhookController::class, 'handleWebhook'])
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