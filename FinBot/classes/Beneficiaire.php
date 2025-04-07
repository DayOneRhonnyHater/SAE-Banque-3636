<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\classes\Beneficiaire.php

require_once __DIR__ . '/Database.php';

/**
 * Classe permettant de gérer les bénéficiaires de virements
 */
class Beneficiaire {
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
     * @return Beneficiaire Instance unique
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Récupère tous les bénéficiaires d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Liste des bénéficiaires
     */
    public function getBeneficiairesByUser($userId) {
        $sql = "SELECT * FROM beneficiaires WHERE utilisateur_id = ? ORDER BY nom ASC";
        
        try {
            return $this->db->select($sql, [$userId]);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des bénéficiaires: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère un bénéficiaire par son ID
     * 
     * @param int $id ID du bénéficiaire
     * @param int $userId ID de l'utilisateur (pour vérification)
     * @return array|null Données du bénéficiaire ou null si non trouvé
     */
    public function getBeneficiaireById($id, $userId) {
        $sql = "SELECT * FROM beneficiaires WHERE id = ? AND utilisateur_id = ?";
        
        try {
            return $this->db->selectOne($sql, [$id, $userId]);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération du bénéficiaire: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Ajoute un nouveau bénéficiaire
     * 
     * @param array $data Données du bénéficiaire (nom, iban, bic, utilisateur_id)
     * @return int|false ID du bénéficiaire créé ou false en cas d'échec
     */
    public function createBeneficiaire($data) {
        // Validation des données
        if (empty($data['nom']) || empty($data['iban']) || empty($data['utilisateur_id'])) {
            throw new Exception("Données de bénéficiaire incomplètes");
        }
        
        // Nettoyer et valider l'IBAN
        $data['iban'] = $this->formatIban($data['iban']);
        if (!$this->validateIban($data['iban'])) {
            throw new Exception("IBAN invalide");
        }
        
        // Vérifier si ce bénéficiaire existe déjà pour cet utilisateur
        $existingBeneficiaire = $this->db->selectOne(
            "SELECT id FROM beneficiaires WHERE iban = ? AND utilisateur_id = ?",
            [$data['iban'], $data['utilisateur_id']]
        );
        
        if ($existingBeneficiaire) {
            throw new Exception("Un bénéficiaire avec cet IBAN existe déjà");
        }
        
        // Insertion du bénéficiaire
        try {
            $id = $this->db->insert('beneficiaires', [
                'nom' => $data['nom'],
                'iban' => $data['iban'],
                'bic' => $data['bic'] ?? null,
                'email' => $data['email'] ?? null,
                'telephone' => $data['telephone'] ?? null,
                'adresse' => $data['adresse'] ?? null,
                'utilisateur_id' => $data['utilisateur_id'],
                'date_creation' => date('Y-m-d H:i:s'),
                'actif' => 1
            ]);
            
            return $id;
        } catch (Exception $e) {
            error_log('Erreur lors de la création du bénéficiaire: ' . $e->getMessage());
            throw new Exception("Erreur lors de la création du bénéficiaire");
        }
    }
    
    /**
     * Met à jour un bénéficiaire existant
     * 
     * @param int $id ID du bénéficiaire
     * @param array $data Données à mettre à jour
     * @param int $userId ID de l'utilisateur (pour vérification)
     * @return bool Succès de l'opération
     */
    public function updateBeneficiaire($id, $data, $userId) {
        // Vérifier que le bénéficiaire appartient à l'utilisateur
        $beneficiaire = $this->getBeneficiaireById($id, $userId);
        if (!$beneficiaire) {
            throw new Exception("Bénéficiaire introuvable ou vous n'avez pas les droits nécessaires");
        }
        
        // Si l'IBAN est modifié, le valider
        if (isset($data['iban'])) {
            $data['iban'] = $this->formatIban($data['iban']);
            if (!$this->validateIban($data['iban'])) {
                throw new Exception("IBAN invalide");
            }
            
            // Vérifier si un autre bénéficiaire a déjà cet IBAN
            $existingBeneficiaire = $this->db->selectOne(
                "SELECT id FROM beneficiaires WHERE iban = ? AND utilisateur_id = ? AND id != ?",
                [$data['iban'], $userId, $id]
            );
            
            if ($existingBeneficiaire) {
                throw new Exception("Un autre bénéficiaire avec cet IBAN existe déjà");
            }
        }
        
        // Préparation des données pour la mise à jour
        $updateData = [];
        
        if (isset($data['nom'])) $updateData['nom'] = $data['nom'];
        if (isset($data['iban'])) $updateData['iban'] = $data['iban'];
        if (isset($data['bic'])) $updateData['bic'] = $data['bic'];
        if (isset($data['email'])) $updateData['email'] = $data['email'];
        if (isset($data['telephone'])) $updateData['telephone'] = $data['telephone'];
        if (isset($data['adresse'])) $updateData['adresse'] = $data['adresse'];
        if (isset($data['actif'])) $updateData['actif'] = $data['actif'] ? 1 : 0;
        
        if (empty($updateData)) {
            // Rien à mettre à jour
            return true;
        }
        
        // Mise à jour du bénéficiaire
        try {
            $this->db->update('beneficiaires', $updateData, 'id = ? AND utilisateur_id = ?', [$id, $userId]);
            return true;
        } catch (Exception $e) {
            error_log('Erreur lors de la mise à jour du bénéficiaire: ' . $e->getMessage());
            throw new Exception("Erreur lors de la mise à jour du bénéficiaire");
        }
    }
    
    /**
     * Supprime un bénéficiaire
     * 
     * @param int $id ID du bénéficiaire
     * @param int $userId ID de l'utilisateur (pour vérification)
     * @return bool Succès de l'opération
     */
    public function deleteBeneficiaire($id, $userId) {
        // Vérifier que le bénéficiaire appartient à l'utilisateur
        $beneficiaire = $this->getBeneficiaireById($id, $userId);
        if (!$beneficiaire) {
            throw new Exception("Bénéficiaire introuvable ou vous n'avez pas les droits nécessaires");
        }
        
        try {
            // Vérifier si le bénéficiaire a des transactions
            $transactionCount = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM transactions WHERE beneficiaire_id = ?",
                [$id]
            );
            
            if ($transactionCount && $transactionCount['count'] > 0) {
                // Si le bénéficiaire a des transactions, on le désactive au lieu de le supprimer
                return $this->updateBeneficiaire($id, ['actif' => 0], $userId);
            } else {
                // Sinon, on peut le supprimer définitivement
                $this->db->delete('beneficiaires', 'id = ? AND utilisateur_id = ?', [$id, $userId]);
                return true;
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la suppression du bénéficiaire: ' . $e->getMessage());
            throw new Exception("Erreur lors de la suppression du bénéficiaire");
        }
    }
    
    /**
     * Recherche des bénéficiaires
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $search Terme de recherche
     * @return array Liste des bénéficiaires correspondants
     */
    public function searchBeneficiaires($userId, $search) {
        $sql = "SELECT * FROM beneficiaires 
                WHERE utilisateur_id = ? 
                AND (nom LIKE ? OR iban LIKE ? OR email LIKE ?) 
                AND actif = 1
                ORDER BY nom ASC";
        
        $searchTerm = '%' . $search . '%';
        
        try {
            return $this->db->select($sql, [$userId, $searchTerm, $searchTerm, $searchTerm]);
        } catch (Exception $e) {
            error_log('Erreur lors de la recherche de bénéficiaires: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Vérifie si un IBAN est valide
     * 
     * @param string $iban IBAN à vérifier
     * @return bool Validité de l'IBAN
     */
    public function validateIban($iban) {
        // Retirer les espaces de l'IBAN
        $iban = str_replace(' ', '', $iban);
        
        // Vérifier la longueur minimale (au moins 15 caractères)
        if (strlen($iban) < 15 || strlen($iban) > 34) {
            return false;
        }
        
        // Vérifier le format de base (commence par deux lettres suivies de chiffres)
        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/', $iban)) {
            return false;
        }
        
        // Pour une validation plus complète, il existe des algorithmes spécifiques
        // que vous pourriez implémenter, mais cette vérification de base suffit
        // pour la plupart des cas d'utilisation.
        
        return true;
    }
    
    /**
     * Formate un IBAN pour l'affichage et le stockage
     * 
     * @param string $iban IBAN à formater
     * @return string IBAN formaté
     */
    public function formatIban($iban) {
        // Retirer tous les caractères non alphanumériques
        $iban = preg_replace('/[^A-Z0-9]/', '', strtoupper($iban));
        
        // Formater par blocs de 4 caractères pour l'affichage
        $formattedIban = '';
        for ($i = 0; $i < strlen($iban); $i += 4) {
            $formattedIban .= substr($iban, $i, 4) . ' ';
        }
        
        return trim($formattedIban);
    }
    
    /**
     * Récupère les bénéficiaires favoris d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $limit Nombre maximum de bénéficiaires à récupérer
     * @return array Liste des bénéficiaires favoris
     */
    public function getFavorites($userId, $limit = 5) {
        $sql = "SELECT b.*, COUNT(t.id) as nb_transactions 
                FROM beneficiaires b
                LEFT JOIN transactions t ON b.id = t.beneficiaire_id
                WHERE b.utilisateur_id = ? AND b.actif = 1
                GROUP BY b.id
                ORDER BY nb_transactions DESC, b.nom ASC
                LIMIT ?";
        
        try {
            return $this->db->select($sql, [$userId, $limit]);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des bénéficiaires favoris: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère le nombre de bénéficiaires d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return int Nombre de bénéficiaires
     */
    public function countBeneficiaires($userId) {
        $sql = "SELECT COUNT(*) as count FROM beneficiaires WHERE utilisateur_id = ? AND actif = 1";
        
        try {
            $result = $this->db->selectOne($sql, [$userId]);
            return $result ? $result['count'] : 0;
        } catch (Exception $e) {
            error_log('Erreur lors du comptage des bénéficiaires: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Vérifie si un utilisateur peut faire un virement à un bénéficiaire
     * 
     * @param int $beneficiaireId ID du bénéficiaire
     * @param int $userId ID de l'utilisateur
     * @return bool Possibilité de faire un virement
     */
    public function canTransferTo($beneficiaireId, $userId) {
        $beneficiaire = $this->getBeneficiaireById($beneficiaireId, $userId);
        
        if (!$beneficiaire) {
            return false;
        }
        
        return $beneficiaire['actif'] == 1;
    }
}