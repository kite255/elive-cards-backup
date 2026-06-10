<?php

namespace App\Http\Controllers;

use App\Models\BulkSMS;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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

    public function sendsinglemessage(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'phone' => 'required|string',
            'sender_id' => 'nullable|string|max:50',
        ]);

        $batch_id = $this->generateBatchId();

        $phone = $this->formatTanzaniaPhone($validated['phone']);
        $message = $validated['message'];
        $senderId = $validated['sender_id'] ?? config('services.elive_sms.sender_id', 'elive card');

        if (! $phone) {
            Alert::error('Invalid Phone', 'Please enter a valid phone number.');
            return redirect()->back()->withInput();
        }

        $smsResult = $this->sendSmsToApi($phone, $message, $senderId);

        $singlemessage = new BulkSMS();
        $singlemessage->user_id = Auth::id();
        $singlemessage->phone = $phone;
        $singlemessage->message = $message;
        $singlemessage->sender_id = $senderId;
        $singlemessage->request_id = $smsResult['message_id'] ?? 'unknown';
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

    public function sendbatchmessage(Request $request)
    {
        $validated = $request->validate([
            'excel_file' => 'required|mimes:xlsx,csv,xls',
            'message' => 'required|string',
            'sender_id' => 'nullable|string|max:50',
        ]);

        $batch_id = $this->generateBatchId();
        $senderId = $validated['sender_id'] ?? config('services.elive_sms.sender_id', 'elive card');

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

                $smsResult = $this->sendSmsToApi($phone, $message, $senderId);

                $singlemessage = new BulkSMS();
                $singlemessage->user_id = Auth::id();
                $singlemessage->phone = $phone;
                $singlemessage->message = $message;
                $singlemessage->sender_id = $senderId;
                $singlemessage->request_id = $smsResult['message_id'] ?? 'unknown';
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
        } catch (\Exception $e) {
            Log::error('Batch SMS Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            Alert::error('Error', 'Failed to send batch SMS. Check Laravel logs.');
            return redirect()->back()->withInput();
        }
    }

    private function sendSmsToApi(string $phone, string $message, string $senderId): array
    {
        $url = config('services.elive_sms.url');
        $apiKey = config('services.elive_sms.api_key');
        $apiSecret = config('services.elive_sms.api_secret');

        if (! $url || ! $apiKey || ! $apiSecret) {
            Log::error('SMS API credentials missing', [
                'url_exists' => ! empty($url),
                'api_key_exists' => ! empty($apiKey),
                'api_secret_exists' => ! empty($apiSecret),
            ]);

            return [
                'success' => false,
                'message' => 'SMS API credentials are missing.',
                'message_id' => null,
            ];
        }

        $payload = [
            'senderId' => $senderId,
            'messageType' => 'text',
            'message' => $message,
            'contacts' => $phone,
            'deliveryReportUrl' => config('services.elive_sms.delivery_report_url', url('/sms/delivery-callback')),
        ];

        try {
            Log::info('SMS sending started', [
                'phone' => $phone,
                'senderId' => $senderId,
                'payload' => $payload,
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
            ])
                ->timeout(30)
                ->post($url, $payload);

            $responseBody = $response->json();

            Log::info('SMS API response', [
                'status' => $response->status(),
                'body' => $responseBody,
                'raw_body' => $response->body(),
            ]);

            $messageId = $responseBody['data']['shootId'] ?? null;

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'message' => $responseBody['message'] ?? $response->body() ?? 'SMS API request failed.',
                    'message_id' => $messageId,
                ];
            }

            return [
                'success' => true,
                'message' => $responseBody['message'] ?? 'SMS sent successfully.',
                'message_id' => $messageId,
            ];
        } catch (\Exception $e) {
            Log::error('SMS API exception', [
                'phone' => $phone,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'message_id' => null,
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
        switch (strtolower($extension)) {
            case 'xlsx':
                return \Maatwebsite\Excel\Excel::XLSX;
            case 'xls':
                return \Maatwebsite\Excel\Excel::XLS;
            case 'csv':
                return \Maatwebsite\Excel\Excel::CSV;
            default:
                return \Maatwebsite\Excel\Excel::XLSX;
        }
    }
}