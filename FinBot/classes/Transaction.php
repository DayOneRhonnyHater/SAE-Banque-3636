<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\classes\Transaction.php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Compte.php';
require_once __DIR__ . '/Beneficiaire.php';
require_once __DIR__ . '/../includes/notification_functions.php';

class Transaction {
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
     * Crée une nouvelle transaction
     * 
     * @param array $data Données de la transaction
     * @return int|bool ID de la transaction créée ou false en cas d'erreur
     */
    public function createTransaction($data) {
        try {
            // Vérifier que les données minimales sont présentes
            if (!isset($data['compte_id']) || !isset($data['type']) || !isset($data['montant']) || !isset($data['description'])) {
                throw new Exception('Données de transaction incomplètes');
            }
            
            // Vérifications supplémentaires si nécessaire
            if (!in_array($data['type'], ['CREDIT', 'DEBIT', 'VIREMENT', 'PRELEVEMENT'])) {
                throw new Exception('Type de transaction invalide');
            }
            
            // Préparation des données
            $transactionData = [
                'compte_id' => $data['compte_id'],
                'type' => $data['type'],
                'montant' => $data['montant'],
                'description' => $data['description'],
                'date_transaction' => $data['date_transaction'] ?? date('Y-m-d H:i:s')
            ];
            
            // Ajout des champs optionnels s'ils sont présents
            if (isset($data['details'])) $transactionData['details'] = $data['details'];
            if (isset($data['reference'])) $transactionData['reference'] = $data['reference'];
            
            // Insertion dans la base de données
            $result = $this->db->insert('transactions', $transactionData);
            
            // Mise à jour du solde du compte
            $this->updateSoldeCompte($data['compte_id'], $data['type'], abs($data['montant']));
            
            return $result;
        } catch (Exception $e) {
            error_log('Erreur lors de la création de la transaction: ' . $e->getMessage());
            throw new Exception('Erreur lors de la création de la transaction: ' . $e->getMessage());
        }
    }
    
    /**
     * Méthode privée pour mettre à jour le solde du compte après une transaction
     * 
     * @param int $compteId ID du compte
     * @param string $type Type de transaction (CREDIT ou DEBIT)
     * @param float $montant Montant de la transaction
     */
    private function updateSoldeCompte($compteId, $type, $montant) {
        try {
            // Si c'est un crédit, on ajoute au solde, si c'est un débit, on soustrait
            $operator = ($type === 'CREDIT') ? '+' : '-';
            
            $this->db->execute(
                "UPDATE comptes SET solde = solde $operator ? WHERE id = ?",
                [$montant, $compteId]
            );
        } catch (Exception $e) {
            error_log('Erreur lors de la mise à jour du solde: ' . $e->getMessage());
        }
    }

    /**
     * Met à jour le solde d'un compte en ajoutant le montant spécifié (positif ou négatif)
     * 
     * @param int $compteId ID du compte
     * @param float $montant Montant à ajouter (positif) ou soustraire (négatif)
     */
    private function updateSoldeCompteDirectement($compteId, $montant) {
        try {
            $this->db->execute(
                "UPDATE comptes SET solde = solde + ? WHERE id = ?",
                [$montant, $compteId]
            );
        } catch (Exception $e) {
            error_log('Erreur lors de la mise à jour du solde: ' . $e->getMessage());
            throw new Exception("Erreur lors de la mise à jour du solde: " . $e->getMessage());
        }
    }
    
    /**
     * Récupère les transactions d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param array $filtres Filtres (dates, type, montant, etc.)
     * @param int $limit Nombre maximum de transactions
     * @param int $offset Décalage pour pagination
     * @return array Liste des transactions
     */
    public function getTransactionsByUser($userId, $filtres = [], $limit = 50, $offset = 0) {
        $query = "SELECT t.*, c.numero_compte, c.type_compte_id,
                         c2.numero_compte as compte_destinataire_numero
                  FROM transactions t
                  JOIN comptes c ON t.compte_id = c.id
                  LEFT JOIN comptes c2 ON t.compte_destinataire = c2.id
                  WHERE c.utilisateur_id = ?";
        $params = [$userId];
        
        // Application des filtres
        if (isset($filtres['date_debut']) && $filtres['date_debut']) {
            $query .= " AND t.date_transaction >= ?";
            $params[] = $filtres['date_debut'] . ' 00:00:00';
        }
        
        if (isset($filtres['date_fin']) && $filtres['date_fin']) {
            $query .= " AND t.date_transaction <= ?";
            $params[] = $filtres['date_fin'] . ' 23:59:59';
        }
        
        if (isset($filtres['type']) && $filtres['type']) {
            $query .= " AND t.type_transaction = ?";
            $params[] = $filtres['type'];
        }
        
        if (isset($filtres['montant_min']) && is_numeric($filtres['montant_min'])) {
            $query .= " AND ABS(t.montant) >= ?";
            $params[] = abs(floatval($filtres['montant_min']));
        }
        
        if (isset($filtres['montant_max']) && is_numeric($filtres['montant_max'])) {
            $query .= " AND ABS(t.montant) <= ?";
            $params[] = abs(floatval($filtres['montant_max']));
        }
        
        if (isset($filtres['compte_id']) && $filtres['compte_id']) {
            $query .= " AND t.compte_id = ?";
            $params[] = $filtres['compte_id'];
        }
        
        if (isset($filtres['recherche']) && $filtres['recherche']) {
            $query .= " AND (t.description LIKE ? OR c.numero_compte LIKE ?)";
            $searchTerm = '%' . $filtres['recherche'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Tri par date décroissante
        $query .= " ORDER BY t.date_transaction DESC";
        
        // Pagination
        if ($limit > 0) {
            $query .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        return $this->db->select($query, $params);
    }
    
    /**
     * Récupère les transactions d'un compte spécifique
     * 
     * @param int $compteId ID du compte
     * @param array $filtres Filtres (dates, type, montant, etc.)
     * @param int $limit Nombre maximum de transactions
     * @param int $offset Décalage pour pagination
     * @return array Liste des transactions
     */
    public function getTransactionsByCompte($compteId, $filtres = [], $limit = 50, $offset = 0) {
        $query = "SELECT t.*, c.numero_compte, c.type_compte_id,
                         c2.numero_compte as compte_destinataire_numero
                  FROM transactions t
                  JOIN comptes c ON t.compte_id = c.id
                  LEFT JOIN comptes c2 ON t.compte_destinataire = c2.id
                  WHERE t.compte_id = ?";
        $params = [$compteId];
        
        // Application des filtres - même logique que la fonction précédente
        if (isset($filtres['date_debut']) && $filtres['date_debut']) {
            $query .= " AND t.date_transaction >= ?";
            $params[] = $filtres['date_debut'] . ' 00:00:00';
        }
        
        if (isset($filtres['date_fin']) && $filtres['date_fin']) {
            $query .= " AND t.date_transaction <= ?";
            $params[] = $filtres['date_fin'] . ' 23:59:59';
        }
        
        if (isset($filtres['type']) && $filtres['type']) {
            $query .= " AND t.type_transaction = ?";
            $params[] = $filtres['type'];
        }
        
        if (isset($filtres['recherche']) && $filtres['recherche']) {
            $query .= " AND t.description LIKE ?";
            $params[] = '%' . $filtres['recherche'] . '%';
        }
        
        // Tri par date décroissante
        $query .= " ORDER BY t.date_transaction DESC";
        
        // Pagination
        if ($limit > 0) {
            $query .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        return $this->db->select($query, $params);
    }
    
    /**
     * Récupère une transaction spécifique
     * 
     * @param int $transactionId ID de la transaction
     * @param int $userId ID de l'utilisateur (pour vérification)
     * @return array|false Données de la transaction ou false si non trouvée
     */
    public function getTransaction($transactionId, $userId = null) {
        $query = "SELECT t.*, c.numero_compte, c.type_compte_id, c.utilisateur_id,
                         c2.numero_compte as compte_destinataire_numero
                  FROM transactions t
                  JOIN comptes c ON t.compte_id = c.id
                  LEFT JOIN comptes c2 ON t.compte_destinataire = c2.id
                  WHERE t.id = ?";
        $params = [$transactionId];
        
        if ($userId !== null) {
            $query .= " AND c.utilisateur_id = ?";
            $params[] = $userId;
        }
        
        return $this->db->selectOne($query, $params);
    }
    
    /**
     * Récupère les statistiques de transactions d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $periode Période (JOUR, SEMAINE, MOIS, ANNEE)
     * @return array Statistiques de transactions
     */
    public function getStatistiques($userId, $periode = 'MOIS') {
        $dateDebut = '';
        
        switch ($periode) {
            case 'JOUR':
                $dateDebut = date('Y-m-d 00:00:00');
                break;
            case 'SEMAINE':
                $dateDebut = date('Y-m-d 00:00:00', strtotime('-7 days'));
                break;
            case 'MOIS':
                $dateDebut = date('Y-m-01 00:00:00');
                break;
            case 'ANNEE':
                $dateDebut = date('Y-01-01 00:00:00');
                break;
            default:
                $dateDebut = date('Y-m-01 00:00:00');
                break;
        }
        
        // Requête pour calculer les totaux
        $query = "SELECT 
                    SUM(CASE WHEN t.montant > 0 THEN t.montant ELSE 0 END) as total_entrees,
                    SUM(CASE WHEN t.montant < 0 THEN ABS(t.montant) ELSE 0 END) as total_sorties,
                    COUNT(*) as nombre_transactions
                  FROM transactions t
                  JOIN comptes c ON t.compte_id = c.id
                  WHERE c.utilisateur_id = ? AND t.date_transaction >= ?";
        
        $stats = $this->db->selectOne($query, [$userId, $dateDebut]);
        
        // Requête pour les transactions par type
        $queryTypes = "SELECT 
                         t.type_transaction as type,
                         COUNT(*) as nombre,
                         SUM(ABS(t.montant)) as total
                       FROM transactions t
                       JOIN comptes c ON t.compte_id = c.id
                       WHERE c.utilisateur_id = ? AND t.date_transaction >= ?
                       GROUP BY t.type_transaction";
        
        $parType = $this->db->select($queryTypes, [$userId, $dateDebut]);
        
        // Requête pour les transactions par jour
        $queryJours = "SELECT 
                         DATE(t.date_transaction) as jour,
                         SUM(CASE WHEN t.montant > 0 THEN t.montant ELSE 0 END) as entrees,
                         SUM(CASE WHEN t.montant < 0 THEN ABS(t.montant) ELSE 0 END) as sorties
                       FROM transactions t
                       JOIN comptes c ON t.compte_id = c.id
                       WHERE c.utilisateur_id = ? AND t.date_transaction >= ?
                       GROUP BY DATE(t.date_transaction)
                       ORDER BY jour";
        
        $parJour = $this->db->select($queryJours, [$userId, $dateDebut]);
        
        return [
            'resume' => $stats,
            'par_type' => $parType,
            'par_jour' => $parJour,
            'periode' => $periode
        ];
    }
    
    /**
     * Récupère les types de transactions disponibles
     * 
     * @return array Types de transactions
     */
    public function getTypesTransactions() {
        return ['CREDIT', 'DEBIT', 'VIREMENT', 'INTERET', 'FRAIS'];
    }
    
    /**
     * Exporte les transactions au format CSV
     * 
     * @param int $userId ID de l'utilisateur
     * @param array $filtres Filtres pour les transactions
     * @return string Contenu CSV
     */
    public function exporterCSV($userId, $filtres = []) {
        $transactions = $this->getTransactionsByUser($userId, $filtres, 1000, 0);
        
        if (empty($transactions)) {
            return false;
        }
        
        // Ouvrir un flux de mémoire
        $output = fopen('php://temp', 'r+');
        
        // UTF-8 BOM pour Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // En-têtes
        fputcsv($output, [
            'Date',
            'Type',
            'Description',
            'Montant',
            'Numéro de compte',
            'Compte destinataire'
        ], ';');
        
        // Données
        foreach ($transactions as $transaction) {
            fputcsv($output, [
                date('d/m/Y H:i', strtotime($transaction['date_transaction'])),
                $transaction['type_transaction'],
                $transaction['description'],
                number_format($transaction['montant'], 2, ',', ' ') . ' €',
                $transaction['numero_compte'],
                $transaction['compte_destinataire_numero'] ?? ''
            ], ';');
        }
        
        // Rembobiner et lire le contenu
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Méthode pour récupérer les transactions avec des filtres
     */
    public function getTransactionsByFilters($params = []) {
        $compte_id = $params['compte_id'] ?? null;
        $utilisateur_id = $params['utilisateur_id'] ?? null;
        $date_debut = $params['date_debut'] ?? null;
        $date_fin = $params['date_fin'] ?? null;
        $type = $params['type'] ?? null;
        $montant_min = $params['montant_min'] ?? null;
        $montant_max = $params['montant_max'] ?? null;
        $search = $params['search'] ?? null;
        
        $sql = "SELECT t.*, c.numero_compte 
                FROM transactions t
                JOIN comptes c ON t.compte_id = c.id
                WHERE 1=1";
        
        $sqlParams = [];
        
        if ($compte_id) {
            $sql .= " AND t.compte_id = ?";
            $sqlParams[] = $compte_id;
        }
        
        if ($utilisateur_id) {
            $sql .= " AND c.utilisateur_id = ?";
            $sqlParams[] = $utilisateur_id;
        }
        
        if ($date_debut) {
            $sql .= " AND DATE(t.date_transaction) >= ?";
            $sqlParams[] = $date_debut;
        }
        
        if ($date_fin) {
            $sql .= " AND DATE(t.date_transaction) <= ?";
            $sqlParams[] = $date_fin;
        }
        
        if ($type) {
            $sql .= " AND t.type_transaction = ?"; // Utiliser type_transaction au lieu de type
            $sqlParams[] = $type;
        }
        
        if ($montant_min !== '' && $montant_min !== null) {
            $sql .= " AND ABS(t.montant) >= ?";
            $sqlParams[] = $montant_min;
        }
        
        if ($montant_max !== '' && $montant_max !== null) {
            $sql .= " AND ABS(t.montant) <= ?";
            $sqlParams[] = $montant_max;
        }
        
        if ($search) {
            $sql .= " AND (t.description LIKE ? OR c.numero_compte LIKE ?)";
            $searchParam = "%$search%";
            $sqlParams[] = $searchParam;
            $sqlParams[] = $searchParam;
        }
        
        $sql .= " ORDER BY t.date_transaction DESC";
        
        try {
            return $this->db->select($sql, $sqlParams);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des transactions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Méthode pour récupérer les données des transactions groupées par jour
     */
    public function getTransactionsGroupedByDay($params = []) {
        // Paramètres par défaut
        $userId = $params['user_id'] ?? null;
        $compteId = $params['compte_id'] ?? null;
        $dateDebut = $params['date_debut'] ?? null;
        $dateFin = $params['date_fin'] ?? null;
        $type = $params['type'] ?? null;
        
        // Requête pour les données du graphique
        $sql = "SELECT 
                    DATE(t.date_transaction) as date,
                    SUM(CASE WHEN t.type IN ('CREDIT', 'VIREMENT_RECU', 'INTERET') THEN t.montant ELSE 0 END) as credit,
                    SUM(CASE WHEN t.type IN ('DEBIT', 'VIREMENT', 'PRELEVEMENT', 'FRAIS') THEN t.montant ELSE 0 END) as debit
                FROM transactions t
                JOIN comptes c ON t.compte_id = c.id
                JOIN utilisateurs_comptes uc ON c.id = uc.compte_id
                WHERE uc.utilisateur_id = ?";
        
        $sqlParams = [$userId];
        
        // Filtres additionnels
        if ($compteId) {
            $sql .= " AND t.compte_id = ?";
            $sqlParams[] = $compteId;
        }
        
        if ($dateDebut) {
            $sql .= " AND t.date_transaction >= ?";
            $sqlParams[] = $dateDebut;
        }
        
        if ($dateFin) {
            $sql .= " AND t.date_transaction <= ?";
            $sqlParams[] = $dateFin . ' 23:59:59';
        }
        
        if ($type) {
            $sql .= " AND t.type = ?";
            $sqlParams[] = $type;
        }
        
        $sql .= " GROUP BY DATE(t.date_transaction) ORDER BY DATE(t.date_transaction)";
        
        try {
            return $this->db->select($sql, $sqlParams);
        } catch (Exception $e) {
            // Log de l'erreur
            error_log('Erreur lors de la récupération des données du graphique: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les virements récents d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $limit Nombre maximal de virements à récupérer
     * @return array Liste des virements
     */
    public function getRecentTransfersByUser($userId, $limit = 5) {
        $sql = "SELECT t.id, t.date_transaction, t.montant, t.description, 
                       c_source.numero_compte AS compte_source,
                       c_dest.numero_compte AS compte_destinataire,
                       b.nom AS beneficiaire_nom
                FROM transactions t
                JOIN comptes c_source ON t.compte_id = c_source.id
                JOIN utilisateurs_comptes uc ON c_source.id = uc.compte_id
                LEFT JOIN comptes c_dest ON t.compte_destinataire_id = c_dest.id
                LEFT JOIN beneficiaires b ON t.beneficiaire_id = b.id
                WHERE uc.utilisateur_id = ? 
                  AND t.type = 'VIREMENT'
                ORDER BY t.date_transaction DESC
                LIMIT ?";
        
        try {
            return $this->db->select($sql, [$userId, $limit]);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des virements récents: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère une transaction spécifique
     * 
     * @param int $transactionId ID de la transaction
     * @param int $userId ID de l'utilisateur (pour vérification de propriété)
     * @return array|null Détails de la transaction ou null si non trouvée
     */
    public function getTransactionById($transactionId, $userId) {
        $sql = "SELECT t.*
                FROM transactions t
                JOIN comptes c ON t.compte_id = c.id
                JOIN utilisateurs_comptes uc ON c.id = uc.compte_id
                WHERE t.id = ? AND uc.utilisateur_id = ?";
        
        try {
            return $this->db->selectOne($sql, [$transactionId, $userId]);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération de la transaction: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Crée un virement interne
     */
    public function createInternalTransfer($compteSourceId, $compteDestinationId, $montant, $description, $userId) {
        // Vérifier que les comptes appartiennent à l'utilisateur
        if (!$this->verifyAccountOwnership($compteSourceId, $userId) || 
            !$this->verifyAccountOwnership($compteDestinationId, $userId)) {
            throw new Exception("Vous n'avez pas l'autorisation d'effectuer cette opération");
        }
        
        // Vérifier le solde disponible
        if (!$this->checkSufficientBalance($compteSourceId, $montant)) {
            throw new Exception("Solde insuffisant pour effectuer ce virement");
        }
        
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        
        try {
            // Enregistrer le débit sur le compte source
            $queryDebit = "INSERT INTO transactions 
                          (compte_id, type_transaction, montant, description, compte_destinataire, date_transaction) 
                          VALUES (?, 'VIREMENT', ?, ?, ?, NOW())";
            
            $stmtDebit = $db->prepare($queryDebit);
            $stmtDebit->execute([
                $compteSourceId,
                -$montant, // Montant négatif pour le débit
                $description,
                $this->getNumeroCompte($compteDestinationId)
            ]);
            
            // Enregistrer le crédit sur le compte destination
            $queryCredit = "INSERT INTO transactions 
                           (compte_id, type_transaction, montant, description, compte_destinataire, date_transaction) 
                           VALUES (?, 'VIREMENT', ?, ?, ?, NOW())";
            
            $stmtCredit = $db->prepare($queryCredit);
            $stmtCredit->execute([
                $compteDestinationId,
                $montant, // Montant positif pour le crédit
                $description,
                $this->getNumeroCompte($compteSourceId)
            ]);
            
            // Mettre à jour les soldes
            $this->updateAccountBalance($compteSourceId, -$montant);
            $this->updateAccountBalance($compteDestinationId, $montant);
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw new Exception("Erreur lors du virement : " . $e->getMessage());
        }
    }

    /**
     * Crée un virement externe
     */
    public function createExternalTransfer($compteSourceId, $beneficiaireId, $montant, $description, $userId) {
        // Vérifier que le compte appartient à l'utilisateur
        if (!$this->verifyAccountOwnership($compteSourceId, $userId)) {
            throw new Exception("Vous n'avez pas l'autorisation d'effectuer cette opération");
        }
        
        // Vérifier que le bénéficiaire appartient à l'utilisateur
        if (!$this->verifyBeneficiaryOwnership($beneficiaireId, $userId)) {
            throw new Exception("Ce bénéficiaire n'est pas dans votre liste");
        }
        
        // Vérifier le solde disponible
        if (!$this->checkSufficientBalance($compteSourceId, $montant)) {
            throw new Exception("Solde insuffisant pour effectuer ce virement");
        }
        
        // Récupérer les infos du bénéficiaire
        $beneficiaire = $this->getBeneficiaireById($beneficiaireId);
        if (!$beneficiaire) {
            throw new Exception("Bénéficiaire introuvable");
        }
        
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        
        try {
            // Enregistrer la transaction
            $query = "INSERT INTO transactions 
                     (compte_id, type_transaction, montant, description, beneficiaire, compte_destinataire, date_transaction) 
                     VALUES (?, 'VIREMENT', ?, ?, ?, ?, NOW())";
            
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                $compteSourceId,
                -$montant, // Montant négatif pour le débit
                $description,
                $beneficiaire['nom'],
                $beneficiaire['numero_compte']
            ]);
            
            if (!$result) {
                throw new Exception("Erreur lors de l'enregistrement de la transaction");
            }
            
            // Mettre à jour le solde
            $this->updateAccountBalance($compteSourceId, -$montant);

            // Ajouter ce code après la transaction de débit
            // Récupérer l'ID du compte du bénéficiaire à partir du numéro de compte
            $queryCompte = "SELECT id FROM comptes WHERE numero_compte = ?";
            $stmtCompte = $db->prepare($queryCompte);
            $stmtCompte->execute([$beneficiaire['numero_compte']]);
            $compteDestinataire = $stmtCompte->fetch(PDO::FETCH_ASSOC);

            if ($compteDestinataire) {
                // Enregistrer le crédit sur le compte du bénéficiaire
                $queryCredit = "INSERT INTO transactions 
                               (compte_id, type_transaction, montant, description, beneficiaire, compte_destinataire, date_transaction) 
                               VALUES (?, 'VIREMENT', ?, ?, ?, ?, NOW())";
                
                $stmtCredit = $db->prepare($queryCredit);
                $stmtCredit->execute([
                    $compteDestinataire['id'],
                    $montant, // Montant positif pour le crédit
                    $description . ' (reçu)',
                    $this->getNomExpediteur($compteSourceId), // Vous devrez créer cette méthode
                    $this->getNumeroCompte($compteSourceId)
                ]);
                
                // Mettre à jour le solde du compte bénéficiaire
                $this->updateAccountBalance($compteDestinataire['id'], $montant);
            }
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw new Exception("Erreur lors du virement : " . $e->getMessage());
        }
    }

    /**
     * Récupère le numéro de compte à partir de son ID
     */
    private function getNumeroCompte($compteId) {
        $db = Database::getInstance()->getConnection();
        $query = "SELECT numero_compte FROM comptes WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$compteId]);
        $compte = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $compte ? $compte['numero_compte'] : '';
    }

    /**
     * Met à jour le solde d'un compte
     */
    private function updateAccountBalance($compteId, $montant) {
        $db = Database::getInstance()->getConnection();
        $query = "UPDATE comptes SET solde = solde + ?, date_derniere_operation = NOW() WHERE id = ?";
        $stmt = $db->prepare($query);
        return $stmt->execute([$montant, $compteId]);
    }

    /**
     * Vérifie si un compte appartient à un utilisateur
     */
    private function verifyAccountOwnership($compteId, $userId) {
        $db = Database::getInstance()->getConnection();
        $query = "SELECT COUNT(*) FROM comptes WHERE id = ? AND utilisateur_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$compteId, $userId]);
        
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Vérifie si un bénéficiaire appartient à un utilisateur
     */
    private function verifyBeneficiaryOwnership($beneficiaireId, $userId) {
        $db = Database::getInstance()->getConnection();
        $query = "SELECT COUNT(*) FROM beneficiaires WHERE id = ? AND utilisateur_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$beneficiaireId, $userId]);
        
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Vérifie si le solde est suffisant pour effectuer un virement
     */
    private function checkSufficientBalance($compteId, $montant) {
        $db = Database::getInstance()->getConnection();
        $query = "SELECT solde FROM comptes WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$compteId]);
        $compte = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $compte && $compte['solde'] >= $montant;
    }

    /**
     * Récupère un bénéficiaire par son ID
     */
    private function getBeneficiaireById($beneficiaireId) {
        $db = Database::getInstance()->getConnection();
        $query = "SELECT * FROM beneficiaires WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$beneficiaireId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les transactions récentes d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $limit Nombre maximal de transactions à récupérer
     * @return array Liste des transactions
     */
    public function getRecentTransactionsByUser($userId, $limit = 5) {
        $sql = "SELECT t.id, t.date_transaction, t.montant, t.description, 
                       t.type_transaction, c.numero_compte, t.beneficiaire, 
                       t.compte_destinataire
                FROM transactions t
                JOIN comptes c ON t.compte_id = c.id
                WHERE c.utilisateur_id = ? 
                ORDER BY t.date_transaction DESC
                LIMIT ?";
        
        try {
            return $this->db->select($sql, [$userId, $limit]);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des transactions récentes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les transactions d'un utilisateur pour un mois spécifique
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $mois Mois au format 'YYYY-MM'
     * @return array Liste des transactions du mois
     */
    public function getTransactionsByMonth($userId, $mois) {
        // Déterminer les dates de début et de fin du mois
        $dateDebut = $mois . '-01';
        $dateFin = date('Y-m-t', strtotime($dateDebut)); // t = dernier jour du mois
        
        $sql = "SELECT t.id, t.date_transaction, t.montant, t.description, 
                       t.type_transaction, c.numero_compte, t.beneficiaire, 
                       t.compte_destinataire
                FROM transactions t
                JOIN comptes c ON t.compte_id = c.id
                WHERE c.utilisateur_id = ? 
                  AND t.date_transaction BETWEEN ? AND ?
                ORDER BY t.date_transaction DESC";
        
        try {
            return $this->db->select($sql, [
                $userId, 
                $dateDebut . ' 00:00:00', 
                $dateFin . ' 23:59:59'
            ]);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des transactions du mois: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère le nom de l'expéditeur à partir de l'ID du compte
     * 
     * @param int $compteId ID du compte
     * @return string Nom de l'expéditeur
     */
    private function getNomExpediteur($compteId) {
        $db = Database::getInstance()->getConnection();
        $query = "SELECT CONCAT(u.prenom, ' ', u.nom) as nom_expediteur 
                  FROM comptes c
                  JOIN utilisateurs u ON c.utilisateur_id = u.id
                  WHERE c.id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$compteId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['nom_expediteur'] : 'Expéditeur inconnu';
    }
}