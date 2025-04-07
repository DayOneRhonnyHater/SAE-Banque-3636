<?php
session_start();
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/classes/Compte.php';

// Vérifier si l'utilisateur est connecté
require_once __DIR__ . '/includes/auth_functions.php';
try {
    checkAuth();
} catch (Exception $e) {
    // Rediriger vers la page de connexion
    header('Location: index.php');
    exit;
}

// Récupérer l'identifiant de l'utilisateur
$userId = $_SESSION['user']['id'];

// Initialiser les variables
$message = '';
$error = '';

// Récupérer les comptes courants de l'utilisateur
$compteManager = Compte::getInstance();
$comptesCourants = array_filter(
    $compteManager->getComptesByUser($userId),
    function($compte) {
        return $compte['type_compte_id'] === 'COURANT';
    }
);

// Récupérer les types de comptes d'épargne disponibles
$typesEpargne = array_filter(
    $compteManager->getTypesComptes(),
    function($type) {
        return $type['id'] !== 'COURANT';
    }
);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'compte_epargne') {
        try {
            $type_compte = $_POST['type_compte'] ?? '';
            $depot_initial = floatval($_POST['depot_initial'] ?? 0);
            $compte_source_id = intval($_POST['compte_source'] ?? 0);
            
            // Vérifier si les données sont valides
            if (empty($type_compte) || $depot_initial < 10 || $compte_source_id <= 0) {
                throw new Exception('Veuillez remplir tous les champs correctement.');
            }
            
            // Créer le compte épargne
            $nouveauCompte = $compteManager->creerCompteEpargne($userId, $type_compte, $depot_initial, $compte_source_id);
            
            if ($nouveauCompte) {
                $message = "Votre compte épargne a été créé avec succès.";
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Configurer les variables pour le layout
$pageTitle = 'Créer un compte épargne';
$pageCss = 'accounts';
$viewFile = '/views/create_epargne.php';

// Inclure le layout principal
include __DIR__ . '/templates/layout.php';