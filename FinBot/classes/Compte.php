<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\classes\Compte.php

require_once __DIR__ . '/Database.php';

class Compte {
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
     * Récupère tous les comptes d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Liste des comptes
     */
    public function getComptesByUser($userId) {
        return $this->db->select(
            "SELECT c.*, tc.nom as type_nom, tc.taux_interet, tc.plafond 
             FROM comptes c
             JOIN types_comptes tc ON c.type_compte_id = tc.id
             WHERE c.utilisateur_id = ?
             ORDER BY c.date_creation ASC",
            [$userId]
        );
    }
    
    /**
     * Récupère un compte spécifique
     * 
     * @param int $compteId ID du compte
     * @param int $userId ID de l'utilisateur (pour vérification)
     * @return array|false Données du compte ou false si non trouvé
     */
    public function getCompte($compteId, $userId = null) {
        $query = "SELECT c.*, tc.nom as type_nom, tc.taux_interet, tc.plafond 
                  FROM comptes c
                  JOIN types_comptes tc ON c.type_compte_id = tc.id
                  WHERE c.id = ?";
        $params = [$compteId];
        
        if ($userId !== null) {
            $query .= " AND c.utilisateur_id = ?";
            $params[] = $userId;
        }
        
        return $this->db->selectOne($query, $params);
    }

    /**
     * Récupère un compte par son ID pour un utilisateur spécifique
     * 
     * @param int $compteId ID du compte
     * @param int $userId ID de l'utilisateur (pour vérification de propriété)
     * @return array|null Détails du compte ou null si non trouvé
     */
    public function getCompteById($compteId, $userId) {
        $sql = "SELECT c.*, tc.nom as type_nom
                FROM comptes c
                JOIN types_comptes tc ON c.type_compte_id = tc.id
                WHERE c.id = ? AND c.utilisateur_id = ?";
        
        try {
            return $this->db->selectOne($sql, [$compteId, $userId]);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération du compte: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Crée un nouveau compte
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $typeCompte Type de compte (COURANT, LIVRET_A, etc.)
     * @param float $soldeInitial Solde initial
     * @return int|false ID du compte créé ou false si échec
     */
    public function createCompte($userId, $typeCompte, $soldeInitial = 0.0) {
        // Vérifier le type de compte
        $typeInfo = $this->db->selectOne(
            "SELECT * FROM types_comptes WHERE id = ?",
            [$typeCompte]
        );
        
        if (!$typeInfo || !$typeInfo['actif']) {
            throw new Exception("Type de compte non disponible");
        }
        
        // Vérifier le plafond pour les comptes d'épargne
        if ($typeCompte !== 'COURANT' && $typeInfo['plafond'] && $soldeInitial > $typeInfo['plafond']) {
            throw new Exception("Le dépôt initial dépasse le plafond autorisé de " . 
                                number_format($typeInfo['plafond'], 2, ',', ' ') . "€");
        }
        
        // Générer un numéro de compte unique
        $numeroCompte = $this->generateNumeroCompte();
        
        // Démarrer une transaction
        $this->db->beginTransaction();
        
        try {
            // Créer le compte
            $compteId = $this->db->insert('comptes', [
                'utilisateur_id' => $userId,
                'type_compte_id' => $typeCompte,
                'numero_compte' => $numeroCompte,
                'solde' => $soldeInitial,
                'date_creation' => date('Y-m-d H:i:s'),
                'statut' => 'ACTIF'
            ]);
            
            // Si c'est un dépôt initial, enregistrer la transaction
            if ($soldeInitial > 0) {
                $this->db->insert('transactions', [
                    'compte_id' => $compteId,
                    'type_transaction' => 'CREDIT',
                    'montant' => $soldeInitial,
                    'description' => 'Dépôt initial',
                    'date_transaction' => date('Y-m-d H:i:s')
                ]);
            }
            
            $this->db->commit();
            return $compteId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Effectue un virement entre comptes
     * 
     * @param int $compteSourceId ID du compte source
     * @param int $compteDestId ID du compte destination
     * @param float $montant Montant du virement
     * @param string $description Description du virement
     * @return bool Succès ou échec
     */
    public function effectuerVirement($compteSourceId, $compteDestId, $montant, $description = "Virement") {
        // Vérifications
        if ($montant <= 0) {
            throw new Exception("Le montant doit être positif");
        }
        
        if ($compteSourceId === $compteDestId) {
            throw new Exception("Impossible d'effectuer un virement vers le même compte");
        }
        
        // Récupérer les informations des comptes
        $compteSource = $this->getCompte($compteSourceId);
        $compteDest = $this->getCompte($compteDestId);
        
        if (!$compteSource || !$compteDest) {
            throw new Exception("Un des comptes n'existe pas");
        }
        
        if ($compteSource['statut'] !== 'ACTIF' || $compteDest['statut'] !== 'ACTIF') {
            throw new Exception("Un des comptes n'est pas actif");
        }
        
        if ($compteSource['solde'] < $montant) {
            throw new Exception("Solde insuffisant");
        }
        
        // Pour les comptes d'épargne, vérifier le plafond
        if ($compteDest['type_compte_id'] !== 'COURANT' && $compteDest['plafond']) {
            if ($compteDest['solde'] + $montant > $compteDest['plafond']) {
                throw new Exception("Ce virement dépasserait le plafond du compte de destination");
            }
        }
        
        // Démarrer une transaction
        $this->db->beginTransaction();
        
        try {
            // Mettre à jour les soldes
            $this->db->update('comptes',
                ['solde' => $compteSource['solde'] - $montant],
                'id = ?',
                [$compteSourceId]
            );
            
            $this->db->update('comptes',
                ['solde' => $compteDest['solde'] + $montant],
                'id = ?',
                [$compteDestId]
            );
            
            // Enregistrer les transactions
            $date = date('Y-m-d H:i:s');
            
            // Transaction débit
            $this->db->insert('transactions', [
                'compte_id' => $compteSourceId,
                'type_transaction' => 'VIREMENT',
                'montant' => -$montant,
                'description' => $description,
                'date_transaction' => $date,
                'compte_destinataire' => $compteDestId
            ]);
            
            // Transaction crédit
            $this->db->insert('transactions', [
                'compte_id' => $compteDestId,
                'type_transaction' => 'VIREMENT',
                'montant' => $montant,
                'description' => $description,
                'date_transaction' => $date,
                'compte_destinataire' => $compteSourceId
            ]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Calcule et applique les intérêts sur un compte d'épargne
     * 
     * @param int $compteId ID du compte
     * @return float Montant des intérêts appliqués
     */
    public function appliquerInterets($compteId) {
        $compte = $this->getCompte($compteId);
        
        if (!$compte) {
            throw new Exception("Compte introuvable");
        }
        
        if ($compte['type_compte_id'] === 'COURANT') {
            throw new Exception("Les intérêts ne s'appliquent pas aux comptes courants");
        }
        
        if ($compte['statut'] !== 'ACTIF') {
            throw new Exception("Les intérêts ne s'appliquent pas aux comptes inactifs");
        }
        
        // Calculer les intérêts (taux annuel / 12 pour intérêts mensuels)
        $tauxMensuel = $compte['taux_interet'] / 100 / 12;
        $interets = $compte['solde'] * $tauxMensuel;
        
        // Arrondir à 2 décimales
        $interets = round($interets, 2);
        
        if ($interets <= 0) {
            return 0;
        }
        
        // Vérifier le plafond
        if ($compte['plafond'] && $compte['solde'] + $interets > $compte['plafond']) {
            $interets = $compte['plafond'] - $compte['solde'];
            if ($interets <= 0) {
                return 0;
            }
        }
        
        // Démarrer une transaction
        $this->db->beginTransaction();
        
        try {
            // Mettre à jour le solde
            $this->db->update('comptes',
                ['solde' => $compte['solde'] + $interets],
                'id = ?',
                [$compteId]
            );
            
            // Enregistrer la transaction
            $this->db->insert('transactions', [
                'compte_id' => $compteId,
                'type_transaction' => 'INTERET',
                'montant' => $interets,
                'description' => 'Intérêts ' . $compte['type_nom'],
                'date_transaction' => date('Y-m-d H:i:s')
            ]);
            
            $this->db->commit();
            return $interets;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Ferme un compte
     * 
     * @param int $compteId ID du compte
     * @param int $compteTransfertId ID du compte où transférer le solde
     * @return bool Succès ou échec
     */
    public function fermerCompte($compteId, $compteTransfertId = null) {
        $compte = $this->getCompte($compteId);
        
        if (!$compte) {
            throw new Exception("Compte introuvable");
        }
        
        if ($compte['statut'] === 'CLOTURE') {
            throw new Exception("Ce compte est déjà clôturé");
        }
        
        // Si le solde n'est pas à zéro, il faut un compte de transfert
        if ($compte['solde'] > 0 && $compteTransfertId === null) {
            throw new Exception("Le solde n'est pas à zéro, un compte de transfert est nécessaire");
        }
        
        // Démarrer une transaction
        $this->db->beginTransaction();
        
        try {
            // Si besoin de transférer le solde
            if ($compte['solde'] > 0) {
                $compteDest = $this->getCompte($compteTransfertId);
                
                if (!$compteDest) {
                    throw new Exception("Compte de transfert introuvable");
                }
                
                if ($compteDest['statut'] !== 'ACTIF') {
                    throw new Exception("Le compte de transfert n'est pas actif");
                }
                
                // Pour les comptes d'épargne, vérifier le plafond
                if ($compteDest['type_compte_id'] !== 'COURANT' && $compteDest['plafond']) {
                    if ($compteDest['solde'] + $compte['solde'] > $compteDest['plafond']) {
                        throw new Exception("Ce transfert dépasserait le plafond du compte de destination");
                    }
                }
                
                // Transférer le solde
                $this->db->update('comptes',
                    ['solde' => $compteDest['solde'] + $compte['solde']],
                    'id = ?',
                    [$compteTransfertId]
                );
                
                // Enregistrer les transactions
                $date = date('Y-m-d H:i:s');
                $description = "Transfert clôture de compte";
                
                $this->db->insert('transactions', [
                    'compte_id' => $compteId,
                    'type_transaction' => 'VIREMENT',
                    'montant' => -$compte['solde'],
                    'description' => $description,
                    'date_transaction' => $date,
                    'compte_destinataire' => $compteTransfertId
                ]);
                
                $this->db->insert('transactions', [
                    'compte_id' => $compteTransfertId,
                    'type_transaction' => 'VIREMENT',
                    'montant' => $compte['solde'],
                    'description' => $description,
                    'date_transaction' => $date,
                    'compte_destinataire' => $compteId
                ]);
            }
            
            // Clôturer le compte
            $this->db->update('comptes',
                [
                    'statut' => 'CLOTURE',
                    'solde' => 0
                ],
                'id = ?',
                [$compteId]
            );
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Génère un numéro de compte unique
     * 
     * @return string Numéro de compte au format IBAN
     */
    private function generateNumeroCompte() {
        // Format FR76 + 16 chiffres aléatoires
        $numero = 'FR76';
        for ($i = 0; $i < 16; $i++) {
            $numero .= mt_rand(0, 9);
        }
        
        // Vérifier si ce numéro existe déjà
        $existingAccount = $this->db->selectOne(
            "SELECT id FROM comptes WHERE numero_compte = ?",
            [$numero]
        );
        
        if ($existingAccount) {
            // Appel récursif pour générer un nouveau numéro
            return $this->generateNumeroCompte();
        }
        
        return $numero;
    }
    
    /**
     * Récupère les types de comptes disponibles
     * 
     * @return array Liste des types de comptes
     */
    public function getTypesComptes() {
        return $this->db->select(
            "SELECT * FROM types_comptes WHERE actif = 1"
        );
    }

    /**
     * Crée un nouveau compte épargne pour un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $typeCompte Type de compte épargne (LIVRET_A, LDDS, PEL)
     * @param float $depotInitial Montant du dépôt initial
     * @param int $compteSourceId ID du compte source pour le dépôt initial
     * @return bool|array Retourne les détails du compte créé ou false en cas d'échec
     */
    public function creerCompteEpargne($userId, $typeCompte, $depotInitial, $compteSourceId) {
        try {
            // Connexion à la base de données
            $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Vérifier si le type de compte est valide
            $stmt = $pdo->prepare('SELECT id, taux_interet, plafond FROM types_comptes WHERE id = ?');
            $stmt->execute([$typeCompte]);
            $typeCompteInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$typeCompteInfo) {
                throw new Exception('Type de compte invalide.');
            }
            
            // Vérifier si le compte source existe et appartient à l'utilisateur
            $stmt = $pdo->prepare('SELECT id, solde FROM comptes WHERE id = ? AND utilisateur_id = ?');
            $stmt->execute([$compteSourceId, $userId]);
            $compteSource = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$compteSource) {
                throw new Exception('Compte source invalide.');
            }
            
            // Vérifier si le compte source a suffisamment de fonds
            if ($compteSource['solde'] < $depotInitial) {
                throw new Exception('Solde insuffisant sur le compte source.');
            }
            
            // Vérifier si le dépôt respecte le plafond du compte
            if ($typeCompteInfo['plafond'] && $depotInitial > $typeCompteInfo['plafond']) {
                throw new Exception('Le dépôt dépasse le plafond autorisé pour ce type de compte.');
            }
            
            // Générer un numéro de compte
            $numeroCompte = 'FR76' . str_pad(mt_rand(1000000, 9999999), 10, '0', STR_PAD_LEFT) . str_pad($userId, 10, '0', STR_PAD_LEFT);
            
            // Démarrer une transaction
            $pdo->beginTransaction();
            
            // 1. Créer le nouveau compte épargne
            $stmt = $pdo->prepare('INSERT INTO comptes (utilisateur_id, numero_compte, type_compte_id, solde, date_creation) 
                                  VALUES (?, ?, ?, ?, NOW())');
            $stmt->execute([$userId, $numeroCompte, $typeCompte, $depotInitial]);
            $nouveauCompteId = $pdo->lastInsertId();
            
            // 2. Débiter le compte source
            $stmt = $pdo->prepare('UPDATE comptes SET solde = solde - ?, date_derniere_operation = NOW() WHERE id = ?');
            $stmt->execute([$depotInitial, $compteSourceId]);
            
            // 3. Enregistrer la transaction de débit sur le compte source
            $stmt = $pdo->prepare('INSERT INTO transactions (compte_id, type_transaction, montant, date_transaction, description, categorie) 
                                  VALUES (?, "DEBIT", ?, NOW(), ?, "Épargne")');
            $stmt->execute([$compteSourceId, $depotInitial, 'Ouverture compte ' . $typeCompte]);
            
            // 4. Enregistrer la transaction de crédit sur le nouveau compte
            $stmt = $pdo->prepare('INSERT INTO transactions (compte_id, type_transaction, montant, date_transaction, description, categorie) 
                                  VALUES (?, "CREDIT", ?, NOW(), ?, "Dépôt initial")');
            $stmt->execute([$nouveauCompteId, $depotInitial, 'Dépôt initial']);
            
            // Valider la transaction
            $pdo->commit();
            
            // Récupérer et retourner les détails du nouveau compte
            $stmt = $pdo->prepare('SELECT * FROM comptes WHERE id = ?');
            $stmt->execute([$nouveauCompteId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            // En cas d'erreur, annuler la transaction
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception('Erreur lors de la création du compte épargne: ' . $e->getMessage());
        }
    }
}