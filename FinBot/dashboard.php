<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\dashboard.php
session_start();
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth_functions.php';

// Pour les tests, on commente cette vérification d'authentification
/*
try {
    checkAuth();
} catch (Exception $e) {
    header('Location: index.php');
    exit;
}
*/

// Récupérer les données utilisateur
$user = $_SESSION['user'] ?? null;

// Définir le message de bienvenue en fonction de l'heure
date_default_timezone_set('Europe/Paris'); // Définir le fuseau horaire
$hour = (int)date('H');

// Définir $hours et $minutes avant de les utiliser
$hours = $hour;
$minutes = (int)date('i');

// Message de bienvenue en fonction de l'heure
$welcomeMessage = 'Bonjour';
if ($hour < 12) {
    $welcomeMessage = 'Bonjour';
} elseif ($hour < 18) {
    $welcomeMessage = 'Bon après-midi';
} else {
    $welcomeMessage = 'Bonsoir';
}

// Récupérer les prénom et nom pour le message de bienvenue
$userName = '';
if ($user) {
    $userName = $user['prenom'] . ' ' . $user['nom'];
}

// Configurer les variables pour le layout
$pageTitle = 'Tableau de bord';
$pageCss = 'dashboard';
$viewFile = 'views/dashboard.php';

// Pour les besoins de l'exemple, simuler un utilisateur connecté si la session n'existe pas


// Inclure le layout principal
include __DIR__ . '/templates/layout.php';