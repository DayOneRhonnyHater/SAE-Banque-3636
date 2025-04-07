<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

try {
    // Vérification de session plus robuste
    if (!isset($_SESSION['user']['id'])) {
        throw new Exception('Authentification requise', 401);
    }

    $input = file_get_contents('php://input');
    if ($input === false) {
        throw new Exception('Erreur de lecture des données', 400);
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Données JSON invalides', 400);
    }

    // Validation des données
    $required = ['email_beneficiaire', 'montant'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Le champ $field est requis", 400);
        }
    }

    $montant = floatval($data['montant']);
    if ($montant <= 0) {
        throw new Exception('Le montant doit être positif', 400);
    }

    $db = Database::getInstance();
    $db->beginTransaction();

    try {
        // 1. Vérification compte source
        $compteSource = $db->selectOne(
            "SELECT id, solde FROM comptes WHERE utilisateur_id = ? AND type_compte_id = 'COURANT' LIMIT 1",
            [$_SESSION['user']['id']]
        );
        if (!$compteSource) {
            throw new Exception('Compte courant introuvable', 404);
        }

        // 2. Vérification solde suffisant
        if ($compteSource['solde'] < $montant) {
            throw new Exception('Solde insuffisant', 400);
        }

        // 3. Vérification bénéficiaire
        $beneficiaire = $db->selectOne(
            "SELECT id FROM utilisateurs WHERE email = ?",
            [$data['email_beneficiaire']]
        );
        if (!$beneficiaire) {
            throw new Exception('Bénéficiaire introuvable', 404);
        }

        // 4. Vérification compte bénéficiaire
        $compteBeneficiaire = $db->selectOne(
            "SELECT id, solde FROM comptes WHERE utilisateur_id = ? AND type_compte_id = 'COURANT' LIMIT 1",
            [$beneficiaire['id']]
        );
        if (!$compteBeneficiaire) {
            throw new Exception('Compte bénéficiaire introuvable', 404);
        }

        // 5. Opérations de débit/crédit
        $dateNow = date('Y-m-d H:i:s');
        
        // Débit compte source
        $db->update('comptes',
            ['solde' => $compteSource['solde'] - $montant],
            'id = ?',
            [$compteSource['id']]
        );

        // Crédit compte bénéficiaire
        $db->update('comptes',
            ['solde' => $compteBeneficiaire['solde'] + $montant],
            'id = ?',
            [$compteBeneficiaire['id']]
        );

        // Enregistrement transactions
        $description = $data['description'] ?? 'Virement';
        
        $db->insert('transactions', [
            'compte_id' => $compteSource['id'],
            'type_transaction' => 'VIREMENT',
            'montant' => -$montant,
            'description' => $description,
            'date_transaction' => $dateNow,
            'compte_destinataire' => $compteBeneficiaire['id']
        ]);

        $db->insert('transactions', [
            'compte_id' => $compteBeneficiaire['id'],
            'type_transaction' => 'VIREMENT',
            'montant' => $montant,
            'description' => $description,
            'date_transaction' => $dateNow,
            'compte_destinataire' => $compteSource['id']
        ]);

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Virement effectué',
            'new_balance' => $compteSource['solde'] - $montant
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    error_log("PDO Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données',
        'error_code' => 'DB_ERROR'
    ]);
} catch (Exception $e) {
    error_log("Transaction Error: " . $e->getMessage());
    http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'TRANSACTION_ERROR'
    ]);
}