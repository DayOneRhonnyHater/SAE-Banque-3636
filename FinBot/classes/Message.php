<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\classes\Message.php

require_once __DIR__ . '/Database.php';

/**
 * Classe pour gérer les messages entre utilisateurs
 */
class Message {
    private static $instance = null;
    private $db;
    
    /**
     * Constructeur privé (pattern Singleton)
     */
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Récupère l'instance unique de la classe
     * 
     * @return Message Instance unique
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Récupère les messages reçus par un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Liste des messages reçus
     */
    public function getReceivedMessages($userId) {
        $sql = "SELECT m.*, 
                       e.prenom AS expediteur_prenom, e.nom AS expediteur_nom, e.email AS expediteur_email,
                       d.prenom AS destinataire_prenom, d.nom AS destinataire_nom, d.email AS destinataire_email,
                       CONCAT(e.prenom, ' ', e.nom) AS expediteur_nom,
                       CONCAT(d.prenom, ' ', d.nom) AS destinataire_nom
                FROM messages m
                JOIN utilisateurs e ON m.expediteur_id = e.id
                JOIN utilisateurs d ON m.destinataire_id = d.id
                WHERE m.destinataire_id = ? AND m.archive = 0 AND m.supprime = 0
                ORDER BY m.date_envoi DESC";
        
        try {
            return $this->db->select($sql, [$userId]);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des messages reçus: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les messages envoyés par un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Liste des messages envoyés
     */
    public function getSentMessages($userId) {
        $sql = "SELECT m.*, 
                       e.prenom AS expediteur_prenom, e.nom AS expediteur_nom, e.email AS expediteur_email,
                       d.prenom AS destinataire_prenom, d.nom AS destinataire_nom, d.email AS destinataire_email,
                       CONCAT(e.prenom, ' ', e.nom) AS expediteur_nom,
                       CONCAT(d.prenom, ' ', d.nom) AS destinataire_nom
                FROM messages m
                JOIN utilisateurs e ON m.expediteur_id = e.id
                JOIN utilisateurs d ON m.destinataire_id = d.id
                WHERE m.expediteur_id = ? AND m.supprime = 0
                ORDER BY m.date_envoi DESC";
        
        try {
            return $this->db->select($sql, [$userId]);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des messages envoyés: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les messages archivés d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Liste des messages archivés
     */
    public function getArchivedMessages($userId) {
        $sql = "SELECT m.*, 
                       e.prenom AS expediteur_prenom, e.nom AS expediteur_nom, e.email AS expediteur_email,
                       d.prenom AS destinataire_prenom, d.nom AS destinataire_nom, d.email AS destinataire_email,
                       CONCAT(e.prenom, ' ', e.nom) AS expediteur_nom,
                       CONCAT(d.prenom, ' ', d.nom) AS destinataire_nom
                FROM messages m
                JOIN utilisateurs e ON m.expediteur_id = e.id
                JOIN utilisateurs d ON m.destinataire_id = d.id
                WHERE m.destinataire_id = ? AND m.archive = 1 AND m.supprime = 0
                ORDER BY m.date_envoi DESC";
        
        try {
            return $this->db->select($sql, [$userId]);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des messages archivés: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère un message spécifique par son ID
     * 
     * @param int $messageId ID du message
     * @param int $userId ID de l'utilisateur (pour vérification)
     * @return array|null Détails du message ou null si non trouvé
     */
    public function getMessageById($messageId, $userId) {
        $sql = "SELECT m.*, 
                       e.prenom AS expediteur_prenom, e.nom AS expediteur_nom, e.email AS expediteur_email,
                       d.prenom AS destinataire_prenom, d.nom AS destinataire_nom, d.email AS destinataire_email,
                       CONCAT(e.prenom, ' ', e.nom) AS expediteur_nom,
                       CONCAT(d.prenom, ' ', d.nom) AS destinataire_nom
                FROM messages m
                JOIN utilisateurs e ON m.expediteur_id = e.id
                JOIN utilisateurs d ON m.destinataire_id = d.id
                WHERE m.id = ? AND (m.expediteur_id = ? OR m.destinataire_id = ?) AND m.supprime = 0";
        
        try {
            return $this->db->selectOne($sql, [$messageId, $userId, $userId]);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération du message: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Crée un nouveau message
     * 
     * @param array $data Données du message
     * @return int|false ID du message créé ou false en cas d'échec
     */
    public function createMessage($data) {
        // Validation des données
        if (empty($data['expediteur_id']) || empty($data['destinataire_id']) || 
            empty($data['sujet']) || empty($data['contenu'])) {
            throw new Exception("Données de message incomplètes");
        }
        
        try {
            $messageData = [
                'expediteur_id' => $data['expediteur_id'],
                'destinataire_id' => $data['destinataire_id'],
                'sujet' => $data['sujet'],
                'contenu' => $data['contenu'],
                'date_envoi' => $data['date_envoi'] ?? date('Y-m-d H:i:s'),
                'lu' => 0,
                'archive' => 0,
                'supprime' => 0,
                'important' => $data['important'] ?? 0,
                'message_parent_id' => $data['message_parent_id'] ?? null
            ];
            
            // Gestion de la pièce jointe si présente
            if (isset($data['piece_jointe']) && !empty($data['piece_jointe'])) {
                $messageData['piece_jointe'] = $data['piece_jointe'];
                $messageData['piece_jointe_nom'] = $data['piece_jointe_nom'] ?? null;
                $messageData['piece_jointe_type'] = $data['piece_jointe_type'] ?? null;
            }
            
            return $this->db->insert('messages', $messageData);
        } catch (Exception $e) {
            error_log('Erreur lors de la création du message: ' . $e->getMessage());
            throw new Exception("Erreur lors de l'envoi du message");
        }
    }
    
    /**
     * Marque un message comme lu
     * 
     * @param int $messageId ID du message
     * @param int $userId ID de l'utilisateur (pour vérification)
     * @return bool Succès de l'opération
     */
    public function markAsRead($messageId, $userId) {
        // Vérifier que l'utilisateur est bien le destinataire
        $message = $this->getMessageById($messageId, $userId);
        
        if (!$message || $message['destinataire_id'] != $userId) {
            throw new Exception("Vous n'êtes pas autorisé à effectuer cette action");
        }
        
        try {
            $this->db->update('messages', ['lu' => 1], 'id = ?', [$messageId]);
            return true;
        } catch (Exception $e) {
            error_log('Erreur lors du marquage comme lu: ' . $e->getMessage());
            throw new Exception("Erreur lors du marquage comme lu");
        }
    }
    
    /**
     * Marque un message comme non lu
     * 
     * @param int $messageId ID du message
     * @param int $userId ID de l'utilisateur (pour vérification)
     * @return bool Succès de l'opération
     */
    public function markAsUnread($messageId, $userId) {
        // Vérifier que l'utilisateur est bien le destinataire
        $message = $this->getMessageById($messageId, $userId);
        
        if (!$message || $message['destinataire_id'] != $userId) {
            throw new Exception("Vous n'êtes pas autorisé à effectuer cette action");
        }
        
        try {
            $this->db->update('messages', ['lu' => 0], 'id = ?', [$messageId]);
            return true;
        } catch (Exception $e) {
            error_log('Erreur lors du marquage comme non lu: ' . $e->getMessage());
            throw new Exception("Erreur lors du marquage comme non lu");
        }
    }
    
    /**
     * Supprime un message (marque comme supprimé)
     * 
     * @param int $messageId ID du message
     * @param int $userId ID de l'utilisateur (pour vérification)
     * @return bool Succès de l'opération
     */
    public function deleteMessage($messageId, $userId) {
        // Vérifier que l'utilisateur est bien l'expéditeur ou le destinataire
        $message = $this->getMessageById($messageId, $userId);
        
        if (!$message) {
            throw new Exception("Message introuvable");
        }
        
        try {
            $this->db->update('messages', ['supprime' => 1], 'id = ?', [$messageId]);
            return true;
        } catch (Exception $e) {
            error_log('Erreur lors de la suppression du message: ' . $e->getMessage());
            throw new Exception("Erreur lors de la suppression du message");
        }
    }
    
    /**
     * Archive un message
     * 
     * @param int $messageId ID du message
     * @param int $userId ID de l'utilisateur (pour vérification)
     * @return bool Succès de l'opération
     */
    public function archiveMessage($messageId, $userId) {
        // Vérifier que l'utilisateur est bien le destinataire
        $message = $this->getMessageById($messageId, $userId);
        
        if (!$message || $message['destinataire_id'] != $userId) {
            throw new Exception("Vous n'êtes pas autorisé à effectuer cette action");
        }
        
        try {
            $this->db->update('messages', ['archive' => 1], 'id = ?', [$messageId]);
            return true;
        } catch (Exception $e) {
            error_log('Erreur lors de l\'archivage du message: ' . $e->getMessage());
            throw new Exception("Erreur lors de l'archivage du message");
        }
    }
    
    /**
     * Désarchive un message
     * 
     * @param int $messageId ID du message
     * @param int $userId ID de l'utilisateur (pour vérification)
     * @return bool Succès de l'opération
     */
    public function unarchiveMessage($messageId, $userId) {
        // Vérifier que l'utilisateur est bien le destinataire
        $message = $this->getMessageById($messageId, $userId);
        
        if (!$message || $message['destinataire_id'] != $userId) {
            throw new Exception("Vous n'êtes pas autorisé à effectuer cette action");
        }
        
        try {
            $this->db->update('messages', ['archive' => 0], 'id = ?', [$messageId]);
            return true;
        } catch (Exception $e) {
            error_log('Erreur lors du désarchivage du message: ' . $e->getMessage());
            throw new Exception("Erreur lors du désarchivage du message");
        }
    }
    
    /**
     * Compte le nombre de messages non lus d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return int Nombre de messages non lus
     */
    public function countUnreadMessages($userId) {
        $sql = "SELECT COUNT(*) as count FROM messages 
                WHERE destinataire_id = ? AND lu = 0 AND archive = 0 AND supprime = 0";
        
        try {
            $result = $this->db->selectOne($sql, [$userId]);
            return $result ? intval($result['count']) : 0;
        } catch (Exception $e) {
            error_log('Erreur lors du comptage des messages non lus: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Marque tous les messages comme lus pour un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return bool Succès de l'opération
     */
    public function markAllAsRead($userId) {
        try {
            $this->db->update('messages', 
                              ['lu' => 1], 
                              'destinataire_id = ? AND lu = 0 AND archive = 0 AND supprime = 0', 
                              [$userId]);
            return true;
        } catch (Exception $e) {
            error_log('Erreur lors du marquage de tous les messages comme lus: ' . $e->getMessage());
            throw new Exception("Erreur lors du marquage de tous les messages comme lus");
        }
    }
    
    /**
     * Récupère les conversations (échanges de messages) entre deux utilisateurs
     * 
     * @param int $user1Id ID du premier utilisateur
     * @param int $user2Id ID du deuxième utilisateur
     * @return array Liste des messages de la conversation
     */
    public function getConversation($user1Id, $user2Id) {
        $sql = "SELECT m.*, 
                       e.prenom AS expediteur_prenom, e.nom AS expediteur_nom, e.email AS expediteur_email,
                       d.prenom AS destinataire_prenom, d.nom AS destinataire_nom, d.email AS destinataire_email,
                       CONCAT(e.prenom, ' ', e.nom) AS expediteur_nom,
                       CONCAT(d.prenom, ' ', d.nom) AS destinataire_nom
                FROM messages m
                JOIN utilisateurs e ON m.expediteur_id = e.id
                JOIN utilisateurs d ON m.destinataire_id = d.id
                WHERE ((m.expediteur_id = ? AND m.destinataire_id = ?) 
                      OR (m.expediteur_id = ? AND m.destinataire_id = ?))
                      AND m.supprime = 0
                ORDER BY m.date_envoi ASC";
        
        try {
            return $this->db->select($sql, [$user1Id, $user2Id, $user2Id, $user1Id]);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération de la conversation: ' . $e->getMessage());
            return [];
        }
    }
}