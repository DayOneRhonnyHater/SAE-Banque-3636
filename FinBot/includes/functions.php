<?php
require_once __DIR__ . '/../classes/Database.php';

/**
 * Récupère la liste des utilisateurs filtrée et paginée
 */
function getUsers($search = '', $role = '', $status = '', $limit = 10, $offset = 0) {
    $db = Database::getInstance();
    
    $query = "SELECT * FROM utilisateurs WHERE 1=1";
    $params = [];
    
    // Appliquer les filtres
    if (!empty($search)) {
        $query .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($role)) {
        $query .= " AND role = ?";
        $params[] = $role;
    }
    
    if ($status !== '') {
        $query .= " AND actif = ?";
        $params[] = (int)$status;
    }
    
    $query .= " ORDER BY date_creation DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    return $db->select($query, $params);
}

/**
 * Compte le nombre total d'utilisateurs selon les filtres
 */
function countUsers($search = '', $role = '', $status = '') {
    $db = Database::getInstance();
    
    $query = "SELECT COUNT(*) as total FROM utilisateurs WHERE 1=1";
    $params = [];
    
    // Appliquer les filtres
    if (!empty($search)) {
        $query .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($role)) {
        $query .= " AND role = ?";
        $params[] = $role;
    }
    
    if ($status !== '') {
        $query .= " AND actif = ?";
        $params[] = (int)$status;
    }
    
    $result = $db->selectOne($query, $params);
    return $result ? $result['total'] : 0;
}

/**
 * Récupère un utilisateur par son ID
 */
function getUserById($userId) {
    $db = Database::getInstance();
    
    $query = "SELECT * FROM utilisateurs WHERE id = ?";
    return $db->selectOne($query, [$userId]);
}

/**
 * Ajoute un nouvel utilisateur
 */
function addUser($data) {
    $db = Database::getInstance();
    
    // Vérifier si l'email existe déjà
    $emailCheck = $db->selectOne("SELECT COUNT(*) as count FROM utilisateurs WHERE email = ?", [$data['email']]);
    if ($emailCheck && $emailCheck['count'] > 0) {
        return ['success' => false, 'message' => 'Cette adresse email est déjà utilisée.'];
    }
    
    // Vérifier la concordance des mots de passe
    if ($data['password'] !== $data['confirm_password']) {
        return ['success' => false, 'message' => 'Les mots de passe ne correspondent pas.'];
    }
    
    try {
        $db->beginTransaction();
        
        // Traitement de la photo de profil
        $photoPath = null;
        if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === UPLOAD_ERR_OK) {
            $photoPath = uploadProfilePicture($_FILES['photo_profil']);
            if (!$photoPath) {
                return ['success' => false, 'message' => 'Erreur lors du téléchargement de la photo de profil.'];
            }
        }
        
        // Hachage du mot de passe
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Préparation des données d'insertion
        $userData = [
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'password' => $hashedPassword,
            'telephone' => $data['telephone'] ?? null,
            'adresse' => $data['adresse'] ?? null,
            'role' => $data['role'],
            'photo_profil' => $photoPath,
            'actif' => (int)($data['actif'] ?? 1),
            'date_creation' => date('Y-m-d H:i:s')
        ];
        
        // Insertion de l'utilisateur
        $db->insert('utilisateurs', $userData);
        
        $db->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => 'Erreur lors de l\'ajout de l\'utilisateur: ' . $e->getMessage()];
    }
}

/**
 * Met à jour un utilisateur existant
 */
function updateUser($data) {
    $db = Database::getInstance();
    
    // Vérifier que l'utilisateur existe
    $user = $db->selectOne("SELECT * FROM utilisateurs WHERE id = ?", [$data['user_id']]);
    
    if (!$user) {
        return ['success' => false, 'message' => 'Utilisateur non trouvé.'];
    }
    
    // Vérifier si l'email existe déjà pour un autre utilisateur
    $emailCheck = $db->selectOne(
        "SELECT COUNT(*) as count FROM utilisateurs WHERE email = ? AND id != ?", 
        [$data['email'], $data['user_id']]
    );
    
    if ($emailCheck && $emailCheck['count'] > 0) {
        return ['success' => false, 'message' => 'Cette adresse email est déjà utilisée par un autre utilisateur.'];
    }
    
    try {
        $db->beginTransaction();
        
        // Préparation des données de base
        $userData = [
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'telephone' => $data['telephone'] ?? null,
            'adresse' => $data['adresse'] ?? null,
            'role' => $data['role'],
            'actif' => (int)($data['actif'] ?? 1)
        ];
        
        // Gestion du mot de passe si modifié
        if (!empty($data['password'])) {
            if ($data['password'] !== $data['confirm_password']) {
                return ['success' => false, 'message' => 'Les mots de passe ne correspondent pas.'];
            }
            
            $userData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        // Gestion de la photo de profil
        // Si l'utilisateur souhaite supprimer la photo actuelle
        if (isset($data['delete_photo'])) {
            if (!empty($user['photo_profil']) && file_exists('../' . $user['photo_profil'])) {
                unlink('../' . $user['photo_profil']);
            }
            $userData['photo_profil'] = null;
        }
        // Si l'utilisateur télécharge une nouvelle photo
        elseif (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === UPLOAD_ERR_OK) {
            $photoPath = uploadProfilePicture($_FILES['photo_profil']);
            if (!$photoPath) {
                return ['success' => false, 'message' => 'Erreur lors du téléchargement de la photo de profil.'];
            }
            
            // Supprimer l'ancienne photo si elle existe
            if (!empty($user['photo_profil']) && file_exists('../' . $user['photo_profil'])) {
                unlink('../' . $user['photo_profil']);
            }
            
            $userData['photo_profil'] = $photoPath;
        }
        
        // Mise à jour de l'utilisateur
        $db->update('utilisateurs', $userData, 'id = ?', [$data['user_id']]);
        
        $db->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour de l\'utilisateur: ' . $e->getMessage()];
    }
}

/**
 * Supprime un utilisateur
 */
function deleteUser($userId) {
    $db = Database::getInstance();
    
    // Vérifier que l'utilisateur existe
    $user = $db->selectOne("SELECT * FROM utilisateurs WHERE id = ?", [$userId]);
    
    if (!$user) {
        return ['success' => false, 'message' => 'Utilisateur non trouvé.'];
    }
    
    try {
        $db->beginTransaction();
        
        // Supprimer la photo de profil si elle existe
        if (!empty($user['photo_profil']) && file_exists('../' . $user['photo_profil'])) {
            unlink('../' . $user['photo_profil']);
        }
        
        // TODO: Ajouter ici la gestion des dépendances (par exemple, supprimer les comptes associés, etc.)
        
        // Supprimer l'utilisateur
        $db->delete('utilisateurs', 'id = ?', [$userId]);
        
        $db->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => 'Erreur lors de la suppression de l\'utilisateur: ' . $e->getMessage()];
    }
}

/**
 * Change le statut d'un utilisateur (actif/inactif)
 */
function toggleUserStatus($userId) {
    $db = Database::getInstance();
    
    try {
        // Récupérer le statut actuel
        $user = $db->selectOne("SELECT actif FROM utilisateurs WHERE id = ?", [$userId]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Utilisateur non trouvé.'];
        }
        
        // Inverser le statut
        $newStatus = $user['actif'] ? 0 : 1;
        
        // Mettre à jour le statut
        $db->update('utilisateurs', ['actif' => $newStatus], 'id = ?', [$userId]);
        
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erreur lors du changement de statut: ' . $e->getMessage()];
    }
}

/**
 * Télécharge et traite une photo de profil
 */
function uploadProfilePicture($file) {
    $uploadDir = 'uploads/profiles/';
    
    // Créer le répertoire s'il n'existe pas
    if (!file_exists('../' . $uploadDir)) {
        mkdir('../' . $uploadDir, 0777, true);
    }
    
    // Vérifier le type de fichier (uniquement JPG et PNG)
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($fileType != "jpg" && $fileType != "jpeg" && $fileType != "png") {
        return false;
    }
    
    // Vérifier la taille du fichier (max 2Mo)
    if ($file['size'] > 2 * 1024 * 1024) {
        return false;
    }
    
    // Générer un nom unique pour éviter les conflits
    $fileName = 'profile_' . uniqid() . '.' . $fileType;
    $targetFile = $uploadDir . $fileName;
    
    // Déplacer le fichier téléchargé vers le répertoire cible
    if (!move_uploaded_file($file['tmp_name'], '../' . $targetFile)) {
        return false;
    }
    
    return $targetFile;
}

/**
 * Fonctions d'aide pour l'affichage
 */
function formatDate($dateStr, $includeTime = false) {
    if (empty($dateStr)) return '-';
    $date = new DateTime($dateStr);
    return $includeTime ? $date->format('d/m/Y H:i') : $date->format('d/m/Y');
}

function getRoleBadgeClass($role) {
    switch ($role) {
        case 'ADMINISTRATEUR':
            return 'danger';
        case 'CLIENT':
            return 'primary';
        default:
            return 'secondary';
    }
}

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'ACTIF':
        case 'SUCCESS':
            return 'success';
        case 'BLOQUE':
            return 'danger';
        case 'INACTIF':
            return 'warning';
        default:
            return 'secondary';
    }
}

function formatMontant($montant) {
    return number_format($montant, 2, ',', ' ') . ' €';
}

/**
 * Enregistre une action dans les logs
 */
function logAction($userId, $action, $details = '') {
    $db = Database::getInstance();
    
    try {
        $logData = [
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'date_action' => date('Y-m-d H:i:s')
        ];
        
        $db->insert('logs', $logData);
        
        return true;
    } catch (Exception $e) {
        // En cas d'erreur, on pourrait la journaliser, mais on ne bloque pas l'exécution
        return false;
    }
}