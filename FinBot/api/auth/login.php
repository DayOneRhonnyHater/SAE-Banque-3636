<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../classes/Database.php';

// Define DEBUG constant if not already defined
if (!defined('DEBUG')) {
    define('DEBUG', false); // Set to true for debugging
}

// Configuration des logs détaillés
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/../../logs/auth.log');

// Créer le dossier logs si nécessaire
if (!file_exists(__DIR__.'/../../logs')) {
    mkdir(__DIR__.'/../../logs', 0777, true);
}

try {
    // Log des données reçues (à retirer en production)
    error_log("Données reçues : " . file_get_contents('php://input'));

    // Vérification de la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée', 405);
    }

    // Récupération et validation des données
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Données JSON invalides: ' . json_last_error_msg(), 400);
    }

    // Log des données décodées
    error_log("Données décodées : " . print_r($data, true));

    // Validation des champs requis
    if (empty($data['email']) || empty($data['password'])) {
        throw new Exception('Email et mot de passe requis', 400);
    }

    $db = Database::getInstance();
    
    // Recherche de l'utilisateur
    $user = $db->selectOne(
        "SELECT * FROM utilisateurs WHERE email = ?",
        [$data['email']]
    );

    if (!$user) {
        throw new Exception('Identifiants invalides', 401);
    }

    // Vérification du mot de passe
    if (!password_verify($data['password'], $user['mot_de_passe'])) {
        throw new Exception('Identifiants invalides', 401);
    }

    // Mise à jour de la dernière connexion
    $db->update('utilisateurs', 
        ['derniere_connexion' => date('Y-m-d H:i:s')],
        'id = ?',
        [$user['id']]
    );

    // Création de la session
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $user['id'],
        'email' => $user['email'],
        'nom' => $user['nom'],
        'prenom' => $user['prenom'],
        'deux_facteurs' => $user['deux_facteurs'],
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'created_at' => time()
    ];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Réponse JSON au lieu d'afficher la session
    echo json_encode([
        'success' => true,
        'message' => 'Connexion réussie',
        'redirect' => 'dashboard.php'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données',
        'debug' => defined('DEBUG') && DEBUG ? $e->getMessage() : null
    ]);
} catch (Exception $e) {
    error_log("Erreur générale : " . $e->getMessage() . "\nTrace : " . $e->getTraceAsString());
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}