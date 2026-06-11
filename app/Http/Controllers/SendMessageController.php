<?php

namespace App\Http\Controllers;

use App\Models\BulkSMS;
use App\Services\EliveSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use RealRashid\SweetAlert\Facades\Alert;

class SendMessageController extends Controller
{
    public function index()
    {
        $messages = BulkSMS::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('venecardDashboard.message.sms.index', compact('messages'));
    }

    public function sendsinglemessage(Request $request, EliveSmsService $smsService)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'phone' => 'required|string',
            'sender_id' => 'nullable|string|max:50',
        ]);

        $batch_id = $this->generateBatchId();

        $phone = $this->formatTanzaniaPhone($validated['phone']);
        $message = $validated['message'];
        $senderId = $validated['sender_id'] ?? config('services.elive_sms.sender_id', 'eLive Card');

        if (! $phone) {
            Alert::error('Invalid Phone', 'Please enter a valid phone number.');
            return redirect()->back()->withInput();
        }

        $smsResult = $this->sendSmsToApi($smsService, $phone, $message, $senderId);

        $singlemessage = new BulkSMS();
        $singlemessage->user_id = Auth::id();
        $singlemessage->phone = $phone;
        $singlemessage->message = $message;
        $singlemessage->sender_id = $senderId;

        /*
        |--------------------------------------------------------------------------
        | Important
        |--------------------------------------------------------------------------
        | Save provider shootId in request_id.
        | Delivery report uses:
        | GET /message/deliver/{shootId}
        */
        $singlemessage->request_id = $smsResult['shoot_id'] ?? $smsResult['message_id'] ?? 'unknown';

        $singlemessage->sent_status = $smsResult['success'] ? 'sent' : 'failed';
        $singlemessage->batch_id = $batch_id;
        $singlemessage->save();

        if (! $smsResult['success']) {
            Alert::error('SMS Not Sent', $smsResult['message'] ?? 'SMS provider rejected the request.');
            return redirect()->back()->withInput();
        }

        Alert::success('Successfully Sent', 'Batch Number: ' . $batch_id);
        return redirect()->back();
    }

    public function sendbatchmessage(Request $request, EliveSmsService $smsService)
    {
        $validated = $request->validate([
            'excel_file' => 'required|mimes:xlsx,csv,xls',
            'message' => 'required|string',
            'sender_id' => 'nullable|string|max:50',
        ]);

        $batch_id = $this->generateBatchId();
        $senderId = $validated['sender_id'] ?? config('services.elive_sms.sender_id', 'eLive Card');

        try {
            $fileExtension = $request->file('excel_file')->getClientOriginalExtension();

            $data = Excel::toArray(
                [],
                $request->file('excel_file'),
                null,
                $this->getReaderType($fileExtension)
            );

            $dataRows = array_slice($data[0] ?? [], 1);

            $sentCount = 0;
            $failedCount = 0;

            foreach ($dataRows as $row) {
                $rawPhone = $row[0] ?? null;
                $phone = $this->formatTanzaniaPhone($rawPhone);

                if (! $phone) {
                    $failedCount++;
                    continue;
                }

                $message = $this->replaceMessagePlaceholders($validated['message'], $row);

                $smsResult = $this->sendSmsToApi($smsService, $phone, $message, $senderId);

                $singlemessage = new BulkSMS();
                $singlemessage->user_id = Auth::id();
                $singlemessage->phone = $phone;
                $singlemessage->message = $message;
                $singlemessage->sender_id = $senderId;
                $singlemessage->request_id = $smsResult['shoot_id'] ?? $smsResult['message_id'] ?? 'unknown';
                $singlemessage->sent_status = $smsResult['success'] ? 'sent' : 'failed';
                $singlemessage->batch_id = $batch_id;
                $singlemessage->save();

                if ($smsResult['success']) {
                    $sentCount++;
                } else {
                    $failedCount++;
                }
            }

            Alert::success(
                'Batch SMS Completed',
                'Batch Number: ' . $batch_id . '. Sent: ' . $sentCount . ', Failed: ' . $failedCount
            );

            return redirect()->back();
        } catch (\Throwable $e) {
            Log::error('Batch SMS Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            Alert::error('Error', 'Failed to send batch SMS. Check Laravel logs.');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Manual delivery report refresh.
     *
     * This checks sent SMS records that have request_id/shootId and updates sent_status.
     * It works without adding new database columns.
     */
    public function refreshDeliveryReports(Request $request, EliveSmsService $smsService)
    {
        $query = BulkSMS::where('user_id', Auth::id())
            ->whereNotNull('request_id')
            ->where('request_id', '!=', 'unknown');

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }

        $messages = $query->latest()->limit(100)->get();

        $updated = 0;
        $failed = 0;

        foreach ($messages as $message) {
            try {
                $result = $smsService->firstDeliveryStatus($message->request_id);

                $deliveryStatus = strtolower((string) ($result['delivery_status'] ?? ''));

                if ($deliveryStatus === '') {
                    continue;
                }

                $message->sent_status = match (true) {
                    str_contains($deliveryStatus, 'deliver') => 'delivered',
                    str_contains($deliveryStatus, 'fail') => 'failed',
                    str_contains($deliveryStatus, 'reject') => 'failed',
                    str_contains($deliveryStatus, 'undeliver') => 'failed',
                    str_contains($deliveryStatus, 'pending') => 'pending',
                    default => $deliveryStatus,
                };

                $message->save();

                $updated++;
            } catch (\Throwable $e) {
                $failed++;

                Log::error('SMS delivery report refresh failed.', [
                    'bulk_sms_id' => $message->id,
                    'request_id' => $message->request_id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        Alert::success(
            'Delivery Report Updated',
            'Updated: ' . $updated . ', Failed: ' . $failed
        );

        return redirect()->back();
    }

    private function sendSmsToApi(
        EliveSmsService $smsService,
        string $phone,
        string $message,
        string $senderId
    ): array {
        try {
            Log::info('SMS sending started', [
                'phone' => $phone,
                'sender_id' => $senderId,
            ]);

            $response = $smsService->send($phone, $message, $senderId);

            $body = $response['body'] ?? [];

            $success = (bool) ($response['successful'] ?? false);
            $shootId = $response['shoot_id'] ?? null;

            Log::info('SMS API normalized response', [
                'phone' => $phone,
                'success' => $success,
                'shoot_id' => $shootId,
                'body' => $body,
            ]);

            return [
                'success' => $success,
                'message' => is_array($body)
                    ? ($body['message'] ?? 'SMS request completed.')
                    : 'SMS request completed.',
                'message_id' => $shootId,
                'shoot_id' => $shootId,
                'body' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('SMS API exception', [
                'phone' => $phone,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'message_id' => null,
                'shoot_id' => null,
                'body' => null,
            ];
        }
    }

    private function formatTanzaniaPhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $phone = preg_replace('/\D/', '', $phone);

        if (! $phone) {
            return null;
        }

        if (str_starts_with($phone, '255')) {
            return $phone;
        }

        if (str_starts_with($phone, '0')) {
            return '255' . substr($phone, 1);
        }

        if (str_starts_with($phone, '7') || str_starts_with($phone, '6')) {
            return '255' . $phone;
        }

        return null;
    }

    private function replaceMessagePlaceholders(string $message, array $row): string
    {
        for ($i = 0; $i < count($row); $i++) {
            $columnLetter = chr(65 + $i);
            $placeholder = '#' . $columnLetter . '#';
            $value = $row[$i] ?? '';

            $message = str_replace($placeholder, $value, $message);
        }

        return $message;
    }

    private function generateBatchId(): string
    {
        return substr(str_shuffle('ABCDEF'), 0, 3) . substr(str_shuffle('123456789'), 0, 7);
    }

    private function getReaderType($extension)
    {
        return match (strtolower((string) $extension)) {
            'xlsx' => \Maatwebsite\Excel\Excel::XLSX,
            'xls' => \Maatwebsite\Excel\Excel::XLS,
            'csv' => \Maatwebsite\Excel\Excel::CSV,
            default => \Maatwebsite\Excel\Excel::XLSX,
        };
    }
}