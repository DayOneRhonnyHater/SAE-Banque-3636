<?php
// Afficher toutes les erreurs pour le développement
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure les fichiers nécessaires
if (file_exists(__DIR__ . '/includes/config.php')) {
    require_once __DIR__ . '/includes/config.php';
} else {
    die("Le fichier de configuration est manquant. Veuillez créer le dossier 'includes' et y placer le fichier 'config.php'.");
}

// Vérifier l'existence des classes nécessaires
if (!file_exists(__DIR__ . '/classes/Transaction.php')) {
    die("Le fichier Transaction.php est manquant dans le dossier 'classes'.");
}

require_once __DIR__ . '/classes/Transaction.php';
require_once __DIR__ . '/classes/Database.php';

// Démarrer la session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérification de l'authentification
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
$userId = $_SESSION['user']['id'];

// Récupérer les filtres
$compteId = isset($_GET['compte_id']) ? (int)$_GET['compte_id'] : null;
$dateDebut = isset($_GET['date_debut']) ? $_GET['date_debut'] : date('Y-m-d', strtotime('-30 days'));
$dateFin = isset($_GET['date_fin']) ? $_GET['date_fin'] : date('Y-m-d');
$typeTransaction = isset($_GET['type']) ? $_GET['type'] : '';
$montantMin = isset($_GET['montant_min']) ? $_GET['montant_min'] : '';
$montantMax = isset($_GET['montant_max']) ? $_GET['montant_max'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Créer des données factices pour les tests si la classe Transaction n'est pas accessible
$transactions = [];
try {
    $transactionManager = Transaction::getInstance();
    $params = [
        'compte_id' => $compteId,
        'date_debut' => $dateDebut,
        'date_fin' => $dateFin,
        'type' => $typeTransaction,
        'montant_min' => $montantMin,
        'montant_max' => $montantMax,
        'search' => $search,
        'utilisateur_id' => $userId
    ];
    $transactions = $transactionManager->getTransactionsByFilters($params);
} catch (Exception $e) {
    // Créer des transactions de test en cas d'erreur
    for ($i = 1; $i <= 5; $i++) {
        $transactions[] = [
            'id' => $i,
            'compte_id' => 1,
            'type_transaction' => $i % 2 ? 'CREDIT' : 'DEBIT',
            'montant' => $i % 2 ? rand(1000, 5000) / 100 : -rand(1000, 5000) / 100,
            'date_transaction' => date('Y-m-d H:i:s', strtotime("-$i days")),
            'description' => "Transaction test #$i",
            'numero_compte' => 'FR76XXXXXXXX'
        ];
    }
}

// Configuration de la page
$pageTitle = 'Transactions';
$pageCss = 'transactions'; 
$viewFile = 'views/transactions.php';

// Inclure le layout
require_once __DIR__ . '/templates/layout.php';