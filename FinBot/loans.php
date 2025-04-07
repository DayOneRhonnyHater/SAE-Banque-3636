<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\loans.php
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

// Vérifier les rôles autorisés (client, conseiller, administrateur)
$role = $_SESSION['user']['role'];
if (!in_array($role, ['CLIENT', 'CONSEILLER', 'ADMINISTRATEUR'])) {
    header('Location: dashboard.php');
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
$userId = $_SESSION['user']['id'];

// Messages de succès/erreur
$success = '';
$error = '';

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once __DIR__ . '/classes/Pret.php';
    $pretManager = Pret::getInstance();
    
    switch ($_POST['action']) {
        case 'new_loan':
            // Nouvelle demande de prêt (client)
            try {
                // Validation des données
                $typePretId = isset($_POST['type_pret']) ? intval($_POST['type_pret']) : 0;
                $montant = isset($_POST['montant']) ? floatval($_POST['montant']) : 0;
                $duree = isset($_POST['duree']) ? intval($_POST['duree']) : 0;
                $compteId = isset($_POST['compte_id']) ? intval($_POST['compte_id']) : 0;
                $description = isset($_POST['description']) ? trim($_POST['description']) : '';
                
                if ($typePretId <= 0) {
                    throw new Exception("Veuillez sélectionner un type de prêt valide.");
                }
                
                if ($montant < 1000) {
                    throw new Exception("Le montant minimum pour un prêt est de 1 000 €.");
                }
                
                if ($duree < 12 || $duree > 360) {
                    throw new Exception("La durée du prêt doit être comprise entre 12 et 360 mois.");
                }
                
                if ($compteId <= 0) {
                    throw new Exception("Veuillez sélectionner un compte pour le versement.");
                }
                
                // Vérifier si le consentement est donné
                if (!isset($_POST['consent']) || $_POST['consent'] !== 'on') {
                    throw new Exception("Vous devez accepter les conditions générales pour continuer.");
                }
                
                // Créer la demande de prêt
                $pretId = $pretManager->createLoanRequest([
                    'utilisateur_id' => $userId,
                    'type_pret_id' => $typePretId,
                    'montant' => $montant,
                    'duree_mois' => $duree,
                    'compte_id' => $compteId,
                    'description' => $description,
                    'date_demande' => date('Y-m-d H:i:s')
                ]);
                
                $success = "Votre demande de prêt a été soumise avec succès (référence #$pretId). Un conseiller l'examinera prochainement.";
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
            
        case 'cancel_loan':
            // Annuler une demande de prêt (client)
            try {
                $pretId = isset($_POST['pret_id']) ? intval($_POST['pret_id']) : 0;
                
                if ($pretId <= 0) {
                    throw new Exception("Demande de prêt introuvable.");
                }
                
                if ($pretManager->cancelLoanRequest($pretId, $userId)) {
                    $success = "Votre demande de prêt a été annulée avec succès.";
                } else {
                    throw new Exception("Impossible d'annuler cette demande de prêt.");
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
            
        case 'approve_loan':
            // Approuver une demande de prêt (conseiller/admin)
            try {
                if (!in_array($role, ['CONSEILLER', 'ADMINISTRATEUR'])) {
                    throw new Exception("Vous n'êtes pas autorisé à effectuer cette action.");
                }
                
                $pretId = isset($_POST['pret_id']) ? intval($_POST['pret_id']) : 0;
                $tauxInteret = isset($_POST['taux_interet']) ? floatval($_POST['taux_interet']) : 0;
                $commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : '';
                
                if ($pretId <= 0) {
                    throw new Exception("Demande de prêt introuvable.");
                }
                
                if ($tauxInteret <= 0 || $tauxInteret > 20) {
                    throw new Exception("Veuillez spécifier un taux d'intérêt valide (entre 0.1% et 20%).");
                }
                
                if ($pretManager->approveLoanRequest($pretId, $tauxInteret, $commentaire, $userId)) {
                    $success = "La demande de prêt a été approuvée avec succès.";
                } else {
                    throw new Exception("Impossible d'approuver cette demande de prêt.");
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
            
        case 'reject_loan':
            // Rejeter une demande de prêt (conseiller/admin)
            try {
                if (!in_array($role, ['CONSEILLER', 'ADMINISTRATEUR'])) {
                    throw new Exception("Vous n'êtes pas autorisé à effectuer cette action.");
                }
                
                $pretId = isset($_POST['pret_id']) ? intval($_POST['pret_id']) : 0;
                $motifRefus = isset($_POST['motif_refus']) ? trim($_POST['motif_refus']) : '';
                
                if ($pretId <= 0) {
                    throw new Exception("Demande de prêt introuvable.");
                }
                
                if (empty($motifRefus)) {
                    throw new Exception("Veuillez spécifier le motif du refus.");
                }
                
                if ($pretManager->rejectLoanRequest($pretId, $motifRefus, $userId)) {
                    $success = "La demande de prêt a été refusée.";
                } else {
                    throw new Exception("Impossible de refuser cette demande de prêt.");
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
            
        case 'accept_offer':
            // Accepter une offre de prêt (client)
            try {
                $pretId = isset($_POST['pret_id']) ? intval($_POST['pret_id']) : 0;
                
                if ($pretId <= 0) {
                    throw new Exception("Offre de prêt introuvable.");
                }
                
                if ($pretManager->acceptLoanOffer($pretId, $userId)) {
                    $success = "Vous avez accepté l'offre de prêt. Le montant sera versé sur votre compte dans les prochains jours.";
                } else {
                    throw new Exception("Impossible d'accepter cette offre de prêt.");
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
            
        case 'reject_offer':
            // Rejeter une offre de prêt (client)
            try {
                $pretId = isset($_POST['pret_id']) ? intval($_POST['pret_id']) : 0;
                
                if ($pretId <= 0) {
                    throw new Exception("Offre de prêt introuvable.");
                }
                
                if ($pretManager->rejectLoanOffer($pretId, $userId)) {
                    $success = "Vous avez refusé l'offre de prêt.";
                } else {
                    throw new Exception("Impossible de refuser cette offre de prêt.");
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
    }
}

// Configurer les variables pour le layout
$pageTitle = 'Gestion des prêts';
$pageCss = 'loans';
$viewFile = '/views/loans.php';

// Scripts pour la page de prêts
$footerScripts = <<<HTML
<script>
    // Fonction pour calculer la mensualité
    function calculateMonthlyPayment() {
        const montant = parseFloat(document.getElementById('montant').value);
        const duree = parseInt(document.getElementById('duree').value);
        const taux = parseFloat(document.getElementById('taux_simulation').value) / 100 / 12; // Taux mensuel
        
        if (isNaN(montant) || isNaN(duree) || isNaN(taux)) {
            alert('Veuillez entrer des valeurs numériques valides');
            return;
        }
        
        // Formule de calcul de mensualité: M = P[r(1+r)^n]/[(1+r)^n-1]
        const mensualite = montant * (taux * Math.pow(1 + taux, duree)) / (Math.pow(1 + taux, duree) - 1);
        const coutTotal = (mensualite * duree) - montant;
        
        // Afficher les résultats
        document.getElementById('mensualite').innerText = formatMontant(mensualite);
        document.getElementById('cout_total').innerText = formatMontant(coutTotal);
        document.getElementById('simulation_result').style.display = 'block';
        
        // Mettre à jour les champs du formulaire de demande
        document.getElementById('montant_demande').value = montant;
        document.getElementById('duree_demande').value = duree;
    }
    
    // Fonction pour formater un montant
    function formatMontant(montant) {
        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(montant);
    }
    
    // Initialisation
    document.addEventListener('DOMContentLoaded', function() {
        // Calculer automatiquement si les valeurs sont déjà définies
        const montant = document.getElementById('montant');
        const duree = document.getElementById('duree');
        const taux = document.getElementById('taux_simulation');
        
        if (montant && duree && taux && 
            montant.value !== '' && duree.value !== '' && taux.value !== '') {
            calculateMonthlyPayment();
        }
    });
</script>
HTML;

// Inclure le layout principal
include __DIR__ . '/templates/layout.php';