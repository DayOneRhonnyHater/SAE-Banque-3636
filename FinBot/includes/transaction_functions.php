<?php
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Transaction.php';
require_once __DIR__ . '/../classes/Compte.php';

// Si la fonction notification est utilisée
require_once __DIR__ . '/../includes/notification_functions.php';

/**
 * Récupère les transactions d'un utilisateur
 * 
 * @param int $userId ID de l'utilisateur
 * @return array Liste des transactions
 */
function getTransactions($userId, $filtres = [], $limit = 50, $offset = 0) {
    try {
        // Utiliser la nouvelle classe Transaction
        $transaction = Transaction::getInstance();
        return $transaction->getTransactionsByUser($userId, $filtres, $limit, $offset);
    } catch (Exception $e) {
        throw new Exception("Erreur lors du chargement des transactions: " . $e->getMessage());
    }
}

/**
 * Effectue un virement entre deux comptes
 * 
 * @param int $expediteurId ID de l'utilisateur expéditeur
 * @param string $emailBeneficiaire Email du bénéficiaire
 * @param float $montant Montant du virement
 * @param string $description Description du virement
 * @return bool Succès ou échec
 */
function effectuerVirement($expediteurId, $emailBeneficiaire, $montant, $description = 'Virement') {
    try {
        $db = Database::getInstance();
        
        // Vérification du compte source
        $compteSource = $db->selectOne(
            "SELECT id FROM comptes WHERE utilisateur_id = ? AND type_compte_id = 'COURANT'",
            [$expediteurId]
        );

        if (!$compteSource) {
            throw new Exception('Compte source introuvable');
        }
        
        // Vérification du bénéficiaire
        $beneficiaire = $db->selectOne(
            "SELECT id FROM utilisateurs WHERE email = ?",
            [$emailBeneficiaire]
        );

        if (!$beneficiaire) {
            throw new Exception('Bénéficiaire introuvable');
        }

        $compteBeneficiaire = $db->selectOne(
            "SELECT id FROM comptes WHERE utilisateur_id = ? AND type_compte_id = 'COURANT'",
            [$beneficiaire['id']]
        );
        
        if (!$compteBeneficiaire) {
            throw new Exception('Compte bénéficiaire introuvable');
        }

        // Utiliser la classe Compte pour effectuer le virement
        $compte = Compte::getInstance();
        $success = $compte->effectuerVirement(
            $compteSource['id'],
            $compteBeneficiaire['id'],
            $montant,
            $description
        );

        if ($success) {
            // Si la fonction addNotification est disponible
            if (function_exists('addNotification')) {
                // Notification pour l'expéditeur
                addNotification(
                    $expediteurId,
                    "Virement de " . formatMontant($montant) . " effectué vers " . $emailBeneficiaire,
                    'success'
                );
                
                // Notification pour le bénéficiaire
                addNotification(
                    $beneficiaire['id'],
                    "Virement reçu de " . formatMontant($montant),
                    'success'
                );
            }
        }

        return $success;

    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Formate un montant pour l'affichage
 * 
 * @param float $montant Montant à formater
 * @return string Montant formaté
 */
function formatMontant($montant) {
    return number_format($montant, 2, ',', ' ') . ' €';
}

/**
 * Formate une date pour l'affichage
 * 
 * @param string $date Date à formater
 * @return string Date formatée
 */
function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

/**
 * Récupère la classe CSS pour un type de transaction
 * 
 * @param string $type Type de transaction
 * @return string Classe CSS
 */
function getTransactionTypeClass($type) {
    $classes = [
        'CREDIT' => 'badge badge-success',
        'DEBIT' => 'badge badge-danger',
        'VIREMENT' => 'badge badge-info',
        'INTERET' => 'badge badge-primary',
        'FRAIS' => 'badge badge-warning'
    ];
    return $classes[$type] ?? 'badge badge-secondary';
}

/**
 * Exporte les transactions au format CSV
 * 
 * @param int $userId ID de l'utilisateur
 * @param array $filtres Filtres pour les transactions
 * @return string Contenu CSV
 */
function exportTransactionsCSV($userId, $filtres = []) {
    $transaction = Transaction::getInstance();
    return $transaction->exporterCSV($userId, $filtres);
}