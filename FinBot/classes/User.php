<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\classes\User.php

require_once __DIR__ . '/Database.php';

class User {
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
     * Récupère un utilisateur par son ID
     * 
     * @param int $userId ID de l'utilisateur
     * @return array|false Données de l'utilisateur ou false si non trouvé
     */
    public function getUserById($userId) {
        $user = $this->db->selectOne(
            "SELECT id, email, nom, prenom, telephone, adresse, ville, code_postal,
                    date_naissance, role, statut, date_creation, derniere_connexion
             FROM utilisateurs 
             WHERE id = ?",
            [$userId]
        );
        
        // Ne pas renvoyer le mot de passe
        return $user;
    }
    
    /**
     * Récupère un utilisateur par son email
     * 
     * @param string $email Email de l'utilisateur
     * @return array|null Données de l'utilisateur ou null si non trouvé
     */
    public function getUserByEmail($email) {
        $sql = "SELECT * FROM utilisateurs WHERE email = ?";
        
        try {
            return $this->db->selectOne($sql, [$email]);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération de l\'utilisateur par email: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Met à jour les informations d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param array $data Données à mettre à jour
     * @return bool Succès ou échec
     */
    public function updateUser($userId, $data) {
        // Champs autorisés à la mise à jour
        $allowedFields = [
            'nom', 'prenom', 'telephone', 'adresse', 'ville', 'code_postal', 
            'date_naissance', 'email'
        ];
        
        $updateData = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updateData[$key] = $value;
            }
        }
        
        if (empty($updateData)) {
            return false;
        }
        
        // Si l'email est modifié, vérifier qu'il n'existe pas déjà
        if (isset($updateData['email'])) {
            if ($this->isEmailTaken($updateData['email'], $userId)) {
                throw new Exception("Cet email est déjà utilisé par un autre compte");
            }
        }
        
        return $this->db->update('utilisateurs',
            $updateData,
            'id = ?',
            [$userId]
        ) > 0;
    }
    
    /**
     * Change le mot de passe d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $oldPassword Ancien mot de passe
     * @param string $newPassword Nouveau mot de passe
     * @return bool Succès ou échec
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        // Vérifier l'ancien mot de passe
        $user = $this->db->selectOne(
            "SELECT mot_de_passe FROM utilisateurs WHERE id = ?",
            [$userId]
        );
        
        if (!$user || !password_verify($oldPassword, $user['mot_de_passe'])) {
            throw new Exception("Ancien mot de passe incorrect");
        }
        
        // Mettre à jour le mot de passe
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        return $this->db->update('utilisateurs',
            ['mot_de_passe' => $hashedPassword],
            'id = ?',
            [$userId]
        ) > 0;
    }
    
    /**
     * Télécharge et enregistre une photo de profil
     * 
     * @param int $userId ID de l'utilisateur
     * @param array $file Données du fichier ($_FILES['photo'])
     * @return bool Succès ou échec
     */
    public function uploadProfilePicture($userId, $file) {
        if (!$file || !isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception("Aucun fichier téléchargé");
        }
        
        // Vérifier le type de fichier
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception("Type de fichier non autorisé. Utilisez JPG, PNG ou GIF");
        }
        
        // Vérifier la taille
        if ($file['size'] > 5 * 1024 * 1024) { // 5 MB
            throw new Exception("Le fichier est trop volumineux (max 5 MB)");
        }
        
        // Créer le dossier de destination s'il n'existe pas
        $uploadDir = __DIR__ . '/../uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Générer un nom de fichier unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $userId . '_' . uniqid() . '.' . $extension;
        $destination = $uploadDir . $filename;
        
        // Déplacer le fichier
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception("Erreur lors du téléchargement du fichier");
        }
        
        // Mettre à jour la base de données
        $success = $this->db->update('utilisateurs',
            ['photo_profil' => 'uploads/avatars/' . $filename],
            'id = ?',
            [$userId]
        ) > 0;
        
        if (!$success) {
            // Supprimer le fichier si la mise à jour a échoué
            @unlink($destination);
            throw new Exception("Erreur lors de la mise à jour de la base de données");
        }
        
        return $success;
    }
    
    /**
     * Récupère la photo de profil d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return string|null Chemin vers la photo de profil ou null si non définie
     */
    public function getProfilePicture($userId) {
        $user = $this->db->selectOne(
            "SELECT photo_profil FROM utilisateurs WHERE id = ?",
            [$userId]
        );
        
        return $user && $user['photo_profil'] ? $user['photo_profil'] : null;
    }
    
    /**
     * Récupère les préférences d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Préférences de l'utilisateur
     */
    public function getPreferences($userId) {
        $preferences = $this->db->select(
            "SELECT `cle`, `valeur` FROM preferences_utilisateurs WHERE utilisateur_id = ?",
            [$userId]
        );
        
        $result = [];
        foreach ($preferences as $pref) {
            $result[$pref['cle']] = $pref['valeur'];
        }
        
        return $result;
    }
    
    /**
     * Met à jour une préférence utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $key Clé de la préférence
     * @param string $value Valeur de la préférence
     * @return bool Succès ou échec
     */
    public function setPreference($userId, $key, $value) {
        // Vérifier si la préférence existe déjà
        $existing = $this->db->selectOne(
            "SELECT id FROM preferences_utilisateurs WHERE utilisateur_id = ? AND cle = ?",
            [$userId, $key]
        );
        
        if ($existing) {
            // Mettre à jour
            return $this->db->update('preferences_utilisateurs',
                ['valeur' => $value],
                'utilisateur_id = ? AND cle = ?',
                [$userId, $key]
            ) > 0;
        } else {
            // Créer
            return $this->db->insert('preferences_utilisateurs', [
                'utilisateur_id' => $userId,
                'cle' => $key,
                'valeur' => $value
            ]) > 0;
        }
    }
    
    /**
     * Récupère tous les utilisateurs (pour l'administration)
     * 
     * @param string $recherche Terme de recherche
     * @param string $statut Filtre par statut
     * @param string $role Filtre par rôle
     * @param int $limit Limite de résultats
     * @param int $offset Décalage pour pagination
     * @return array Liste des utilisateurs
     */
    public function getAllUsers($recherche = '', $statut = '', $role = '', $limit = 20, $offset = 0) {
        $query = "SELECT id, email, nom, prenom, telephone, role, statut, date_creation, derniere_connexion
                  FROM utilisateurs
                  WHERE 1=1";
        $params = [];
        
        if ($recherche) {
            $query .= " AND (email LIKE ? OR nom LIKE ? OR prenom LIKE ?)";
            $searchTerm = '%' . $recherche . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if ($statut) {
            $query .= " AND statut = ?";
            $params[] = $statut;
        }
        
        if ($role) {
            $query .= " AND role = ?";
            $params[] = $role;
        }
        
        $query .= " ORDER BY date_creation DESC";
        
        if ($limit > 0) {
            $query .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        return $this->db->select($query, $params);
    }
    
    /**
     * Change le statut d'un utilisateur (admin uniquement)
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $statut Nouveau statut
     * @return bool Succès ou échec
     */
    public function changeUserStatus($userId, $statut) {
        $validStatus = ['ACTIF', 'BLOQUE', 'INACTIF'];
        
        if (!in_array($statut, $validStatus)) {
            throw new Exception("Statut invalide");
        }
        
        return $this->db->update('utilisateurs',
            ['statut' => $statut],
            'id = ?',
            [$userId]
        ) > 0;
    }
    
    /**
     * Change le rôle d'un utilisateur (admin uniquement)
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $role Nouveau rôle
     * @return bool Succès ou échec
     */
    public function changeUserRole($userId, $role) {
        $validRoles = ['CLIENT', 'CONSEILLER', 'ADMINISTRATEUR'];
        
        if (!in_array($role, $validRoles)) {
            throw new Exception("Rôle invalide");
        }
        
        return $this->db->update('utilisateurs',
            ['role' => $role],
            'id = ?',
            [$userId]
        ) > 0;
    }
    
    /**
     * Vérifie si un utilisateur est admin
     * 
     * @param int $userId ID de l'utilisateur
     * @return bool True si admin, false sinon
     */
    public function isAdmin($userId) {
        $user = $this->db->selectOne(
            "SELECT role FROM utilisateurs WHERE id = ?",
            [$userId]
        );
        
        return $user && $user['role'] === 'ADMINISTRATEUR';
    }
    
    /**
     * Vérifie si un utilisateur est conseiller
     * 
     * @param int $userId ID de l'utilisateur
     * @return bool True si conseiller, false sinon
     */
    public function isConseiller($userId) {
        $user = $this->db->selectOne(
            "SELECT role FROM utilisateurs WHERE id = ?",
            [$userId]
        );
        
        return $user && ($user['role'] === 'CONSEILLER' || $user['role'] === 'ADMINISTRATEUR');
    }
    
    /**
     * Récupère l'historique des connexions d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $limit Nombre maximum de connexions à récupérer
     * @return array Historique des connexions
     */
    public function getLoginHistory($userId, $limit = 10) {
        return $this->db->select(
            "SELECT id, date_connexion, ip_adresse, user_agent 
             FROM connexions 
             WHERE utilisateur_id = ? 
             ORDER BY date_connexion DESC 
             LIMIT ?",
            [$userId, $limit]
        );
    }
    
    /**
     * Vérifie si une adresse email est déjà utilisée
     * 
     * @param string $email Adresse email à vérifier
     * @param int|null $excludeUserId ID utilisateur à exclure (pour les mises à jour)
     * @return bool True si l'email est déjà pris
     */
    public function isEmailTaken($email, $excludeUserId = null) {
        $sql = "SELECT COUNT(*) as count FROM utilisateurs WHERE email = ?";
        $params = [$email];
        
        if ($excludeUserId) {
            $sql .= " AND id != ?";
            $params[] = $excludeUserId;
        }
        
        try {
            $result = $this->db->selectOne($sql, $params);
            return ($result && $result['count'] > 0);
        } catch (Exception $e) {
            error_log('Erreur lors de la vérification de l\'email: ' . $e->getMessage());
            return true; // Par sécurité, on considère que l'email est déjà pris en cas d'erreur
        }
    }
    
    /**
     * Crée un nouvel utilisateur
     * 
     * @param array $userData Données de l'utilisateur
     * @return int|bool ID de l'utilisateur créé ou false en cas d'erreur
     */
    public function createUser($userData) {
        try {
            // Ajout de la date de création
            $userData['date_creation'] = date('Y-m-d H:i:s');
            
            // Insérer l'utilisateur
            return $this->db->insert('utilisateurs', $userData);
        } catch (Exception $e) {
            error_log('Erreur lors de la création de l\'utilisateur: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Met à jour la date de dernière connexion
     * 
     * @param int $userId ID de l'utilisateur
     * @return bool Succès de l'opération
     */
    public function updateLastLogin($userId) {
        try {
            return $this->db->update('utilisateurs', 
                ['derniere_connexion' => date('Y-m-d H:i:s')], 
                'id = ?', 
                [$userId]
            );
        } catch (Exception $e) {
            error_log('Erreur lors de la mise à jour de la dernière connexion: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enregistre une connexion dans les logs
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $ipAddress Adresse IP
     * @param string $userAgent User agent du navigateur
     * @return bool Succès de l'opération
     */
    public function logLogin($userId, $ipAddress, $userAgent) {
        try {
            $data = [
                'utilisateur_id' => $userId,
                'date_connexion' => date('Y-m-d H:i:s'),
                'ip_adresse' => $ipAddress,
                'user_agent' => $userAgent
            ];
            
            return $this->db->insert('connexions', $data);
        } catch (Exception $e) {
            error_log('Erreur lors de l\'enregistrement de la connexion: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère les conseillers disponibles pour un client
     * 
     * @param int $clientId ID du client
     * @return array Liste des conseillers
     */
    public function getAvailableAdvisors($clientId) {
        try {
            // Récupérer d'abord le conseiller attitré du client (s'il en a un)
            $assignedAdvisor = $this->db->selectOne(
                "SELECT c.id, c.nom, c.prenom, c.email, c.role
                 FROM utilisateurs c
                 JOIN utilisateurs cl ON cl.conseiller_id = c.id
                 WHERE cl.id = ? AND c.role = 'CONSEILLER' AND c.statut = 'ACTIF'", 
                [$clientId]
            );
            
            // Récupérer tous les autres conseillers actifs
            $otherAdvisors = $this->db->select(
                "SELECT id, nom, prenom, email, role
                 FROM utilisateurs 
                 WHERE role = 'CONSEILLER' AND statut = 'ACTIF'" .
                ($assignedAdvisor ? " AND id != ?" : ""),
                ($assignedAdvisor ? [$assignedAdvisor['id']] : [])
            );
            
            // Combiner les résultats (conseiller attitré en premier)
            $advisors = [];
            if ($assignedAdvisor) {
                $assignedAdvisor['assigned'] = true;
                $advisors[] = $assignedAdvisor;
            }
            
            foreach ($otherAdvisors as $advisor) {
                $advisor['assigned'] = false;
                $advisors[] = $advisor;
            }
            
            return $advisors;
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des conseillers: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les clients d'un conseiller
     * 
     * @param int $advisorId ID du conseiller
     * @return array Liste des clients
     */
    public function getClientsForAdvisor($advisorId) {
        try {
            return $this->db->select(
                "SELECT id, nom, prenom, email, role
                 FROM utilisateurs 
                 WHERE conseiller_id = ? AND role = 'CLIENT' AND statut = 'ACTIF'
                 ORDER BY nom, prenom", 
                [$advisorId]
            );
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des clients: ' . $e->getMessage());
            return [];
        }
    }
    
    
}