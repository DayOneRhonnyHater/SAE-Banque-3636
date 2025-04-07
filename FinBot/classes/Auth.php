<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\classes\Auth.php

require_once __DIR__ . '/../classes/Database.php';

class Auth {
    private static $instance = null;
    private $db;
    
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Tente d'authentifier un utilisateur
     * 
     * @param string $email Email de l'utilisateur
     * @param string $password Mot de passe
     * @return array|false Données utilisateur ou false si échec
     */
    public function login($email, $password) {
        // Récupérer l'utilisateur
        $user = $this->db->selectOne(
            "SELECT id, email, mot_de_passe, nom, prenom, role, statut FROM utilisateurs WHERE email = ?",
            [$email]
        );
        
        // Vérifier si l'utilisateur existe et que son compte est actif
        if (!$user || $user['statut'] !== 'ACTIF') {
            return false;
        }
        
        // Vérifier le mot de passe
        if (!password_verify($password, $user['mot_de_passe'])) {
            // Enregistrer la tentative échouée
            $this->logFailedAttempt($email);
            return false;
        }
        
        // Mettre à jour la dernière connexion
        $this->db->update('utilisateurs', 
            ['derniere_connexion' => date('Y-m-d H:i:s')],
            'id = ?',
            [$user['id']]
        );
        
        // Nettoyer les données sensibles
        unset($user['mot_de_passe']);
        
        // Démarrer la session si ce n'est pas déjà fait
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Stocker les informations utilisateur en session
        $_SESSION['user'] = $user;
        
        // Générer un nouveau jeton de session pour prévenir les attaques de fixation de session
        session_regenerate_id(true);
        
        return $user;
    }
    
    /**
     * Enregistre un nouvel utilisateur
     * 
     * @param array $userData Données utilisateur (email, password, nom, prenom, etc.)
     * @return int|false ID de l'utilisateur créé ou false si échec
     */
    public function register($userData) {
        // Vérifier si l'email existe déjà
        $existingUser = $this->db->selectOne(
            "SELECT id FROM utilisateurs WHERE email = ?",
            [$userData['email']]
        );
        
        if ($existingUser) {
            return false;
        }
        
        // Hasher le mot de passe
        $userData['mot_de_passe'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        unset($userData['password']);
        
        // Ajouter date de création
        $userData['date_creation'] = date('Y-m-d H:i:s');
        
        // Définir statut par défaut
        $userData['statut'] = 'ACTIF';
        $userData['role'] = 'CLIENT';
        
        // Insérer l'utilisateur
        return $this->db->insert('utilisateurs', $userData);
    }
    
    /**
     * Déconnecte l'utilisateur
     */
    public function logout() {
        // Démarrer la session si ce n'est pas déjà fait
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Supprimer les données de session
        $_SESSION = [];
        
        // Supprimer le cookie de session
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
        
        // Détruire la session
        session_destroy();
    }
    
    /**
     * Vérifie si l'utilisateur est connecté
     * 
     * @return bool
     */
    public function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['user']);
    }
    
    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     * 
     * @param string $role Le rôle à vérifier
     * @return bool
     */
    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return $_SESSION['user']['role'] === $role;
    }
    
    /**
     * Vérifie si l'utilisateur est administrateur
     * 
     * @return bool
     */
    public function isAdmin() {
        return $this->hasRole('ADMINISTRATEUR');
    }
    
    /**
     * Enregistre une tentative de connexion échouée
     * 
     * @param string $email Email utilisé pour la tentative
     */
    private function logFailedAttempt($email) {
        // Récupérer l'état actuel de sécurité
        $security = $this->db->selectOne(
            "SELECT utilisateur_id, tentatives_connexion, date_derniere_tentative 
             FROM securite 
             JOIN utilisateurs ON securite.utilisateur_id = utilisateurs.id
             WHERE utilisateurs.email = ?",
            [$email]
        );
        
        if (!$security) {
            // Récupérer l'ID utilisateur
            $user = $this->db->selectOne(
                "SELECT id FROM utilisateurs WHERE email = ?",
                [$email]
            );
            
            if (!$user) {
                return; // Utilisateur inexistant, ne rien faire
            }
            
            // Créer un nouvel enregistrement de sécurité
            $this->db->insert('securite', [
                'utilisateur_id' => $user['id'],
                'tentatives_connexion' => 1,
                'date_derniere_tentative' => date('Y-m-d H:i:s')
            ]);
            
            return;
        }
        
        // Incrémenter le compteur de tentatives
        $this->db->update('securite', 
            [
                'tentatives_connexion' => $security['tentatives_connexion'] + 1,
                'date_derniere_tentative' => date('Y-m-d H:i:s')
            ],
            'utilisateur_id = ?',
            [$security['utilisateur_id']]
        );
        
        // Si trop de tentatives, bloquer le compte
        if ($security['tentatives_connexion'] + 1 >= 5) {
            $this->db->update('securite', 
                [
                    'compte_bloque' => 1,
                    'date_blocage' => date('Y-m-d H:i:s')
                ],
                'utilisateur_id = ?',
                [$security['utilisateur_id']]
            );
            
            $this->db->update('utilisateurs',
                ['statut' => 'BLOQUE'],
                'id = ?',
                [$security['utilisateur_id']]
            );
        }
    }
    
    /**
     * Réinitialise le compteur de tentatives de connexion
     * 
     * @param int $userId ID de l'utilisateur
     */
    private function resetFailedAttempts($userId) {
        $this->db->update('securite',
            [
                'tentatives_connexion' => 0,
                'compte_bloque' => 0,
                'date_blocage' => null
            ],
            'utilisateur_id = ?',
            [$userId]
        );
    }
    
    /**
     * Génère un jeton pour réinitialiser le mot de passe
     * 
     * @param string $email Email de l'utilisateur
     * @return string|false Le jeton ou false si échec
     */
    public function generatePasswordResetToken($email) {
        // Vérifier si l'utilisateur existe
        $user = $this->db->selectOne(
            "SELECT id FROM utilisateurs WHERE email = ? AND statut = 'ACTIF'",
            [$email]
        );
        
        if (!$user) {
            return false;
        }
        
        // Générer un jeton unique
        $token = bin2hex(random_bytes(32));
        $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Stocker le jeton dans la base de données
        $this->db->insert('reset_tokens', [
            'utilisateur_id' => $user['id'],
            'token' => $token,
            'expiration' => $expiration,
            'utilise' => 0
        ]);
        
        return $token;
    }
    
    /**
     * Vérifie la validité d'un jeton de réinitialisation
     * 
     * @param string $token Le jeton à vérifier
     * @return int|false ID de l'utilisateur ou false si jeton invalide
     */
    public function verifyPasswordResetToken($token) {
        $tokenData = $this->db->selectOne(
            "SELECT utilisateur_id, expiration, utilise 
             FROM reset_tokens 
             WHERE token = ?",
            [$token]
        );
        
        if (!$tokenData || $tokenData['utilise'] == 1 || strtotime($tokenData['expiration']) < time()) {
            return false;
        }
        
        return $tokenData['utilisateur_id'];
    }
    
    /**
     * Réinitialise le mot de passe d'un utilisateur
     * 
     * @param string $token Le jeton de réinitialisation
     * @param string $newPassword Le nouveau mot de passe
     * @return bool Succès ou échec
     */
    public function resetPassword($token, $newPassword) {
        $userId = $this->verifyPasswordResetToken($token);
        
        if (!$userId) {
            return false;
        }
        
        // Mettre à jour le mot de passe
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->update('utilisateurs',
            ['mot_de_passe' => $hashedPassword],
            'id = ?',
            [$userId]
        );
        
        // Marquer le jeton comme utilisé
        $this->db->update('reset_tokens',
            ['utilise' => 1],
            'token = ?',
            [$token]
        );
        
        // Réinitialiser les tentatives échouées
        $this->resetFailedAttempts($userId);
        
        return true;
    }
}