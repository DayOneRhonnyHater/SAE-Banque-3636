<?php
session_start();
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

if (!isset($_SESSION['user'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Non autorisé');
}

try {
    // Remplacer la fonction getTransactions par une requête directe
    $db = Database::getInstance();
    $transactions = $db->select(
        "SELECT t.date_transaction, t.type_transaction as type, t.description, t.montant, c.numero_compte 
        FROM transactions t 
        JOIN comptes c ON t.compte_id = c.id 
        WHERE c.utilisateur_id = ? 
        ORDER BY t.date_transaction DESC",
        [$_SESSION['user']['id']]
    );
    
    // Paramètres du fichier CSV
    $filename = "transactions_" . date('Y-m-d_His') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Création du fichier CSV
    $output = fopen('php://output', 'w');
    
    // UTF-8 BOM pour Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // En-têtes
    fputcsv($output, [
        'Date',
        'Type',
        'Description',
        'Montant',
        'Numéro de compte'
    ], ';');
    
    // Données
    foreach ($transactions as $transaction) {
        fputcsv($output, [
            date('d/m/Y H:i', strtotime($transaction['date_transaction'])),
            $transaction['type'],
            $transaction['description'],
            number_format($transaction['montant'], 2, ',', ' ') . ' €',
            $transaction['numero_compte']
        ], ';');
    }
    
    fclose($output);

} catch (Exception $e) {
    error_log("Erreur export CSV: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    exit('Erreur lors de l\'export: ' . $e->getMessage());
}