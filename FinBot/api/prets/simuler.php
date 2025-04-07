<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../classes/Database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['montant']) || !isset($data['duree'])) {
        throw new Exception('Données manquantes');
    }

    $montant = floatval($data['montant']);
    $duree = intval($data['duree']);

    // Vérifications
    if ($montant < 1000 || $montant > 100000) {
        throw new Exception('Le montant doit être entre 1000€ et 100000€');
    }

    if (!in_array($duree, [12, 24, 36, 48, 60])) {
        throw new Exception('Durée invalide');
    }

    // Calcul du taux selon le montant et la durée
    $taux = 3.5; // Taux de base
    if ($montant > 50000) $taux += 0.5;
    if ($duree > 36) $taux += 0.3;

    // Calcul des mensualités
    $taux_mensuel = ($taux / 100) / 12;
    $mensualite = $montant * ($taux_mensuel * pow(1 + $taux_mensuel, $duree)) 
                  / (pow(1 + $taux_mensuel, $duree) - 1);

    $cout_total = $mensualite * $duree;

    echo json_encode([
        'mensualite' => round($mensualite, 2),
        'taux' => $taux,
        'cout_total' => round($cout_total, 2)
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['message' => $e->getMessage()]);
}