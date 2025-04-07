<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\index.php
session_start();
require_once __DIR__ . '/config/app.php';

// Pour les tests, on commente cette redirection
/*
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}
*/

// Ajouter un lien pour accéder directement au dashboard
echo '<div style="text-align: center; margin-top: 30px; background-color: #f8d7da; padding: 15px;">';
echo '<h1>Mode Test - Sécurité désactivée</h1>';
echo '<p>Vous pouvez accéder à toutes les pages sans connexion.</p>';
echo '<ul style="list-style: none; padding: 0;">';
echo '<li><a href="dashboard.php" style="display: inline-block; margin: 10px; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">Dashboard</a></li>';
echo '<li><a href="admin/index.php" style="display: inline-block; margin: 10px; padding: 10px 20px; background-color: #dc3545; color: white; text-decoration: none; border-radius: 5px;">Administration</a></li>';
echo '<li><a href="messages.php" style="display: inline-block; margin: 10px; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px;">Messages</a></li>';
echo '</ul>';
echo '</div>';

// Traitement du formulaire de connexion
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/includes/auth_functions.php';
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        // Tentative de connexion
        if (login($email, $password)) {
            // Redirection vers le dashboard en cas de succès
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Identifiants incorrects';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Configurer les variables pour le layout
$pageTitle = 'Connexion';
$pageCss = 'login';
$viewFile = '/views/login.php';
$hideNavigation = true;
$bodyClass = 'bg-gradient-primary';

// Inclure le layout principal
include __DIR__ . '/templates/layout.php';