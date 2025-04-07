<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\config\app.php

/**
 * Configuration générale de l'application
 */

// Informations de base de l'application
define('APP_NAME', 'FinBot');
define('APP_VERSION', '1.0.0');
define('ENVIRONMENT', 'development'); // 'development' ou 'production'

// Configurations spécifiques à l'environnement
if (ENVIRONMENT === 'development') {
    // Afficher toutes les erreurs en développement
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    ini_set('display_errors', 1);
} else {
    // Masquer les erreurs en production
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

// Locale pour formatage
setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr.UTF-8', 'fr_FR', 'fr');

// Chemins
define('BASE_PATH', realpath(__DIR__ . '/..'));
define('UPLOAD_PATH', BASE_PATH . '/uploads');

// Configuration des sessions - COMMENTÉ POUR LES TESTS
/*
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', ENVIRONMENT === 'production' ? 1 : 0);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 3600); // 1 heure
ini_set('session.use_strict_mode', 1);
session_name('FINBOT_SESSION');
*/

// Si une session n'est pas déjà démarrée, la démarrer
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// COMMENTEZ OU SUPPRIMEZ CE BLOC POUR QUE L'INSCRIPTION FONCTIONNE
/*
// POUR LES TESTS : Créer un utilisateur de test automatiquement
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => 1,
        'prenom' => 'Test',
        'nom' => 'Utilisateur',
        'email' => 'test@example.com',
        'role' => 'ADMINISTRATEUR', // Pour avoir tous les accès
        'actif' => 1
    ];
}
*/

// Autres configurations...
define('ADMIN_EMAIL', 'admin@finbot.fr');
define('SUPPORT_EMAIL', 'support@finbot.fr');

// URLs
define('BASE_URL', '/projetBUT/SAE.04/SAE-Banque/FinBot');
define('ADMIN_URL', BASE_URL . '/admin');

// Connexion à la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'finbot');  // Assurez-vous que cette base de données existe
define('DB_USER', 'root');    // Utilisateur MySQL/MariaDB (généralement 'root' pour WAMP)
define('DB_PASS', '');        // Mot de passe (généralement vide pour WAMP)

// Taux d'intérêt et frais
define('TAUX_LIVRET_A', 0.03); // 3%
define('TAUX_PEL', 0.02); // 2%

// Autres configurations
define('DEBUG', true);        // Activer/désactiver le mode débogage

// Configuration du système d'authentification
define('AUTH_SALT', 'finbot_salt_secret');  // Sel pour le hachage des mots de passe

// Activer l'affichage des erreurs en mode débogage
if (DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    error_reporting(0);
}