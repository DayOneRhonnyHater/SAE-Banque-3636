<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\classes\Pret.php

require_once __DIR__ . '/Database.php';

/**
 * Classe pour gérer les prêts bancaires
 */
class Pret {
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
     * @return Pret Instance unique
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Récupère les types de prêts disponibles
     * 
     * @return array Liste des types de prêts
     */
    public function getTypePrets() {
        $sql = "SELECT * FROM types_prets WHERE actif = 1 ORDER BY nom";
        
        try {
            return $this->db->select($sql);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des types de prêts: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les prêts d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Liste des prêts
     */
    public function getPretsByUser($userId) {
        $sql = "SELECT p.*, tp.nom as type_nom, c.numero_compte
                FROM prets p
                JOIN types_prets tp ON p.type_pret_id = tp.id
                JOIN comptes c ON p.compte_id = c.id
                WHERE p.utilisateur_id = ?
                ORDER BY p.date_demande DESC";
        
        try {
            return $this->db->select($sql, [$userId]);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des prêts: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les prêts gérés par un conseiller
     * 
     * @param int $advisorId ID du conseiller
     * @return array Liste des prêts
     */
    public function getPretsByAdvisor($advisorId) {
        $sql = "SELECT p.*, tp.nom as type_nom, c.numero_compte,
                       CONCAT(u.prenom, ' ', u.nom) as client_nom
                FROM prets p
                JOIN types_prets tp ON p.type_pret_id = tp.id
                JOIN comptes c ON p.compte_id = c.id
                JOIN utilisateurs u ON p.utilisateur_id = u.id
                JOIN clients_conseillers cc ON u.id = cc.client_id
                WHERE cc.conseiller_id = ?
                ORDER BY 
                    CASE 
                        WHEN p.statut = 'EN_ATTENTE' THEN 1
                        WHEN p.statut = 'APPROUVE' THEN 2
                        WHEN p.statut = 'ACCEPTE' THEN 3
                        ELSE 4
                    END,
                    p.date_demande DESC";
        
        try {
            return $this->db->select($sql, [$advisorId]);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des prêts du conseiller: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère tous les prêts (pour administrateur)
     * 
     * @return array Liste de tous les prêts
     */
    public function getAllPrets() {
        $sql = "SELECT p.*, tp.nom as type_nom, c.numero_compte,
                       CONCAT(u.prenom, ' ', u.nom) as client_nom
                FROM prets p
                JOIN types_prets tp ON p.type_pret_id = tp.id
                JOIN comptes c ON p.compte_id = c.id
                JOIN utilisateurs u ON p.utilisateur_id = u.id
                ORDER BY p.date_demande DESC";
        
        try {
            return $this->db->select($sql);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération de tous les prêts: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les détails d'un prêt pour un client
     * 
     * @param int $pretId ID du prêt
     * @param int $userId ID de l'utilisateur (pour vérification)
     * @return array|null Détails du prêt ou null si non trouvé
     */
    public function getPretDetails($pretId, $userId) {
        $sql = "SELECT p.*, tp.nom as type_nom, c.numero_compte,
                       (SELECT MIN(date_echeance) FROM echeances_prets 
                        WHERE pret_id = p.id AND statut_paiement = 'EN_ATTENTE') as prochaine_echeance,
                       (SELECT SUM(montant_capital) FROM echeances_prets 
                        WHERE pret_id = p.id AND statut_paiement = 'PAYE') as capital_rembourse
                FROM prets p
                JOIN types_prets tp ON p.type_pret_id = tp.id
                JOIN comptes c ON p.compte_id = c.id
                WHERE p.id = ? AND p.utilisateur_id = ?";
        
        try {
            return $this->db->selectOne($sql, [$pretId, $userId]);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des détails du prêt: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupère les détails d'un prêt pour un conseiller
     * 
     * @param int $pretId ID du prêt
     * @return array|null Détails du prêt ou null si non trouvé
     */
    public function getPretDetailsForAdvisor($pretId) {
        $sql = "SELECT p.*, tp.nom as type_nom, c.numero_compte,
                       CONCAT(u.prenom, ' ', u.nom) as client_nom,
                       u.email as client_email, u.telephone as client_telephone,
                       (SELECT MIN(date_echeance) FROM echeances_prets 
                        WHERE pret_id = p.id AND statut_paiement = 'EN_ATTENTE') as prochaine_echeance,
                       (SELECT SUM(montant_capital) FROM echeances_prets 
                        WHERE pret_id = p.id AND statut_paiement = 'PAYE') as capital_rembourse
                FROM prets p
                JOIN types_prets tp ON p.type_pret_id = tp.id
                JOIN comptes c ON p.compte_id = c.id
                JOIN utilisateurs u ON p.utilisateur_id = u.id
                WHERE p.id = ?";
        
        try {
            return $this->db->selectOne($sql, [$pretId]);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des détails du prêt pour le conseiller: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupère les échéances d'un prêt
     * 
     * @param int $pretId ID du prêt
     * @return array Liste des échéances
     */
    public function getEcheancesByPret($pretId) {
        $sql = "SELECT * FROM echeances_prets 
                WHERE pret_id = ? 
                ORDER BY numero_echeance";
        
        try {
            return $this->db->select($sql, [$pretId]);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des échéances: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Crée une nouvelle demande de prêt
     * 
     * @param array $data Données du prêt
     * @return int|false ID du prêt créé ou false en cas d'échec
     */
    public function createLoanRequest($data) {
        // Validation des données
        if (empty($data['utilisateur_id']) || empty($data['type_pret_id']) || 
            empty($data['montant']) || empty($data['duree_mois']) || empty($data['compte_id'])) {
            throw new Exception("Données de prêt incomplètes");
        }
        
        try {
            // Création du prêt
            $pretData = [
                'utilisateur_id' => $data['utilisateur_id'],
                'type_pret_id' => $data['type_pret_id'],
                'compte_id' => $data['compte_id'],
                'montant' => $data['montant'],
                'duree_mois' => $data['duree_mois'],
                'statut' => 'EN_ATTENTE',
                'date_demande' => $data['date_demande'] ?? date('Y-m-d H:i:s'),
                'description' => $data['description'] ?? null
            ];
            
            return $this->db->insert('prets', $pretData);
        } catch (Exception $e) {
            error_log('Erreur lors de la création de la demande de prêt: ' . $e->getMessage());
            throw new Exception("Erreur lors de la création de la demande de prêt");
        }
    }
    
    /**
     * Annule une demande de prêt
     * 
     * @param int $pretId ID du prêt
     * @param int $userId ID de l'utilisateur (pour vérification)
     * @return bool Succès de l'opération
     */
    public function cancelLoanRequest($pretId, $userId) {
        // Vérifier que le prêt appartient à l'utilisateur et qu'il est en attente
        $pret = $this->getPretDetails($pretId, $userId);
        
        if (!$pret || $pret['statut'] !== 'EN_ATTENTE') {
            throw new Exception("Impossible d'annuler cette demande de prêt");
        }
        
        try {
            $this->db->update('prets', 
                              ['statut' => 'ANNULE'], 
                              'id = ? AND utilisateur_id = ?', 
                              [$pretId, $userId]);
            return true;
        } catch (Exception $e) {
            error_log('Erreur lors de l\'annulation de la demande de prêt: ' . $e->getMessage());
            throw new Exception("Erreur lors de l'annulation de la demande");
        }
    }
    
    /**
     * Approuve une demande de prêt (par un conseiller)
     * 
     * @param int $pretId ID du prêt
     * @param float $tauxInteret Taux d'intérêt proposé
     * @param string $commentaire Commentaire optionnel
     * @param int $conseillerId ID du conseiller qui approuve
     * @return bool Succès de l'opération
     */
    public function approveLoanRequest($pretId, $tauxInteret, $commentaire, $conseillerId) {
        // Récupérer les informations du prêt
        $pret = $this->db->selectOne(
            "SELECT * FROM prets WHERE id = ? AND statut = 'EN_ATTENTE'", 
            [$pretId]
        );
        
        if (!$pret) {
            throw new Exception("Demande de prêt introuvable ou déjà traitée");
        }
        
        // Calculer la mensualité
        $montant = $pret['montant'];
        $dureeMois = $pret['duree_mois'];
        $tauxMensuel = $tauxInteret / 100 / 12;
        
        $mensualite = $montant * ($tauxMensuel * pow(1 + $tauxMensuel, $dureeMois)) / (pow(1 + $tauxMensuel, $dureeMois) - 1);
        
        // Calculer le TAEG (Taux Annuel Effectif Global)
        $coutTotal = ($mensualite * $dureeMois) - $montant;
        $taeg = $this->calculateTAEG($montant, $mensualite, $dureeMois);
        
        try {
            $this->db->beginTransaction();
            
            // Mettre à jour le prêt
            $this->db->update('prets', [
                'statut' => 'APPROUVE',
                'taux_interet' => $tauxInteret,
                'mensualite' => $mensualite,
                'taeg' => $taeg,
                'conseiller_id' => $conseillerId,
                'date_decision' => date('Y-m-d H:i:s'),
                'commentaire' => $commentaire
            ], 'id = ?', [$pretId]);
            
            // Créer une notification pour le client
            require_once __DIR__ . '/../includes/notification_functions.php';
            createNotification(
                $pret['utilisateur_id'],
                "Votre demande de prêt (#$pretId) a été approuvée. Une offre de prêt vous a été proposée.",
                'info'
            );
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erreur lors de l\'approbation du prêt: ' . $e->getMessage());
            throw new Exception("Erreur lors de l'approbation du prêt");
        }
    }
    
    /**
     * Rejette une demande de prêt (par un conseiller)
     * 
     * @param int $pretId ID du prêt
     * @param string $motifRefus Motif du refus
     * @param int $conseillerId ID du conseiller qui refuse
     * @return bool Succès de l'opération
     */
    public function rejectLoanRequest($pretId, $motifRefus, $conseillerId) {
        // Récupérer les informations du prêt
        $pret = $this->db->selectOne(
            "SELECT * FROM prets WHERE id = ? AND statut = 'EN_ATTENTE'", 
            [$pretId]
        );
        
        if (!$pret) {
            throw new Exception("Demande de prêt introuvable ou déjà traitée");
        }
        
        try {
            $this->db->beginTransaction();
            
            // Mettre à jour le prêt
            $this->db->update('prets', [
                'statut' => 'REFUSE',
                'conseiller_id' => $conseillerId,
                'date_decision' => date('Y-m-d H:i:s'),
                'commentaire' => $motifRefus
            ], 'id = ?', [$pretId]);
            
            // Créer une notification pour le client
            require_once __DIR__ . '/../includes/notification_functions.php';
            createNotification(
                $pret['utilisateur_id'],
                "Votre demande de prêt (#$pretId) a été refusée. Consultez le dossier pour connaître les motifs.",
                'warning'
            );
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erreur lors du refus du prêt: ' . $e->getMessage());
            throw new Exception("Erreur lors du refus du prêt");
        }
    }
    
    /**
     * Accepte une offre de prêt (par le client)
     * 
     * @param int $pretId ID du prêt
     * @param int $userId ID de l'utilisateur (pour vérification)
     * @return bool Succès de l'opération
     */
    public function acceptLoanOffer($pretId, $userId) {
        // Vérifier que le prêt appartient à l'utilisateur et qu'il est approuvé
        $pret = $this->getPretDetails($pretId, $userId);
        
        if (!$pret || $pret['statut'] !== 'APPROUVE') {
            throw new Exception("Offre de prêt introuvable ou déjà traitée");
        }
        
        try {
            $this->db->beginTransaction();
            
            // Calculer les échéances
            $montant = $pret['montant'];
            $dureeMois = $pret['duree_mois'];
            $tauxMensuel = $pret['taux_interet'] / 100 / 12;
            $mensualite = $pret['mensualite'];
            
            // Date de début (généralement le 1er du mois suivant)
            $dateDebut = date('Y-m-d', strtotime('first day of next month'));
            $dateFin = date('Y-m-d', strtotime("$dateDebut + $dureeMois months - 1 day"));
            
            // Mettre à jour le prêt
            $this->db->update('prets', [
                'statut' => 'ACCEPTE',
                'date_acceptation' => date('Y-m-d H:i:s'),
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin
            ], 'id = ? AND utilisateur_id = ?', [$pretId, $userId]);
            
            // Générer le tableau d'amortissement
            $this->generateAmortizationTable($pretId, $montant, $mensualite, $dureeMois, $tauxMensuel, $dateDebut);
            
            // Créer une transaction pour le versement du montant sur le compte
            require_once __DIR__ . '/Transaction.php';
            $transactionManager = Transaction::getInstance();
            
            $transactionManager->createTransaction([
                'compte_id' => $pret['compte_id'],
                'type' => 'CREDIT',
                'montant' => $montant,
                'date_transaction' => date('Y-m-d H:i:s'),
                'description' => "Versement prêt #$pretId",
                'details' => "Versement du montant du prêt #$pretId",
                'reference' => "PRET" . $pretId
            ]);
            
            // Notifier le conseiller
            require_once __DIR__ . '/../includes/notification_functions.php';
            if ($pret['conseiller_id']) {
                createNotification(
                    $pret['conseiller_id'],
                    "Le client a accepté l'offre de prêt #$pretId. Le versement a été effectué.",
                    'info'
                );
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erreur lors de l\'acceptation de l\'offre: ' . $e->getMessage());
            throw new Exception("Erreur lors de l'acceptation de l'offre");
        }
    }
    
    /**
     * Rejette une offre de prêt (par le client)
     * 
     * @param int $pretId ID du prêt
     * @param int $userId ID de l'utilisateur (pour vérification)
     * @return bool Succès de l'opération
     */
    public function rejectLoanOffer($pretId, $userId) {
        // Vérifier que le prêt appartient à l'utilisateur et qu'il est approuvé
        $pret = $this->getPretDetails($pretId, $userId);
        
        if (!$pret || $pret['statut'] !== 'APPROUVE') {
            throw new Exception("Offre de prêt introuvable ou déjà traitée");
        }
        
        try {
            $this->db->beginTransaction();
            
            // Mettre à jour le prêt
            $this->db->update('prets', [
                'statut' => 'REJETE',
                'date_rejet' => date('Y-m-d H:i:s')
            ], 'id = ? AND utilisateur_id = ?', [$pretId, $userId]);
            
            // Notifier le conseiller
            require_once __DIR__ . '/../includes/notification_functions.php';
            if ($pret['conseiller_id']) {
                createNotification(
                    $pret['conseiller_id'],
                    "Le client a refusé l'offre de prêt #$pretId.",
                    'warning'
                );
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erreur lors du rejet de l\'offre: ' . $e->getMessage());
            throw new Exception("Erreur lors du rejet de l'offre");
        }
    }
    
    /**
     * Calcul du TAEG (Taux Annuel Effectif Global)
     * 
     * @param float $montant Montant emprunté
     * @param float $mensualite Mensualité calculée
     * @param int $duree Durée en mois
     * @return float TAEG calculé
     */
    private function calculateTAEG($montant, $mensualite, $duree) {
        // Approximation du TAEG en utilisant la formule simplifiée
        $coutTotal = ($mensualite * $duree) - $montant;
        $taeg = (($coutTotal / $montant) / ($duree / 12)) * 100;
        
        return round($taeg, 2);
    }
    
    /**
     * Génère le tableau d'amortissement d'un prêt
     * 
     * @param int $pretId ID du prêt
     * @param float $capital Capital emprunté
     * @param float $mensualite Mensualité calculée
     * @param int $duree Durée en mois
     * @param float $tauxMensuel Taux mensuel (en décimal)
     * @param string $dateDebut Date de début au format Y-m-d
     * @return bool Succès de l'opération
     */
    private function generateAmortizationTable($pretId, $capital, $mensualite, $duree, $tauxMensuel, $dateDebut) {
        $capitalRestant = $capital;
        $dateEcheance = $dateDebut;
        
        try {
            for ($i = 1; $i <= $duree; $i++) {
                // Calculer les intérêts et le capital pour cette échéance
                $interets = $capitalRestant * $tauxMensuel;
                $capitalEcheance = $mensualite - $interets;
                
                // S'assurer que la dernière échéance règle exactement le capital restant
                if ($i == $duree) {
                    $capitalEcheance = $capitalRestant;
                    $mensualite = $capitalEcheance + $interets;
                }
                
                // Mettre à jour le capital restant
                $capitalRestant -= $capitalEcheance;
                if ($capitalRestant < 0) $capitalRestant = 0;
                
                // Insérer l'échéance dans la base de données
                $echeanceData = [
                    'pret_id' => $pretId,
                    'numero_echeance' => $i,
                    'date_echeance' => $dateEcheance,
                    'montant_mensualite' => $mensualite,
                    'montant_capital' => $capitalEcheance,
                    'montant_interets' => $interets,
                    'capital_restant' => $capitalRestant,
                    'statut_paiement' => 'EN_ATTENTE'
                ];
                
                $this->db->insert('echeances_prets', $echeanceData);
                
                // Calculer la date de la prochaine échéance
                $dateEcheance = date('Y-m-d', strtotime("$dateEcheance +1 month"));
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Erreur lors de la génération du tableau d\'amortissement: ' . $e->getMessage());
            throw new Exception("Erreur lors de la génération du tableau d'amortissement");
        }
    }
    
    /**
     * Active un prêt après vérification et versement des fonds
     * Cette méthode serait appelée par un processus automatique ou par un administrateur
     * 
     * @param int $pretId ID du prêt
     * @return bool Succès de l'opération
     */
    public function activateLoan($pretId) {
        // Récupérer les informations du prêt
        $pret = $this->db->selectOne(
            "SELECT * FROM prets WHERE id = ? AND statut = 'ACCEPTE'", 
            [$pretId]
        );
        
        if (!$pret) {
            throw new Exception("Prêt introuvable ou déjà activé");
        }
        
        try {
            $this->db->beginTransaction();
            
            // Mettre à jour le prêt
            $this->db->update('prets', [
                'statut' => 'ACTIF',
                'date_activation' => date('Y-m-d H:i:s')
            ], 'id = ?', [$pretId]);
            
            // Notifier le client
            require_once __DIR__ . '/../includes/notification_functions.php';
            createNotification(
                $pret['utilisateur_id'],
                "Votre prêt #$pretId est maintenant actif. Les remboursements commenceront à la date prévue.",
                'success'
            );
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erreur lors de l\'activation du prêt: ' . $e->getMessage());
            throw new Exception("Erreur lors de l'activation du prêt");
        }
    }
    
    /**
     * Traite le paiement d'une échéance
     * Cette méthode serait généralement appelée par un processus automatique
     * 
     * @param int $echeanceId ID de l'échéance
     * @return bool Succès de l'opération
     */
    public function processPayment($echeanceId) {
        // Récupérer les informations de l'échéance
        $echeance = $this->db->selectOne(
            "SELECT ep.*, p.utilisateur_id, p.compte_id, p.id as pret_id 
             FROM echeances_prets ep
             JOIN prets p ON ep.pret_id = p.id
             WHERE ep.id = ? AND ep.statut_paiement = 'EN_ATTENTE'", 
            [$echeanceId]
        );
        
        if (!$echeance) {
            throw new Exception("Échéance introuvable ou déjà payée");
        }
        
        try {
            $this->db->beginTransaction();
            
            // S'assurer que la classe Transaction est chargée et créer une instance
            if (!class_exists('Transaction')) {
                require_once __DIR__ . '/Transaction.php';
            }
            $transactionManager = Transaction::getInstance();
            
            // Vérifier que l'instance a été créée correctement
            if (!$transactionManager) {
                throw new Exception("Impossible de créer une instance de la classe Transaction");
            }
            
            // Créer la transaction
            $result = $transactionManager->createTransaction([
                'compte_id' => $echeance['compte_id'],
                'type' => 'PRELEVEMENT',
                'montant' => -$echeance['montant_mensualite'],
                'date_transaction' => date('Y-m-d H:i:s'),
                'description' => "Mensualité prêt #" . $echeance['pret_id'] . " (échéance " . $echeance['numero_echeance'] . ")",
                'details' => "Capital: " . number_format($echeance['montant_capital'], 2) . " € / Intérêts: " . number_format($echeance['montant_interets'], 2) . " €",
                'reference' => "ECHEANCE" . $echeance['id']
            ]);
            
            // Vérifier que la transaction a été créée avec succès
            if (!$result) {
                throw new Exception("Échec de la création de la transaction pour l'échéance #" . $echeanceId);
            }
            
            // Mettre à jour l'échéance
            $this->db->update('echeances_prets', [
                'statut_paiement' => 'PAYE',
                'date_paiement' => date('Y-m-d H:i:s')
            ], 'id = ?', [$echeanceId]);
            
            // Vérifier si c'est la dernière échéance pour clôturer le prêt
            $echeancesRestantes = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM echeances_prets 
                 WHERE pret_id = ? AND statut_paiement = 'EN_ATTENTE'",
                [$echeance['pret_id']]
            );
            
            if ($echeancesRestantes && $echeancesRestantes['count'] == 0) {
                // C'était la dernière échéance, clôturer le prêt
                $this->db->update('prets', [
                    'statut' => 'TERMINE',
                    'date_cloture' => date('Y-m-d H:i:s')
                ], 'id = ?', [$echeance['pret_id']]);
                
                // Notifier le client
                require_once __DIR__ . '/../includes/notification_functions.php';
                createNotification(
                    $echeance['utilisateur_id'],
                    "Félicitations ! Votre prêt #" . $echeance['pret_id'] . " est entièrement remboursé et clôturé.",
                    'success'
                );
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erreur lors du traitement du paiement: ' . $e->getMessage());
            throw new Exception("Erreur lors du traitement du paiement: " . $e->getMessage());
        }
    }
}