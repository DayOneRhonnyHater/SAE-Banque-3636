<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\accounts.php
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

// Configurer les variables pour le layout
$pageTitle = 'Mes Comptes';
$pageCss = 'accounts';
$viewFile = '/views/comptes.php';

// Inclure le layout principal
include __DIR__ . '/templates/layout.php';