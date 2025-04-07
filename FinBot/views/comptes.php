<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\views\comptes.php

// Vérification de l'authentification
require_once __DIR__ . '/../includes/auth_functions.php';
try {
    checkAuth();
} catch (Exception $e) {
    // Rediriger vers la page de connexion
    header('Location: /projetBUT/SAE.04/SAE-Banque/FinBot/index.php');
    exit;
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../classes/Compte.php';
require_once __DIR__ . '/../classes/Transaction.php';
require_once __DIR__ . '/../includes/helpers.php';

// Récupérer l'ID de l'utilisateur connecté
$userId = $_SESSION['user']['id'];

// Récupérer tous les comptes de l'utilisateur
$compteManager = Compte::getInstance();
$comptes = $compteManager->getComptesByUser($userId);

// Récupérer les données pour les graphiques (si nécessaire)
$transaction = Transaction::getInstance();
$derniersMouvements = $transaction->getRecentTransactionsByUser($userId, 5);

// Traitement des formulaires
$message = '';
$error = '';

// Traitement du formulaire de virement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'virement') {
        try {
            $message = handleVirementSubmit($userId, $_POST);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif ($_POST['action'] === 'compte_epargne') {
        try {
            $message = handleCompteEpargneSubmit($userId, $_POST);
            // Rafraîchir la liste des comptes après création
            $comptes = $compteManager->getComptesByUser($userId);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Titre de la page
$pageTitle = 'Mes Comptes';
?>

<div class="container-fluid py-4">
    <div class="d-sm-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Mes Comptes</h1>
        <div>
            <a href="transfers.php" class="btn btn-primary">
                <i class="fas fa-exchange-alt me-2"></i>Nouveau virement
            </a>
            <a href="create_epargne.php" class="btn btn-success ms-2">
                <i class="fas fa-piggy-bank me-2"></i>Ouvrir un compte épargne
            </a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    <?php endif; ?>

    <!-- Vue d'ensemble des comptes -->
    <div class="row">
        <?php foreach ($comptes as $compte): ?>
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-<?= getCardColorByAccountType($compte['type_compte_id']) ?> shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-<?= getCardColorByAccountType($compte['type_compte_id']) ?> text-uppercase mb-1">
                                    <?= htmlspecialchars($compte['type_nom']) ?>
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?= formatMontant($compte['solde']) ?>
                                </div>
                                <div class="small text-muted mt-2">
                                    N° <?= htmlspecialchars($compte['numero_compte']) ?>
                                </div>
                                <?php if (isset($compte['taux_interet']) && $compte['taux_interet'] > 0): ?>
                                    <div class="small text-success">
                                        Taux d'intérêt: <?= number_format($compte['taux_interet'], 2, ',', ' ') ?>%
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($compte['plafond']) && $compte['plafond'] > 0): ?>
                                    <div class="small text-info">
                                        Plafond: <?= formatMontant($compte['plafond']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-auto">
                                <i class="<?= getIconByAccountType($compte['type_compte_id']) ?> fa-2x text-gray-300"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="transactions.php?compte_id=<?= $compte['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-history me-1"></i> Historique
                            </a>
                            <?php if ($compte['type_compte_id'] === 'COURANT'): ?>
                                <a href="virement.php?compte_id=<?= $compte['id'] ?>" class="btn btn-sm btn-outline-success ms-1">
                                    <i class="fas fa-paper-plane me-1"></i> Virement
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Test d'ouverture manuelle du modal
    var btn = document.querySelector('[data-bs-target="#epargneModal"]');
    if (btn) {
        console.log("Bouton trouvé, simulation de clic");
        setTimeout(function() {
            // Déclencher manuellement le modal après 1 seconde
            var myModal = new bootstrap.Modal(document.getElementById('epargneModal'));
            myModal.show();
        }, 1000);
    } else {
        console.error("Bouton du modal non trouvé");
    }
});
</script>

<?php
/**
 * Détermine la couleur de la carte en fonction du type de compte
 */
function getCardColorByAccountType($type) {
    $colors = [
        'COURANT' => 'primary',
        'LIVRET_A' => 'success',
        'LDDS' => 'info',
        'PEL' => 'warning'
    ];
    
    return $colors[$type] ?? 'secondary';
}

/**
 * Retourne l'icône FontAwesome en fonction du type de compte
 */
function getIconByAccountType($type) {
    $icons = [
        'COURANT' => 'fas fa-money-check-alt',
        'LIVRET_A' => 'fas fa-piggy-bank',
        'LDDS' => 'fas fa-leaf',
        'PEL' => 'fas fa-home'
    ];
    
    return $icons[$type] ?? 'fas fa-university';
}
?>