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

// Vérification de l'authentification
session_start();
if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Non authentifié'
    ]);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Vérifier les données requises selon la structure de la table
    if (!isset($data['nom']) || !isset($data['numero_compte'])) {
        throw new Exception('Données incomplètes: nom et numéro de compte sont requis');
    }

    $userId = $_SESSION['user']['id'];
    $nom = $data['nom'];
    $numeroCompte = $data['numero_compte'];
    $banque = $data['banque'] ?? null;
    $description = $data['description'] ?? null;
    
    // Connexion directe à la base de données pour éviter les problèmes avec la classe Database
    $dsn = "mysql:host=localhost;dbname=finbot;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, 'root', '', $options);
    
    // Vérifier si le bénéficiaire existe déjà pour cet utilisateur
    $queryCheck = "SELECT id FROM beneficiaires WHERE utilisateur_id = ? AND numero_compte = ?";
    $stmtCheck = $pdo->prepare($queryCheck);
    $stmtCheck->execute([$userId, $numeroCompte]);
    $existingBeneficiaire = $stmtCheck->fetch();
    
    if ($existingBeneficiaire) {
        throw new Exception('Ce bénéficiaire existe déjà dans votre liste');
    }
    
    // Ajouter le bénéficiaire
    try {
        // Préparer la requête
        $query = "INSERT INTO beneficiaires 
                  (utilisateur_id, nom, numero_compte, banque, description, date_ajout) 
                  VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([
            $userId, 
            $nom, 
            $numeroCompte, 
            $banque, 
            $description
        ]);
        
        if (!$result) {
            throw new Exception('Erreur lors de l\'ajout du bénéficiaire');
        }
        
        // Récupérer l'ID du bénéficiaire inséré
        $newId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Bénéficiaire ajouté avec succès',
            'id' => $newId
        ]);
    } catch (PDOException $e) {
        // Journaliser l'erreur pour le débogage
        error_log('Erreur SQL dans add.php: ' . $e->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erreur technique lors de l\'ajout du bénéficiaire',
            'debug' => $e->getMessage()
        ]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}