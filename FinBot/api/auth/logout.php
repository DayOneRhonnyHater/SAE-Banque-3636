<?php
header('Content-Type: application/json');

// Initialisation session
session_start();

// Vérification si utilisateur est connecté
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

// Destruction complète de la session
$_SESSION = array();

// Suppression du cookie de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destruction session
session_destroy();

// Réponse JSON
echo json_encode(['success' => true, 'message' => 'Déconnexion réussie']);

// Redirection frontend (optionnelle)
// header('Location: ../../frontend/index.php?logout=success');