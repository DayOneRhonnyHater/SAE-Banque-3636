<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../includes/auth_functions.php';

try {
    // Vérification de l'authentification
    if (!isset($_SESSION['user'])) {
        throw new Exception('Non autorisé', 401);
    }

    $db = Database::getInstance();
    $userId = $_SESSION['user']['id'];

    // Récupération du solde
    $compte = $db->selectOne(
        "SELECT solde FROM comptes WHERE utilisateur_id = ? LIMIT 1",
        [$userId]
    );

    // Récupération des dernières transactions
    $transactions = $db->select(
        "SELECT * FROM transactions 
         WHERE compte_id IN (SELECT id FROM comptes WHERE utilisateur_id = ?) 
         ORDER BY date_transaction DESC LIMIT 5",
        [$userId]
    );

    // Récupération des notifications
    $notifications = $db->select(
        "SELECT * FROM notifications 
         WHERE utilisateur_id = ? 
         ORDER BY date_creation DESC LIMIT 5",
        [$userId]
    );

    echo json_encode([
        'success' => true,
        'solde' => floatval($compte['solde'] ?? 0),
        'transactions' => $transactions,
        'notifications' => $notifications
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

