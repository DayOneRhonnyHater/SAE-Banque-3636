<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\transfers.php
session_start();
require_once __DIR__ . '/config/app.php';

// Vérifier si l'utilisateur est connecté
require_once __DIR__ . '/includes/auth_functions.php';
try {
    checkAuth();
} catch (Exception $e) {
    header('Location: index.php');
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
$userId = $_SESSION['user']['id'];

// Traitement du formulaire de virement
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/classes/Transaction.php';
    require_once __DIR__ . '/classes/Compte.php';
    require_once __DIR__ . '/classes/Beneficiaire.php';
    
    $transaction = Transaction::getInstance();
    
    // Récupérer les données du formulaire
    $compteSource = isset($_POST['compte_source']) ? intval($_POST['compte_source']) : 0;
    $montant = isset($_POST['montant']) ? floatval($_POST['montant']) : 0;
    $motif = isset($_POST['motif']) ? trim($_POST['motif']) : '';
    $typeVirement = isset($_POST['type_virement']) ? $_POST['type_virement'] : '';
    
    // Validation des données
    if ($compteSource <= 0) {
        $error = 'Veuillez sélectionner un compte source valide.';
    } elseif ($montant <= 0) {
        $error = 'Le montant doit être supérieur à zéro.';
    } elseif (empty($motif)) {
        $error = 'Veuillez indiquer un motif pour le virement.';
    } elseif (!in_array($typeVirement, ['interne', 'externe'])) {
        $error = 'Type de virement invalide.';
    } else {
        try {
            if ($typeVirement === 'interne') {
                // Virement interne
                $compteDestination = isset($_POST['compte_destination']) ? intval($_POST['compte_destination']) : 0;
                
                if ($compteDestination <= 0) {
                    $error = 'Veuillez sélectionner un compte destinataire valide.';
                } elseif ($compteSource === $compteDestination) {
                    $error = 'Le compte source et le compte destinataire ne peuvent pas être identiques.';
                } else {
                    // Effectuer le virement interne
                    if ($transaction->createInternalTransfer($compteSource, $compteDestination, $montant, $motif, $userId)) {
                        $success = 'Virement interne effectué avec succès.';
                    } else {
                        $error = 'Une erreur est survenue lors du virement.';
                    }
                }
            } else {
                // Virement externe
                $beneficiaire = isset($_POST['beneficiaire']) ? intval($_POST['beneficiaire']) : 0;
                
                if ($beneficiaire <= 0) {
                    $error = 'Veuillez sélectionner un bénéficiaire valide.';
                } else {
                    if ($transaction->createExternalTransfer($compteSource, $beneficiaire, $montant, $motif, $userId)) {
                        $success = 'Virement externe effectué avec succès.';
                    } else {
                        $error = 'Une erreur est survenue lors du virement.';
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'Erreur : ' . $e->getMessage();
        }
    }
}

// Traiter le paramètre refaire si présent (refaire un virement identique)
$refaireId = isset($_GET['refaire']) ? intval($_GET['refaire']) : 0;
$virementARefaire = [];

if ($refaireId > 0) {
    require_once __DIR__ . '/classes/Transaction.php';
    $transaction = Transaction::getInstance();
    $virementARefaire = $transaction->getTransactionById($refaireId, $userId);
}

// Configurer les variables pour le layout
$pageTitle = 'Effectuer un virement';
$pageCss = 'transfers';
$viewFile = 'views/transfers.php';




// Inclure le layout principal
include __DIR__ . '/templates/layout.php';