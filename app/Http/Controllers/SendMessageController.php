<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Bryceandy\Beem\Facades\Beem;
use App\Models\BulkSMS;
use App\Models\VendorBalance;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use RealRashid\SweetAlert\Facades\Alert;

class SendMessageController extends Controller
{
    public function index()
    {
        $messages = BulkSMS::where('user_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();
        return view('venecardDashboard.message.sms.index', compact('messages'));
    }

    public function sendsinglemessage(Request $request)
    {

        $request->validate([
            'message' => 'required',
            'phone' => 'required|string',
           
        ]);

        // create a random batch id
        $batch_id = substr(str_shuffle('ABCDEF'), 0, 3) . substr(str_shuffle('123456789'), 0, 7);
       
       
              //   sending sms card section
$url = "https://message.elive.co.tz/api/v1/vendor/message/send";
$data = array(
	"senderId" => "elive card",
	"messageType" => "text",
	"message" => $request->message,
    "contacts" => "255" . ltrim($request->phone, '0'),
	"deliveryReportUrl" => "https://your-server.com/delivery-callback"
);

$headers = array(
	"Content-Type: application/json",
	"api_key: elive card",
	"api_secret: FwoVF9fxHt8rJ1hhgprB"
);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);

curl_close($ch);

$response_data = json_decode($response, true); 

$messageId = $response_data['data']['shootId'] ?? null;


        $singlemessage = new BulkSMS();
        $singlemessage->user_id = Auth::user()->id;
        $singlemessage->phone = ltrim($request->phone, '0');
        $singlemessage->message = $request->message;
        $singlemessage->sender_id = $request->sender_id;
        $singlemessage->request_id = $messageId ?? 'unknown';
        $singlemessage->sent_status = 'sent';
        $singlemessage->batch_id = $batch_id;
        $singlemessage->save();

        Alert::success('Successfully Sent', 'Batch Number: ' . $batch_id);
        return redirect()->back();
    }

    public function sendbatchmessage(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,csv,xls',
            'message' => 'required|string',
           
        ]);

        // create a random batch id
        $batch_id = substr(str_shuffle('ABCDEF'), 0, 3) . substr(str_shuffle('123456789'), 0, 7);

        try {
            // Get the file extension to determine the reader type
            $fileExtension = $request->file('excel_file')->getClientOriginalExtension();

            // Import the Excel file and get all rows with explicit reader type
            $data = Excel::toArray([], $request->file('excel_file'), null, $this->getReaderType($fileExtension));

            // Skip the first row (header) and get only the data rows
            $dataRows = array_slice($data[0], 1);

            // dd only the data rows (excluding header)
            // dd($dataRows);

            foreach ($dataRows as $row) {
               

                // message string replace with #column_name#
                $message = $request->message;
                // Replace placeholders with column values
                // #A# = column 0, #B# = column 1, #C# = column 2, etc.
                for ($i = 0; $i < count($row); $i++) {
                    $columnLetter = chr(65 + $i); // Convert 0 to 'A', 1 to 'B', etc.
                    $placeholder = '#' . $columnLetter . '#';
                    $value = $row[$i] ?? '';
                    $message = str_replace($placeholder, $value, $message);
                }

                   //   sending sms card section
$url = "https://message.elive.co.tz/api/v1/vendor/message/send";
$data = array(
	"senderId" => "elive card",
	"messageType" => "text",
	"message" => $message,
	"contacts" => "255" . $row[0],
	"deliveryReportUrl" => "https://your-server.com/delivery-callback"
);

$headers = array(
	"Content-Type: application/json",
	"api_key: elive card",
	"api_secret: FwoVF9fxHt8rJ1hhgprB"
);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);

curl_close($ch);

$response_data = json_decode($response, true); 

$messageId = $response_data['data']['shootId'] ?? null;


               $singlemessage = new BulkSMS();
                $singlemessage->user_id = Auth::user()->id;
                $singlemessage->phone = $row[0];
                $singlemessage->message = $message;
                $singlemessage->sender_id = $request->sender_id;
                $singlemessage->request_id = $messageId ?? 'unknown';
                $singlemessage->sent_status = 'sent';
                $singlemessage->batch_id = $batch_id;
                $singlemessage->save();
            }

            Alert::success('Successfully Sent', 'Batch Number: ' . $batch_id);
            return redirect()->back();
        } catch (\Exception $e) {
            dd('Error: ' . $e->getMessage(), 'File: ' . $e->getFile(), 'Line: ' . $e->getLine());
        }
    }

    //telk sendbatchmessage class the excel imported extension
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
                return \Maatwebsite\Excel\Excel::XLSX; // default to XLSX
        }
    }
}
