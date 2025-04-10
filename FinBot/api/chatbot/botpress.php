<?php
header('Content-Type: application/json');
require_once __DIR__.'/../../includes/security.php';

$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';

$ch = curl_init('http://botpress:3000/api/v1/bots/finbot/converse');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'type' => 'text',
        'text' => $message,
        'userId' => session_id()
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer '.getenv('BOTPRESS_TOKEN')
    ]
]);

echo curl_exec($ch);
?>
