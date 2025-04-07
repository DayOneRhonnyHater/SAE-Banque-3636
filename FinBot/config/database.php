<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\config\database.php

/**
 * Configuration de la base de données
 */

// Paramètres de connexion à la base de données principale
return [
    'default' => [
        'driver'    => 'mysql',
        'host'      => 'localhost',
        'database'  => 'finbot',
        'username'  => 'root',
        'password'  => '',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
        'options'   => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ],
    ],
    
    // Configuration pour les tests
    'testing' => [
        'driver'    => 'mysql',
        'host'      => 'localhost',
        'database'  => 'finbot_test',
        'username'  => 'root',
        'password'  => '',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
        'options'   => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ],
    ],
    
    // Configuration pour le développement
    'development' => [
        'driver'    => 'mysql',
        'host'      => 'localhost',
        'database'  => 'finbot_dev',
        'username'  => 'root',
        'password'  => '',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
        'options'   => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ],
    ],
    
    // Configuration pour la production
    'production' => [
        'driver'    => 'mysql',
        'host'      => 'localhost',
        'database'  => 'finbot_prod',
        'username'  => 'finbot_user',
        'password'  => 'strong_password',  // À changer en production
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
        'options'   => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ],
    ],
];