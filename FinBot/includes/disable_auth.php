<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\includes\disable_auth.php

/**
 * Ce fichier remplace toutes les vérifications d'authentification pour les tests
 * ATTENTION : Ne pas utiliser en production !
 */

// Vérifier si la fonction existe déjà avant de la déclarer
if (!function_exists('require_admin_access')) {
    function require_admin_access() {
        // Logique de la fonction
        return true;
    }
}

// Fonction de remplacement pour require_auth
function require_auth() {
    // Ne fait rien, permet l'accès à tous
    return true;
}

// Fonction de remplacement pour check_role
function check_role($roles) {
    // Ne fait rien, permet l'accès à tous
    return true;
}