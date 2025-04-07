<?php
session_start();
require_once __DIR__ . '/../../includes/transaction_functions.php';  
require_once __DIR__ . '/../../classes/Database.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../../views/auth/login.php');
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    if (empty($_POST['depot_initial']) || empty($_POST['type_epargne'])) {
        throw new Exception('Tous les champs sont obligatoires');
    }

    $depot_initial = floatval($_POST['depot_initial']);
    if ($depot_initial < 20) {
        throw new Exception('Le dépôt initial doit être d\'au moins 20€');
    }

    $db = Database::getInstance();
    $db->getConnection()->beginTransaction();

    try {
        // Vérification du compte courant
        $compteCourant = $db->selectOne(
            "SELECT id, solde FROM comptes WHERE utilisateur_id = ? AND type_compte_id = 'COURANT'",
            [$_SESSION['user']['id']]
        );

        if ($compteCourant['solde'] < $depot_initial) {
            throw new Exception('Solde insuffisant sur votre compte courant');
        }

        // Génération du numéro de compte
        $numeroCompte = 'FR76' . str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);

        // Création du compte épargne
        $compteEpargneId = $db->insert('comptes', [
            'utilisateur_id' => $_SESSION['user']['id'],
            'type_compte_id' => $_POST['type_epargne'],  // Mis à jour pour nouvelle structure DB
            'numero_compte' => $numeroCompte,
            'solde' => $depot_initial,
            'date_creation' => date('Y-m-d H:i:s')
        ]);

        // Mise à jour du solde du compte courant
        $db->update('comptes', 
            ['solde' => $compteCourant['solde'] - $depot_initial],
            'id = ?',
            [$compteCourant['id']]
        );

        // Enregistrement des transactions
        $date = date('Y-m-d H:i:s');
        
        // Transaction débit du compte courant
        $db->insert('transactions', [
            'compte_id' => $compteCourant['id'],
            'type_transaction' => 'DEBIT',  // Mis à jour pour nouvelle structure DB
            'montant' => -$depot_initial,
            'description' => 'Ouverture compte épargne ' . $_POST['type_epargne'],
            'date_transaction' => $date
        ]);

        // Transaction crédit du compte épargne
        $db->insert('transactions', [
            'compte_id' => $compteEpargneId,
            'type_transaction' => 'CREDIT',  // Mis à jour pour nouvelle structure DB
            'montant' => $depot_initial,
            'description' => 'Dépôt initial compte épargne',
            'date_transaction' => $date
        ]);

        $db->commit();

        $_SESSION['success'] = 'Compte épargne créé avec succès';
        header('Location: ../../views/dashboard.php');  // Mis à jour pour nouvelle structure
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: ../../views/dashboard.php');  // Mis à jour pour nouvelle structure
    exit;
}