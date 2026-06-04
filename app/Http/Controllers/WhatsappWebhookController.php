<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;



class WhatsappWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
      
        date_default_timezone_set('Africa/Nairobi'); // EAT (Dar es Salaam timezone)
        
        $WHATSAPP_ACCESS_TOKEN = 'EAATFZAqZCN4WsBO7PUgv1wIX8ZBcu2lLIQ9EMAvCBDzoiOhRdXPwiIEodP2nI8XU6QoKCIBjuaqALxPRX45eRXilKKoCGoU0QCd5kWHuXcNZBPzubZAfG9PN8MqczWkjo8OnCZAHcMq2ANqk8My80WYkfywaMfoiWaq8PyFmUkBVCJnwRNRsectikVQph9ZBkIZA9gZDZD';
        $WEBHOOK_VERIFY_TOKEN = 'my-verify-token';
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['hub_mode'])) {
            // Handle webhook verification
            $mode = $_GET['hub_mode'];
            $token = $_GET['hub_verify_token'];
            $challenge = $_GET['hub_challenge'];
        
            if ($mode === 'subscribe' && $token === $WEBHOOK_VERIFY_TOKEN) {
                echo $challenge;
            } else {
                http_response_code(403);
            }
            exit;
        }
        
        
        



// Read incoming JSON from the POST request
$data = json_decode(file_get_contents('php://input'), true);

// Check if the entry is provided
if (!isset($data['entry']) || empty($data['entry'])) {
    http_response_code(400);
    echo "Invalid Request";
    exit();
}

// Extract changes from the entry
$changes = $data['entry'][0]['changes'] ?? null;

if (!$changes || empty($changes)) {
    http_response_code(400);
    echo "Invalid Request";
    exit();
}

// Check if message statuses or messages are provided
$statuses = $changes[0]['value']['statuses'][0] ?? null;
$messages = $changes[0]['value']['messages'][0] ?? null;

// Log the message status if statuses are present
if ($statuses) {
    // Log the message status to Laravel log
    Log::channel('single')->info('MESSAGE STATUS UPDATE', [
        'id' => $statuses['id'],
        'status' => $statuses['status'],
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
}

// Respond back to acknowledge the request
http_response_code(200);
echo "Webhook processed";



        




        
        
        
        
        
        
    }
}
