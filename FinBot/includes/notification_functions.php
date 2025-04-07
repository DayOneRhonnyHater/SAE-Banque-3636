<?php
require_once __DIR__ . '/../classes/Database.php';

/**
 * Ajoute une notification pour un utilisateur
 * 
 * @param int $userId ID de l'utilisateur
 * @param string $message Contenu de la notification
 * @param string $type Type de notification (info, success, warning, danger)
 * @param string $lien Lien optionnel à associer à la notification
 * @return int|bool ID de la notification créée ou false si échec
 */
function addNotification($userId, $message, $type = 'info', $lien = null) {
    try {
        $db = Database::getInstance();
        return $db->insert('notifications', [
            'utilisateur_id' => $userId,  // Correction du nom de colonne
            'type' => $type,
            'contenu' => $message,        // Correction du nom de colonne
            'lien' => $lien,
            'date_creation' => date('Y-m-d H:i:s'),
            'lu' => 0                     // Correction du nom de colonne
        ]);
    } catch (Exception $e) {
        error_log("Erreur lors de l'ajout de la notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Crée une nouvelle notification pour un utilisateur
 * 
 * @param int $userId ID de l'utilisateur destinataire
 * @param string $message Contenu de la notification
 * @param string $type Type de notification (info, success, warning, danger)
 * @return int|bool ID de la notification créée ou false en cas d'échec
 */
function createNotification($userId, $message, $type = 'info') {
    $db = Database::getInstance();
    
    try {
        $data = [
            'utilisateur_id' => $userId,
            'contenu' => $message,
            'type' => $type,
            'date_creation' => date('Y-m-d H:i:s'),
            'lu' => 0
        ];
        
        return $db->insert('notifications', $data);
    } catch (Exception $e) {
        error_log('Erreur lors de la création de la notification: ' . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les dernières notifications d'un utilisateur
 * 
 * @param int $userId ID de l'utilisateur
 * @param int $limit Nombre maximum de notifications à récupérer
 * @return array Liste des notifications
 */
function getLatestNotifications($userId, $limit = 5) {
    try {
        $db = Database::getInstance();
        return $db->select(
            "SELECT * FROM notifications 
             WHERE utilisateur_id = ? 
             ORDER BY date_creation DESC 
             LIMIT ?",
            [$userId, $limit]
        );
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les notifications non lues d'un utilisateur
 * 
 * @param int $userId ID de l'utilisateur
 * @return array Liste des notifications non lues
 */
function getUnreadNotifications($userId) {
    $db = Database::getInstance();
    
    try {
        $sql = "SELECT * FROM notifications 
                WHERE utilisateur_id = ? AND lu = 0 
                ORDER BY date_creation DESC";
        
        return $db->select($sql, [$userId]);
    } catch (Exception $e) {
        error_log('Erreur lors de la récupération des notifications: ' . $e->getMessage());
        return [];
    }
}

/**
 * Marque une notification comme lue
 * 
 * @param int $notificationId ID de la notification
 * @param int $userId ID de l'utilisateur (pour vérification)
 * @return bool Succès de l'opération
 */
function markNotificationAsRead($notificationId, $userId) {
    $db = Database::getInstance();
    
    try {
        $db->update('notifications', 
                    ['lu' => 1], 
                    'id = ? AND utilisateur_id = ?', 
                    [$notificationId, $userId]);
        return true;
    } catch (Exception $e) {
        error_log('Erreur lors du marquage de la notification: ' . $e->getMessage());
        return false;
    }
}

/**
 * Marque toutes les notifications d'un utilisateur comme lues
 * 
 * @param int $userId ID de l'utilisateur
 * @return bool Succès ou échec
 */
function markAllNotificationsAsRead($userId) {
    try {
        $db = Database::getInstance();
        return $db->update('notifications',
            ['lu' => 1],
            'utilisateur_id = ? AND lu = 0',
            [$userId]
        ) > 0;
    } catch (Exception $e) {
        error_log("Erreur lors du marquage de toutes les notifications comme lues: " . $e->getMessage());
        return false;
    }
}

/**
 * Supprime une notification
 * 
 * @param int $notificationId ID de la notification
 * @param int $userId ID de l'utilisateur (vérification de propriété)
 * @return bool Succès ou échec
 */
function deleteNotification($notificationId, $userId = null) {
    try {
        $db = Database::getInstance();
        $conditions = 'id = ?';
        $params = [$notificationId];
        
        if ($userId !== null) {
            $conditions .= ' AND utilisateur_id = ?';
            $params[] = $userId;
        }
        
        return $db->delete('notifications', $conditions, $params) > 0;
    } catch (Exception $e) {
        error_log("Erreur lors de la suppression de la notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Compte le nombre de notifications non lues pour un utilisateur
 * 
 * @param int $userId ID de l'utilisateur
 * @return int Nombre de notifications non lues
 */
function countUnreadNotifications($userId) {
    $db = Database::getInstance();
    
    try {
        $result = $db->selectOne(
            "SELECT COUNT(*) as count FROM notifications WHERE utilisateur_id = ? AND lu = 0",
            [$userId]
        );
        
        return $result ? $result['count'] : 0;
    } catch (Exception $e) {
        error_log('Erreur lors du comptage des notifications: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Crée une notification pour un événement système
 * 
 * @param int $userId ID de l'utilisateur
 * @param string $evenement Type d'événement (transaction, virement, etc.)
 * @param array $donnees Données liées à l'événement
 * @return int|bool ID de la notification créée ou false si échec
 */
function createSystemNotification($userId, $evenement, $donnees = []) {
    $message = '';
    $type = 'info';
    $lien = null;
    
    switch ($evenement) {
        case 'nouveau_virement':
            $montant = isset($donnees['montant']) ? number_format($donnees['montant'], 2, ',', ' ') . ' €' : '';
            $expediteur = $donnees['expediteur'] ?? 'Un utilisateur';
            $message = "Vous avez reçu un virement de {$montant} de {$expediteur}";
            $type = 'success';
            $lien = '/transactions.php';
            break;
            
        case 'creation_compte':
            $typeCompte = $donnees['type_compte'] ?? 'compte';
            $message = "Votre {$typeCompte} a été créé avec succès";
            $type = 'success';
            $lien = '/accounts.php';
            break;
            
        case 'nouveau_message':
            $expediteur = $donnees['expediteur'] ?? 'Un conseiller';
            $message = "Nouveau message de {$expediteur}";
            $lien = '/messages.php';
            break;
            
        case 'demande_pret_statut':
            $status = $donnees['statut'] ?? '';
            $type = ($status === 'APPROUVE') ? 'success' : ($status === 'REFUSE' ? 'danger' : 'info');
            $message = "Votre demande de prêt a été {$status}";
            $lien = '/loans.php';
            break;
            
        default:
            $message = $donnees['message'] ?? 'Notification système';
            break;
    }
    
    return addNotification($userId, $message, $type, $lien);
}