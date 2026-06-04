<?php

$data = json_decode(file_get_contents('php://inputdata'), true);

if (!isset($data['entry']) || empty($data['entry'])) {
    http_response_code(400);
    echo "Invalid Request: No entry found";
    exit();
}

$changes = $data['entry'][0]['changes'] ?? null;

if (!$changes || empty($changes)) {
    http_response_code(400);
    echo "Invalid Request: No changes found";
    exit();
}

$statuses = $changes[0]['value']['statuses'][0] ?? null;
$messages = $changes[0]['value']['messages'][0] ?? null;

$servername = "localhost";
$username = "user";
$password = "password";
$dbname = "database";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($statuses) {
    $logFile = 'whatsapp_status_log.txt';

    $logMessage = "Message Status Update:\n";
    $logMessage .= "ID: " . $statuses['id'] . "\n";
    $logMessage .= "Status: " . $statuses['status'] . "\n";
    $logMessage .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    $logMessage .= "----------------------------------------\n";

    if (file_exists($logFile)) {
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    } else {
        file_put_contents($logFile, $logMessage);
    }

    $message_id = $statuses['id'];
    $delivery_status = $statuses['status'];
    $delivery_status_time = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("SELECT * FROM send_whatsapp_cards WHERE message_id = ?");
    $stmt->bind_param("s", $message_id);

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $deliveryStatusTimeFromDb = $row['delivery_status_time'];
        $deliveryStatusFromDb = $row['delivery_status'];

        if ($deliveryStatusFromDb === 'read') {
            continue;
        }

        $updateStmt = $conn->prepare("UPDATE send_whatsapp_cards SET delivery_status = ?, delivery_status_time = ? WHERE message_id = ?");
        $updateStmt->bind_param("sss", $delivery_status, $delivery_status_time, $message_id);
        $updateStmt->execute();
    }
}

if ($messages) {
    if (isset($messages['type']) && $messages['type'] == 'text') {
        $receivedMessageData = json_encode($messages, JSON_PRETTY_PRINT);
        $decodedMessageData = json_decode($receivedMessageData, true);

        $phoneNumber = $decodedMessageData['from'];
        $messageBody = $decodedMessageData['text']['body'];

        if (substr($phoneNumber, 0, 3) === '255') {
            $phoneNumber = substr($phoneNumber, 3);
        }

        $getIdStmt = $conn->prepare("
            SELECT id, reply_message
            FROM send_whatsapp_cards
            WHERE whatsapp_sender_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $getIdStmt->bind_param("s", $phoneNumber);
        $getIdStmt->execute();
        $getIdStmt->bind_result($lastId, $existingReply);
        $getIdStmt->fetch();
        $getIdStmt->close();

        if ($lastId) {
            if (!empty($existingReply)) {
                $newReply = $existingReply . "\nInvitee: " . $messageBody;
            } else {
                $newReply = $messageBody;
            }

            $updateStmt = $conn->prepare("
                UPDATE send_whatsapp_cards
                SET reply_message = ?
                WHERE id = ?
            ");
            $updateStmt->bind_param("si", $newReply, $lastId);
            $updateStmt->execute();
            $updateStmt->close();
        }
    } else {
        $receivedMessageData = json_encode($messages, JSON_PRETTY_PRINT);
        $decodedMessageData = json_decode($receivedMessageData, true);

        $message_id = $decodedMessageData['context']['id'];
        $reply_message = $decodedMessageData['button']['text'];

        if ($reply_message === 'VIEW LOCATION') {
            $checkStmt = $conn->prepare("SELECT event_id, whatsapp_sender_id FROM send_whatsapp_cards WHERE message_id = ?");
            $checkStmt->bind_param("s", $message_id);
            $checkStmt->execute();
            $checkStmt->bind_result($event_id, $whatsapp_sender_id);
            $found = $checkStmt->fetch();
            $checkStmt->close();

            if (!$found) {
                echo "Message ID not found";
                exit();
            }

            $eventStmt = $conn->prepare("SELECT venue_map_location_link FROM events WHERE id = ?");
            $eventStmt->bind_param("i", $event_id);
            $eventStmt->execute();
            $eventStmt->bind_result($venue_link);
            $eventFound = $eventStmt->fetch();
            $eventStmt->close();

            if (!$eventFound || empty($venue_link)) {
                echo "Location not found for event ID: $event_id";
                exit();
            }

            $access_token = 'EAANO9ZCIQlT4BP4CFCbZAQnjZC6EcZBNw9rdCbiLgImaCIybwF0Rr60qG9ZAnh1xs7ZAYz6RUuEfMCrMh7mCUh3DkNzJ1NtAhvLLBaIO9Dv2NtblywyYXSUZCrdKFNPMGFoNZAjNz8I3bcjsAjcWVw6WewULDUjZC7pPOouq7shJjJ6fgCGiJrgdq1RA0Mr7RIePo0gZDZD';
            $url = 'https://graph.facebook.com/v23.0/537191036142145/messages';

            $data = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $whatsapp_sender_id,
                'type' => 'text',
                'text' => ['body' => "Location: $venue_link"]
            ];

            $ch = curl_init($url);

            if ($ch === false) {
                dd('Error: Failed to initialize cURL');
            }

            curl_setopt_array($ch, [
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $access_token,
                    'Content-Type: application/json'
                ]
            ]);

            $response = curl_exec($ch);

        } else {

            $checkStmt = $conn->prepare("SELECT reply_message FROM send_whatsapp_cards WHERE message_id = ?");
            $checkStmt->bind_param("s", $message_id);
            $checkStmt->execute();
            $checkStmt->bind_result($existingReply);
            $checkStmt->fetch();
            $checkStmt->close();

            if (!empty($existingReply)) {
                $newReply = $existingReply . "\nInvitee: " . $reply_message;
            } else {
                $newReply = $reply_message;
            }

            $updateStmt = $conn->prepare("UPDATE send_whatsapp_cards SET reply_message = ? WHERE message_id = ?");
            $updateStmt->bind_param("ss", $newReply, $message_id);
            $updateStmt->execute();
        }
    }
}

http_response_code(200);
echo "Webhook processed successfully";
