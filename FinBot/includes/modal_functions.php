<?php
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Transaction.php';
require_once __DIR__ . '/../classes/Compte.php';

// Utiliser function_exists pour éviter les redéclarations
if (!function_exists('handleVirementSubmit')) {
    /**
     * Gère le traitement du formulaire de virement
     */
    function handleVirementSubmit($userId, $postData) {
        if (empty($postData['email_beneficiaire']) || empty($postData['montant'])) {
            throw new Exception('Tous les champs sont obligatoires');
        }

        $montant = floatval($postData['montant']);
        if ($montant <= 0) {
            throw new Exception('Le montant doit être supérieur à 0');
        }

        // Récupérer l'ID du compte source
        $db = Database::getInstance();
        $compteSource = $db->selectOne(
            "SELECT id FROM comptes WHERE utilisateur_id = ? AND type_compte_id = 'COURANT'",
            [$userId]
        );
        
        if (!$compteSource) {
            throw new Exception('Compte source introuvable');
        }
        
        // Récupérer le compte destinataire par email
        $compteDestinataire = $db->selectOne(
            "SELECT c.id FROM comptes c 
             JOIN utilisateurs u ON c.utilisateur_id = u.id 
             WHERE u.email = ? AND c.type_compte_id = 'COURANT'",
            [$postData['email_beneficiaire']]
        );
        
        if (!$compteDestinataire) {
            throw new Exception('Bénéficiaire introuvable');
        }
        
        // Effectuer le virement
        $compte = Compte::getInstance();
        $compte->effectuerVirement(
            $compteSource['id'],
            $compteDestinataire['id'],
            $montant,
            $postData['description'] ?? 'Virement'
        );

        return 'Virement effectué avec succès';
    }
}

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

if (!function_exists('genererModalConfirmation')) {
    /**
     * Génère une modal de confirmation
     */
    function genererModalConfirmation($id, $titre, $message, $actionBouton, $urlAction) {
        ob_start();
        ?>
        <div class="modal fade" id="<?= htmlspecialchars($id) ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= htmlspecialchars($titre) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p><?= htmlspecialchars($message) ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <a href="<?= htmlspecialchars($urlAction) ?>" class="btn btn-primary"><?= htmlspecialchars($actionBouton) ?></a>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('genererModalFormulaire')) {
    /**
     * Génère une modal de formulaire
     */
    function genererModalFormulaire($id, $titre, $contenuFormulaire, $actionBouton = 'Enregistrer') {
        ob_start();
        ?>
        <div class="modal fade" id="<?= htmlspecialchars($id) ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= htmlspecialchars($titre) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <form method="post" class="needs-validation" novalidate>
                        <div class="modal-body">
                            <?= $contenuFormulaire ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary"><?= htmlspecialchars($actionBouton) ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}