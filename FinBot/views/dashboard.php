<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\views\dashboard.php

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
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/notification_functions.php';

// Récupérer l'ID de l'utilisateur connecté
$userId = $_SESSION['user']['id'];
$userName = $_SESSION['user']['prenom'] . ' ' . $_SESSION['user']['nom'];

// Récupérer les comptes de l'utilisateur
$compteManager = Compte::getInstance();
$comptes = $compteManager->getComptesByUser($userId);

$currentTime = new DateTime();
$hours = $currentTime->format('H');
$minutes = $currentTime->format('i');

// Calculer le solde total - remplacer le code existant par :
$soldeTotal = 0;
$compteCourant = null;
foreach ($comptes as $compte) {
    // Ajouter le solde au total
    $soldeTotal += $compte['solde'];
    
    // Identifier le compte courant pour l'affichage principal
    if ($compte['type_compte_id'] === 'COURANT') {
        $compteCourant = $compte;
    }
}

// Récupérer les dernières transactions
$transaction = Transaction::getInstance();
$recentTransactions = $transaction->getTransactionsByUser($userId, [], 5, 0);

// Calculer les revenus et dépenses du mois courant
$moisCourant = date('Y-m');
$transactionsMois = $transaction->getTransactionsByMonth($userId, $moisCourant);

$revenus = 0;
$depenses = 0;
foreach ($transactionsMois as $trans) {
    if ($trans['montant'] > 0) {
        // C'est un crédit/revenu
        $revenus += $trans['montant'];
    } else {
        // C'est un débit/dépense (montant négatif)
        $depenses += abs($trans['montant']); // On utilise abs() pour avoir une valeur positive
    }
}

// Récupérer les notifications non lues
$notifications = getLatestNotifications($userId, 3);
$nbNotificationsNonLues = countUnreadNotifications($userId);

// Obtenir la date et l'heure actuelle
$dateActuelle = new DateTime();
$heureActuelle = $dateActuelle->format('H');
$salutationMessage = '';

if ($heureActuelle < 12) {
    $salutationMessage = 'Bonjour';
} elseif ($heureActuelle < 18) {
    $salutationMessage = 'Bon après-midi';
} else {
    $salutationMessage = 'Bonsoir';
}
?>

<div class="container-fluid py-4">
    <!-- En-tête de bienvenue -->
    <div class="welcome-header mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3 mb-0 text-gray-800"><?= $salutationMessage ?>, <?= htmlspecialchars($userName) ?></h1>
                <p class="text-muted">
                    <?= date('d/m/Y') ?> | 
                    <span class="dashboard-time"><?= date('H:i') ?></span>
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="transfers.php" class="btn btn-primary me-2">
                    <i class="fas fa-exchange-alt me-2"></i>Effectuer un virement
                </a>
                <a href="accounts.php" class="btn btn-outline-primary">
                    <i class="fas fa-wallet me-2"></i>Mes comptes
                </a>
            </div>
        </div>
    </div>

    <!-- Widgets de résumé -->
    <div class="row mb-4">
        <!-- Solde total -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Solde Total
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= formatMontant($soldeTotal) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wallet fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compte courant -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Compte Courant
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $compteCourant ? formatMontant($compteCourant['solde']) : '0,00 €' ?>
                            </div>
                            <?php if ($compteCourant): ?>
                            <div class="small text-muted mt-1">
                                N° <?= htmlspecialchars($compteCourant['numero_compte'] ?? 'N/A') ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-check-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenus du mois -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Revenus du mois
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= formatMontant($revenus) ?>
                            </div>
                            <div class="small text-muted mt-1">
                                <?= date('F Y') ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-up fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dépenses du mois -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Dépenses du mois
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= formatMontant($depenses) ?>
                            </div>
                            <div class="small text-muted mt-1">
                                <?= date('F Y') ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-down fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenu principal du tableau de bord -->
    <div class="row">
        <!-- Aperçu des comptes -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Mes Comptes</h6>
                    <a href="accounts.php" class="btn btn-sm btn-primary">Voir tout</a>
                </div>
                <div class="card-body">
                    <?php if (empty($comptes)): ?>
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-wallet fa-3x text-gray-300"></i>
                            </div>
                            <p>Vous n'avez aucun compte configuré.</p>
                            <a href="accounts.php" class="btn btn-primary">Ouvrir un compte</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Numéro</th>
                                        <th>Solde</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($comptes as $compte): ?>
                                        <?php $typeCompte = $compte['type'] ?? $compte['type_compte_id'] ?? 'Compte'; ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-<?= getColorByAccountType($compte['type_compte_id']) ?>">
                                                    <?= htmlspecialchars($typeCompte) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($compte['numero_compte'] ?? 'N/A') ?></td>
                                            <td class="fw-bold"><?= formatMontant($compte['solde']) ?></td>
                                            <td>
                                                <a href="transactions.php?compte_id=<?= $compte['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Budget du mois -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Budget du mois</h6>
                </div>
                <div class="card-body">
                    <div class="budget-summary mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <div>Revenus</div>
                            <div class="text-success fw-bold"><?= formatMontant($revenus) ?></div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <div>Dépenses</div>
                            <div class="text-danger fw-bold"><?= formatMontant($depenses) ?></div>
                        </div>
                        <div class="d-flex justify-content-between fw-bold">
                            <div>Solde</div>
                            <div class="<?= ($revenus - $depenses) >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= formatMontant($revenus - $depenses) ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Barre de progression du budget -->
                    <?php 
                    $pourcentageDepense = $revenus > 0 ? min(100, ($depenses / $revenus) * 100) : 0;
                    $budgetStatusClass = $pourcentageDepense < 70 ? 'success' : ($pourcentageDepense < 90 ? 'warning' : 'danger');
                    ?>
                    <h4 class="small font-weight-bold">
                        Dépenses / Revenus
                        <span class="float-end"><?= number_format($pourcentageDepense, 1) ?>%</span>
                    </h4>
                    <div class="progress mb-4">
                        <div class="progress-bar bg-<?= $budgetStatusClass ?>" role="progressbar" 
                             style="width: <?= $pourcentageDepense ?>%" 
                             aria-valuenow="<?= $pourcentageDepense ?>" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne de droite -->
        <div class="col-lg-6">
            <!-- Dernières transactions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Dernières Transactions</h6>
                    <a href="transactions.php" class="btn btn-sm btn-primary">Voir tout</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentTransactions)): ?>
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-exchange-alt fa-3x text-gray-300"></i>
                            </div>
                            <p>Aucune transaction récente</p>
                        </div>
                    <?php else: ?>
                        <div class="transactions-list">
                            <?php foreach ($recentTransactions as $transaction): ?>
                                <div class="transaction-item">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="transaction-info">
                                            <div class="transaction-date text-muted small">
                                                <?= formatDate($transaction['date_transaction'], true) ?>
                                            </div>
                                            <div class="transaction-description">
                                                <?= htmlspecialchars($transaction['description']) ?>
                                            </div>
                                        </div>
                                        <div class="transaction-amount <?= $transaction['montant'] > 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= formatMontantTransaction($transaction['montant']) ?>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted small">
                                        <span class="badge bg-<?= getTransactionTypeClass($transaction['type_transaction']) ?>">
                                            <?= htmlspecialchars($transaction['type_transaction']) ?>
                                        </span>
                                        <span><?= htmlspecialchars($transaction['numero_compte'] ?? 'N/A') ?></span>
                                    </div>
                                    <hr class="my-2">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notifications -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Notifications
                        <?php if ($nbNotificationsNonLues > 0): ?>
                            <span class="badge bg-danger ms-2"><?= $nbNotificationsNonLues ?></span>
                        <?php endif; ?>
                    </h6>
                    <?php if (!empty($notifications)): ?>
                        <a href="#" onclick="markAllNotificationsAsRead(); return false;" class="btn btn-sm btn-outline-primary">Tout marquer comme lu</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-bell fa-3x text-gray-300"></i>
                            </div>
                            <p>Aucune notification récente</p>
                        </div>
                    <?php else: ?>
                        <div class="notifications-list">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item <?= $notification['lu'] ? '' : 'unread' ?>">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="notification-content">
                                            <div class="notification-message">
                                                <?= htmlspecialchars($notification['contenu']) ?>
                                            </div>
                                            <div class="notification-date text-muted small">
                                                <?= formatDate($notification['date_creation'], true) ?>
                                            </div>
                                        </div>
                                        <div class="notification-type">
                                            <span class="badge bg-<?= $notification['type'] ?>"></span>
                                        </div>
                                    </div>
                                    <?php if (!$notification['lu']): ?>
                                        <button class="btn btn-sm btn-outline-secondary mt-1" 
                                                onclick="markNotificationAsRead(<?= $notification['id'] ?>)">
                                            Marquer comme lu
                                        </button>
                                    <?php endif; ?>
                                    <hr class="my-2">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Services rapides -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Services rapides</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                            <a href="transfers.php" class="quick-service">
                                <div class="quick-service-icon mb-2">
                                    <i class="fas fa-paper-plane fa-2x text-primary"></i>
                                </div>
                                <h5>Effectuer un virement</h5>
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                            <a href="accounts.php?action=new_account" class="quick-service">
                                <div class="quick-service-icon mb-2">
                                    <i class="fas fa-piggy-bank fa-2x text-success"></i>
                                </div>
                                <h5>Ouvrir un compte épargne</h5>
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3 mb-sm-0">
                            <a href="messages.php" class="quick-service">
                                <div class="quick-service-icon mb-2">
                                    <i class="fas fa-comment-dots fa-2x text-info"></i>
                                </div>
                                <h5>Contacter un conseiller</h5>
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <a href="loans.php" class="quick-service">
                                <div class="quick-service-icon mb-2">
                                    <i class="fas fa-hand-holding-usd fa-2x text-warning"></i>
                                </div>
                                <h5>Demande de prêt</h5>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Détermine la couleur en fonction du type de compte
 */
function getColorByAccountType($type) {
    $colors = [
        'COURANT' => 'primary',
        'LIVRET_A' => 'success',
        'LDDS' => 'info',
        'PEL' => 'warning'
    ];
    
    return $colors[$type] ?? 'secondary';
}

/**
 * Détermine la classe CSS pour le type de transaction
 */
function getTransactionTypeClass($type) {
    $classes = [
        'CREDIT' => 'success',
        'DEBIT' => 'danger',
        'VIREMENT' => 'info',
        'INTERET' => 'primary',
        'FRAIS' => 'warning'
    ];
    
    return $classes[$type] ?? 'secondary';
}

/**
 * Formate le montant sans signe + pour les soldes
 */
function formatMontant($montant) {
    $formatted = number_format(abs($montant), 2, ',', ' ') . ' €';
    return ($montant >= 0) ? $formatted : '-' . $formatted;
}

/**
 * Formate le montant avec signe + ou - pour les transactions
 */
function formatMontantTransaction($montant) {
    $formatted = number_format(abs($montant), 2, ',', ' ') . ' €';
    return ($montant >= 0) ? '+' . $formatted : '-' . $formatted;
}
?>