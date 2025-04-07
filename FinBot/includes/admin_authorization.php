<?php
/**
 * Fichier de gestion des autorisations administrateur (version simplifiée)
 */

// Démarrage de la session si elle n'est pas déjà active
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Pour les tests, on va simplement inclure le fichier de désactivation
require_once __DIR__ . '/disable_auth.php';

/**
 * Vérifie si l'utilisateur actuel est connecté en tant qu'administrateur
 * 
 * @return bool True si l'utilisateur est connecté en tant qu'administrateur, False sinon
 */
function is_admin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Vérifie l'accès administrateur et redirige si non autorisé
 * 
 * @param string $redirect_url URL de redirection en cas d'accès non autorisé
 * @return void
 */
function require_admin_access($redirect_url = '../index.php') {
    if (!is_admin()) {
        // Définir un message d'erreur
        $_SESSION['error_message'] = "Accès refusé. Vous devez être administrateur pour accéder à cette page.";
        
        // Rediriger vers la page spécifiée
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Connecte simplement l'utilisateur en tant qu'administrateur
 * AVERTISSEMENT: Cette fonction ne vérifie aucune information d'authentification
 * À utiliser uniquement en environnement de développement
 * 
 * @param int $user_id Identifiant de l'utilisateur à connecter en tant qu'admin
 * @return void
 */
function admin_login($user_id = 1) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['is_admin'] = true;
    $_SESSION['username'] = 'Admin';
}

/**
 * Déconnecte l'utilisateur administrateur
 * 
 * @return void
 */
function admin_logout() {
    unset($_SESSION['user_id']);
    unset($_SESSION['is_admin']);
    unset($_SESSION['username']);
    
    // Rediriger vers la page d'accueil
    header("Location: ../index.php");
    exit();
}
?>