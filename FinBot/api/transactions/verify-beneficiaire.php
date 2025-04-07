<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Si c'est une requête OPTIONS, arrêter ici
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['email'])) {
        throw new Exception('Email requis');
    }

    $db = Database::getInstance();
    
    // Récupération de l'utilisateur et ses comptes
    $beneficiaire = $db->selectOne(
        "SELECT id, nom, prenom, email FROM utilisateurs WHERE email = ?",
        [$data['email']]
    );

    if (!$beneficiaire) {
        throw new Exception('Utilisateur non trouvé');
    }

    // Récupération des comptes du bénéficiaire
    $comptes = $db->select(
        "SELECT id, type_compte_id AS type_compte, numero_compte FROM comptes WHERE utilisateur_id = ?",
        [$beneficiaire['id']]
    );

    echo json_encode([
        'success' => true,
        'beneficiaire' => [
            'id' => $beneficiaire['id'],
            'nom' => $beneficiaire['nom'],
            'prenom' => $beneficiaire['prenom'],
            'comptes' => $comptes
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}