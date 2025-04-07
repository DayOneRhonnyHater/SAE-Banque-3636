<?php
// Fichier d'initialisation centralisé

// Inclusion des fichiers de fonctions et de configuration
require_once __DIR__ . '/functions.php';

// Configuration de base
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Paramètres de la session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Chemins des fichiers principaux
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('CLASSES_PATH', BASE_PATH . '/classes');
define('TEMPLATES_PATH', BASE_PATH . '/templates');

// Inclure les fichiers de configuration
require_once CONFIG_PATH . '/app.php';
require_once CONFIG_PATH . '/database.php';

// Inclure les fonctions utilitaires
require_once INCLUDES_PATH . '/functions.php';

// Inclure les classes principales
foreach (glob(CLASSES_PATH . '/*.php') as $filename) {
    require_once $filename;
}

// Fonction pour journaliser les erreurs
function log_error($message) {
    // Dans un fichier log ou en base de données
    error_log(date('Y-m-d H:i:s') . ' - ' . $message . "\n", 3, BASE_PATH . '/logs/errors.log');
}

// Charger les fichiers d'authentification
require_once INCLUDES_PATH . '/admin_authorization.php';

// Inclure les fonctions spécifiques
require_once __DIR__ . '/bank_functions.php';