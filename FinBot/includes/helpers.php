<?php
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Compte.php';
require_once __DIR__ . '/../classes/Transaction.php';

// Utiliser function_exists pour éviter les redéclarations
if (!function_exists('handleCompteEpargneSubmit')) {
    /**
     * Gère le traitement du formulaire de création de compte épargne
     */
    function handleCompteEpargneSubmit($userId, $postData) {
        if (empty($postData['type_epargne']) || empty($postData['depot_initial'])) {
            throw new Exception('Tous les champs sont obligatoires');
        }

        $depotInitial = floatval($postData['depot_initial']);
        $typeEpargne = $postData['type_epargne'];

        // Vérification des montants minimum selon le type
        $minDepots = [
            'LIVRET_A' => 10,
            'PEL' => 1500,
            'LDDS' => 15
        ];

        if ($depotInitial < $minDepots[$typeEpargne]) {
            throw new Exception("Le dépôt minimum pour ce type de compte est de {$minDepots[$typeEpargne]}€");
        }

        // Vérification des frais de création
        $fraisCreation = [
            'LIVRET_A' => 0,
            'PEL' => 50,
            'LDDS' => 20
        ];

        $montantTotal = $depotInitial + $fraisCreation[$typeEpargne];

        // Vérification du solde disponible
        $db = Database::getInstance();
        $compteCourant = $db->selectOne(
            "SELECT id, solde FROM comptes WHERE utilisateur_id = ? AND type_compte_id = 'COURANT'",
            [$userId]
        );

        if (!$compteCourant || $compteCourant['solde'] < $montantTotal) {
            throw new Exception('Solde insuffisant pour créer ce compte épargne');
        }

        // Création du compte épargne
        $compte = Compte::getInstance();
        $compteId = $compte->createCompte($userId, $typeEpargne, $depotInitial);
        
        // Si des frais sont applicables, les débiter du compte courant
        if ($fraisCreation[$typeEpargne] > 0) {
            $transaction = Transaction::getInstance();
            $transaction->createTransaction([
                'compte_id' => $compteCourant['id'],
                'type' => 'DEBIT',
                'montant' => $fraisCreation[$typeEpargne],
                'description' => 'Frais de création compte épargne'
            ]);
        }

        return 'Compte épargne créé avec succès';
    }
}

if (!function_exists('getComptesUtilisateur')) {
    /**
     * Récupère tous les comptes d'un utilisateur
     */
    function getComptesUtilisateur($userId) {
        $compte = Compte::getInstance();
        return $compte->getComptesByUser($userId);
    }
}

if (!function_exists('getCompteParNumero')) {
    /**
     * Récupère un compte par son numéro
     */
    function getCompteParNumero($numeroCompte) {
        $db = Database::getInstance();
        return $db->selectOne(
            "SELECT c.*, u.nom as nom_proprietaire, u.prenom as prenom_proprietaire, 
                    tc.nom as type_nom, tc.taux_interet, tc.plafond
             FROM comptes c 
             JOIN utilisateurs u ON c.utilisateur_id = u.id
             JOIN types_comptes tc ON c.type_compte_id = tc.id
             WHERE c.numero_compte = ?",
            [$numeroCompte]
        );
    }
}

if (!function_exists('verifierProprietaireCompte')) {
    /**
     * Vérifie si un compte appartient à un utilisateur
     */
    function verifierProprietaireCompte($compteId, $userId) {
        $db = Database::getInstance();
        $compte = $db->selectOne(
            "SELECT id FROM comptes WHERE id = ? AND utilisateur_id = ?",
            [$compteId, $userId]
        );
        return !empty($compte);
    }
}

if (!function_exists('formatMontant')) {
    /**
     * Formate un montant pour l'affichage
     */
    function formatMontant($montant, $avecSymbole = true) {
        $format = number_format($montant, 2, ',', ' ');
        return $avecSymbole ? $format . ' €' : $format;
    }
}

if (!function_exists('formatDate')) {
    /**
     * Formate une date pour l'affichage
     */
    function formatDate($date, $inclureHeure = false) {
        $dateObj = new DateTime($date);
        $format = $inclureHeure ? 'd/m/Y H:i' : 'd/m/Y';
        return $dateObj->format($format);
    }
}

if (!function_exists('genererSelectComptes')) {
    /**
     * Génère un SELECT HTML avec les comptes d'un utilisateur
     */
    function genererSelectComptes($userId, $name = 'compte_id', $selectedId = null) {
        $comptes = getComptesUtilisateur($userId);
        $html = '<select name="' . htmlspecialchars($name) . '" class="form-select" required>';
        $html .= '<option value="">Sélectionnez un compte</option>';
        
        foreach ($comptes as $compte) {
            $selected = ($selectedId == $compte['id']) ? 'selected' : '';
            $html .= '<option value="' . htmlspecialchars($compte['id']) . '" ' . $selected . '>';
            $html .= htmlspecialchars($compte['type_nom'] . ' - ' . $compte['numero_compte'] . ' (' . formatMontant($compte['solde']) . ')');
            $html .= '</option>';
        }
        
        $html .= '</select>';
        return $html;
    }
}