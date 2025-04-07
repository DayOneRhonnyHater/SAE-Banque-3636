<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\register.php
session_start();
require_once __DIR__ . '/config/app.php';

// Débogage pour voir si nous atteignons ce script et l'état de la session
error_log('Tentative d\'accès à register.php');
error_log('État de la session: ' . print_r($_SESSION, true));

// Pour les tests, commentez cette condition comme dans index.php
/*
if (isset($_SESSION['user'])) {
    error_log('Utilisateur déjà connecté, redirection vers dashboard.php');
    
    // Option 1: Déconnectez l'utilisateur avant d'afficher le formulaire d'inscription
    // unset($_SESSION['user']);
    // session_regenerate_id(true);
    // error_log('Session utilisateur supprimée pour permettre l'inscription');
    
    // Option 2: Gardez la redirection (comportement actuel)
    header('Location: dashboard.php');
    exit;
}
*/

// Traitement du formulaire d'inscription
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/includes/auth_functions.php';
    
    // Récupérer et valider les données du formulaire
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    try {
        // Tentative d'inscription
        if (register($nom, $prenom, $email, $password, $password_confirm)) {
            $success = 'Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Configurer les variables pour le layout
$pageTitle = 'Inscription';
$pageCss = 'register';
$viewFile = '/views/register.php';
$hideNavigation = true;
$bodyClass = 'bg-gradient-primary';

// Inclure le layout principal
include __DIR__ . '/templates/layout.php';