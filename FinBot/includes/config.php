<?php
// Configuration de base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'finbot');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuration de l'application
define('ROOT_PATH', dirname(__DIR__));
define('ROOT_URL', '/projetBUT/SAE.04/SAE-Banque/FinBot');

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

// Types de transactions valides (doivent correspondre à l'énumération dans la BDD)
define('TRANSACTION_TYPES', ['DEBIT', 'CREDIT', 'VIREMENT', 'INTERET']);

// Fonction d'autoload des classes
spl_autoload_register(function($className) {
    $classFile = ROOT_PATH . '/classes/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }
});