<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

try {
    // Vérification de la session
    if (!isset($_SESSION['user'])) {
        throw new Exception('Non autorisé', 401);
    }

    $db = Database::getInstance();
    
    // Récupération des transactions de l'utilisateur
    $transactions = $db->select(
        "SELECT t.*, c.numero_compte 
         FROM transactions t 
         JOIN comptes c ON t.compte_id = c.id 
         WHERE c.utilisateur_id = ? 
         ORDER BY t.date_transaction DESC",
        [$_SESSION['user']['id']]
    );

    // Formater les montants pour l'affichage
    foreach ($transactions as &$transaction) {
        $transaction['montant_formate'] = number_format($transaction['montant'], 2, ',', ' ') . ' €';
        $transaction['date_formate'] = date('d/m/Y H:i', strtotime($transaction['date_transaction']));
    }

    echo json_encode([
        'success' => true,
        'transactions' => $transactions
    ]);

} catch (Exception $e) {
    error_log("Erreur liste transactions: " . $e->getMessage());
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}