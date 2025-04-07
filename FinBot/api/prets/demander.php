<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

try {
    checkAuth();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['montant']) || !isset($data['duree']) || !isset($data['taux'])) {
        throw new Exception('Données manquantes');
    }

    $db = Database::getInstance();
    
    // Insertion de la demande dans la base de données
    $id = $db->insert('demandes_prets', [
        'utilisateur_id' => $_SESSION['user']['id'],
        'montant' => $data['montant'],
        'duree' => $data['duree'],
        'taux' => $data['taux'],
        'mensualite' => $data['mensualite'] ?? 0,
        'motif' => $data['motif'] ?? null,
        'statut' => 'EN_ATTENTE',
        'date_demande' => date('Y-m-d H:i:s')
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Votre demande de prêt a été enregistrée et sera examinée par nos conseillers.',
        'demande_id' => $id
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}