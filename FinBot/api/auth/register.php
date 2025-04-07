<?php
header('Content-Type: application/json');
// Remplacer init.php par les inclusions nécessaires
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../classes/Database.php';

// Configuration des logs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/../../logs/auth.log');

// Créer le dossier logs si nécessaire
if (!file_exists(__DIR__.'/../../logs')) {
    mkdir(__DIR__.'/../../logs', 0777, true);
}

try {
    // Log des données reçues
    $rawData = file_get_contents('php://input');
    error_log("Données reçues: " . $rawData);
    
    $data = json_decode($rawData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Données JSON invalides: ' . json_last_error_msg());
    }

    // Validation des données
    $required = ['email', 'password', 'nom', 'prenom'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Le champ {$field} est obligatoire");
        }
    }

    // Connexion à la base de données
    $db = Database::getInstance();
    $db->getConnection()->beginTransaction();

    try {
        // Vérification si l'email existe déjà
        $existingUser = $db->selectOne(
            "SELECT id FROM utilisateurs WHERE email = ?",
            [$data['email']]
        );

        if ($existingUser) {
            throw new Exception("Cet email est déjà utilisé", 409);
        }

        // Création de l'utilisateur
        $userId = $db->insert('utilisateurs', [
            'email' => $data['email'],
            'mot_de_passe' => password_hash($data['password'], PASSWORD_DEFAULT),
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'telephone' => $data['telephone'] ?? null,
            'date_creation' => date('Y-m-d H:i:s')
        ]);

        error_log("Utilisateur créé avec l'ID: " . $userId);

        // Création du compte bancaire par défaut
        $numeroCompte = 'FR76' . str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
        
        $compteId = $db->insert('comptes', [
            'utilisateur_id' => $userId,
            'type_compte_id' => 'COURANT', // Modifié pour correspondre à la nouvelle structure de la DB
            'numero_compte' => $numeroCompte,
            'solde' => 0.00,
            'date_creation' => date('Y-m-d H:i:s')
        ]);

        error_log("Compte bancaire créé avec l'ID: " . $compteId);

        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Compte créé avec succès',
            'redirect' => '../views/auth/login.php' // Mis à jour pour la nouvelle structure
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Erreur transaction: " . $e->getMessage());
        throw $e;
    }

} catch (Exception $e) {
    error_log("Erreur inscription: " . $e->getMessage());
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => defined('DEBUG') && DEBUG ? [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ] : null
    ]);
}