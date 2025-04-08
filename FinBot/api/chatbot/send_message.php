<?php
// FinBot/api/chatbot/send_message.php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../classes/Chatbot.php';

// Vérifier si l'utilisateur est authentifié
require_once __DIR__ . '/../../includes/auth_functions.php';
try {
    checkAuth();
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Authentification requise']);
    exit;
}

// Récupérer les données JSON du corps de la requête
$data = json_decode(file_get_contents('php://input'), true);
$message = $data['message'] ?? '';

if (empty($message)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Message requis']);
    exit;
}

// Contexte utilisateur pour personnaliser les réponses
$userContext = [];

// Si l'utilisateur est connecté, ajouter des informations contextuelles
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../../classes/User.php';
    require_once __DIR__ . '/../../classes/Compte.php';
    
    $user = new User();
    $userInfo = $user->getUserById($_SESSION['user_id']);
    
    $compte = new Compte();
    $accounts = $compte->getAccountsByUserId($_SESSION['user_id']);
    
    $userContext = [
        'nom' => $userInfo['nom'] ?? '',
        'prenom' => $userInfo['prenom'] ?? '',
        'nombre_comptes' => count($accounts),
        'types_comptes' => array_column($accounts, 'type')
    ];
}

// Initialiser le chatbot et envoyer le message
$chatbot = new Chatbot();
$response = $chatbot->sendMessage($message, $userContext);

// Renvoyer la réponse au format JSON
header('Content-Type: application/json');
echo json_encode(['response' => $response]);
