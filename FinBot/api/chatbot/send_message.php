<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../classes/Chatbot.php';

header('Content-Type: application/json');

try {
    // Authentification
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Authentification requise', 401);
    }

    // Validation des entrées
    $input = json_decode(file_get_contents('php://input'), true);
    $message = filter_var($input['message'] ?? '', FILTER_SANITIZE_STRING);
    
    if (empty($message) || strlen($message) > 500) {
        throw new Exception('Message invalide', 400);
    }

    // Contexte utilisateur
    $context = [
        'user_id' => $_SESSION['user_id'],
        'user_role' => $_SESSION['user']['role'] ?? 'client'
    ];

    // Appel au chatbot
    $chatbot = new Chatbot();
    $response = $chatbot->sendMessage($message, $context);

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}
?>
