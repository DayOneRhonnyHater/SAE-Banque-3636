<?php
require_once __DIR__ . '/../classes/User.php';

/**
 * Vérifie si un utilisateur est connecté
 * 
 * @throws Exception Si l'utilisateur n'est pas connecté
 */
function checkAuth() {
    if (!isset($_SESSION['user'])) {
        throw new Exception("Vous devez être connecté pour accéder à cette page.");
    }
}

/**
 * Enregistre un nouvel utilisateur
 */
function register($nom, $prenom, $email, $password, $password_confirm) {
    // Validation des données
    if (empty($nom) || empty($prenom) || empty($email) || empty($password) || empty($password_confirm)) {
        throw new Exception('Tous les champs sont obligatoires');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Format d\'email invalide');
    }
    
    if ($password !== $password_confirm) {
        throw new Exception('Les mots de passe ne correspondent pas');
    }
    
    if (strlen($password) < 8) {
        throw new Exception('Le mot de passe doit contenir au moins 8 caractères');
    }
    
    try {
        // Connexion à la base de données
        $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Vérifier si l'email existe déjà dans la table utilisateurs
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM utilisateurs WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Cette adresse email est déjà utilisée');
        }
        
        // Hachage du mot de passe
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Créer l'utilisateur dans la table utilisateurs
        // Changement de created_at en date_creation selon la structure de la base de données
        $stmt = $pdo->prepare('INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, statut, date_creation) 
                              VALUES (?, ?, ?, ?, "CLIENT", "ACTIF", NOW())');
        
        if ($stmt->execute([$nom, $prenom, $email, $hashed_password])) {
            // Récupérer l'ID du nouvel utilisateur
            $userId = $pdo->lastInsertId();
            
            // Créer un compte courant par défaut dans la table comptes
            // Supprimer le champ 'nom' qui n'existe pas dans la table
            $stmt = $pdo->prepare('INSERT INTO comptes (utilisateur_id, numero_compte, type_compte_id, solde, date_creation) 
                                  VALUES (?, ?, "COURANT", 50.00, NOW())');
            
            // Solution 1: Utiliser mt_rand() avec des valeurs plus petites
            $numeroCompte = 'FR76' . str_pad(mt_rand(1000000, 9999999), 10, '0', STR_PAD_LEFT) . str_pad($userId, 10, '0', STR_PAD_LEFT);
            
            $stmt->execute([$userId, $numeroCompte]);
            
            // Ajouter une transaction pour enregistrer le dépôt initial
            $stmt = $pdo->prepare('INSERT INTO transactions (compte_id, type_transaction, montant, date_transaction, description, categorie) 
                                   VALUES (LAST_INSERT_ID(), "CREDIT", 50.00, NOW(), "Dépôt initial de bienvenue", "Divers")');
            $stmt->execute();
            
            return true;
        } else {
            throw new Exception('Erreur lors de la création du compte');
        }
    } catch (PDOException $e) {
        // En mode développement, montrer le message d'erreur réel
        if (DEBUG) {
            throw new Exception('Erreur de base de données: ' . $e->getMessage());
        } else {
            throw new Exception('Une erreur s\'est produite lors de l\'inscription');
        }
    }
}

/**
 * Fonction de connexion d'un utilisateur
 * 
 * @param string $email Email de l'utilisateur
 * @param string $password Mot de passe
 * @return array Les informations de l'utilisateur si la connexion a réussi
 * @throws Exception En cas d'erreur lors de la connexion
 */
function login($email, $password) {
    $db = Database::getInstance();
    $user = $db->selectOne("SELECT id, nom, prenom, email, mot_de_passe, role, statut FROM utilisateurs WHERE email = ?", [$email]);
    
    if (!$user) {
        throw new Exception("Utilisateur non trouvé");
    }
    
    if ($user['statut'] != 'ACTIF') {
        throw new Exception("Ce compte est désactivé ou bloqué");
    }
    
    if (!password_verify($password, $user['mot_de_passe'])) {
        throw new Exception("Mot de passe incorrect");
    }
    
    // Stockez le rôle dans la session
    $_SESSION['user'] = [
        'id' => $user['id'],
        'nom' => $user['nom'],
        'prenom' => $user['prenom'],
        'email' => $user['email'],
        'role' => $user['role']  // Assurez-vous que cette ligne existe
    ];
    
    return $user;
}

/**
 * Déconnecte l'utilisateur
 */
function logout() {
    // Supprimer les données de session
    unset($_SESSION['user']);
    
    // Détruire complètement la session
    session_destroy();
}