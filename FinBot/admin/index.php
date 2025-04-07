<?php
// Inclusion des fichiers nécessaires
require_once '../includes/init.php'; // Fichier d'initialisation centralisé
require_once '../classes/Database.php';

// Vérification de l'accès administrateur
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'accès administrateur est autorisé
if (!isset($_SESSION['admin_access']) || $_SESSION['admin_access'] !== true) {
    // Rediriger vers la page d'accès administrateur
    header('Location: ../admin_access.php');
    exit;
}

// Récupération des données depuis la base de données
try {
    $db = Database::getInstance();
    
    // Récupérer le nombre total d'utilisateurs
    $totalUsers = $db->selectOne("SELECT COUNT(*) as total FROM utilisateurs", []);
    $totalUsers = $totalUsers ? $totalUsers['total'] : 0;
    
    // Récupérer le nombre d'utilisateurs actifs
    $activeUsers = $db->selectOne("SELECT COUNT(*) as total FROM utilisateurs WHERE statut = 'ACTIF'", []);
    $activeUsers = $activeUsers ? $activeUsers['total'] : 0;
    
    // Récupérer le nombre d'utilisateurs inactifs ou bloqués
    $inactiveUsers = $db->selectOne("SELECT COUNT(*) as total FROM utilisateurs WHERE statut != 'ACTIF'", []);
    $inactiveUsers = $inactiveUsers ? $inactiveUsers['total'] : 0;
    
    // Récupérer le nombre total de comptes
    $totalAccounts = $db->selectOne("SELECT COUNT(*) as total FROM comptes", []);
    $totalAccounts = $totalAccounts ? $totalAccounts['total'] : 0;
    
    // Récupérer les utilisateurs récemment inscrits (5 derniers)
    $recentUsers = $db->select(
        "SELECT id, nom, prenom, email, role, statut, date_creation, avatar 
         FROM utilisateurs 
         ORDER BY date_creation DESC 
         LIMIT 5"
    );
    
    // Récupérer les dernières transactions
    $recentTransactions = $db->select(
        "SELECT t.id, t.montant, t.date_transaction, t.type_transaction, u.prenom, u.nom, c.numero_compte
         FROM transactions t
         JOIN comptes c ON t.compte_id = c.id
         JOIN utilisateurs u ON c.utilisateur_id = u.id
         ORDER BY t.date_transaction DESC
         LIMIT 5"
    );
    
    // Récupérer les dernières connexions
    $recentConnections = $db->select(
        "SELECT c.id, c.date_connexion, c.ip_adresse, c.statut, u.prenom, u.nom, u.email
         FROM connexions c
         JOIN utilisateurs u ON c.utilisateur_id = u.id
         ORDER BY c.date_connexion DESC
         LIMIT 10"
    );
    
    // Récupérer les demandes de prêts en attente
    $pendingLoans = $db->selectOne("SELECT COUNT(*) as total FROM demandes_prets WHERE statut = 'EN_ATTENTE'", []);
    $pendingLoans = $pendingLoans ? $pendingLoans['total'] : 0;
    
} catch (Exception $e) {
    // Journalisation de l'erreur
    error_log('Erreur tableau de bord admin: ' . $e->getMessage());
    
    // Message d'erreur pour l'utilisateur
    $error_message = "Une erreur est survenue lors du chargement des données. Veuillez réessayer ultérieurement.";
    
    // Valeurs par défaut en cas d'erreur
    $totalUsers = 0;
    $activeUsers = 0;
    $inactiveUsers = 0;
    $totalAccounts = 0;
    $recentUsers = [];
    $recentTransactions = [];
    $recentConnections = [];
    $pendingLoans = 0;
}

// Supprimer cette fonction ou la mettre en commentaire
/*
function formatDate($dateStr, $includeTime = false) {
    if (empty($dateStr)) return '-';
    $date = new DateTime($dateStr);
    return $includeTime ? $date->format('d/m/Y H:i') : $date->format('d/m/Y');
}
*/

// Supprimer ou commenter ces fonctions
/*
function getRoleBadgeClass($role) {
    switch ($role) {
        case 'ADMINISTRATEUR':
            return 'danger';
        case 'CLIENT':
            return 'primary';
        default:
            return 'secondary';
    }
}

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'ACTIF':
        case 'SUCCESS':
            return 'success';
        case 'BLOQUE':
            return 'danger';
        case 'INACTIF':
            return 'warning';
        default:
            return 'secondary';
    }
}

function formatMontant($montant) {
    return number_format($montant, 2, ',', ' ') . ' €';
}
*/

// Titre de la page
$pageTitle = 'Tableau de bord administrateur';
$currentPage = 'admin/index.php';

// Inclure l'en-tête
include_once '../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once '../templates/admin_sidebar.php'; ?>
        
        <!-- Contenu principal -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger mt-3" role="alert">
                    <?= $error_message ?>
                </div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Tableau de bord administrateur</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-download"></i> Exporter
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Cartes de statistiques -->
            <div class="row">
                <!-- Utilisateurs totaux -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card primary h-100">
                        <div class="card-body">
                            <div class="icon-circle">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-title">Utilisateurs totaux</div>
                            <div class="stat-value"><?= $totalUsers ?></div>
                            <div class="stat-text">Comptes enregistrés</div>
                        </div>
                    </div>
                </div>

                <!-- Utilisateurs actifs -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card success h-100">
                        <div class="card-body">
                            <div class="icon-circle">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="stat-title">Utilisateurs actifs</div>
                            <div class="stat-value"><?= $activeUsers ?></div>
                            <div class="stat-text">Comptes activés</div>
                        </div>
                    </div>
                </div>

                <!-- Utilisateurs inactifs -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card danger h-100">
                        <div class="card-body">
                            <div class="icon-circle">
                                <i class="fas fa-user-slash"></i>
                            </div>
                            <div class="stat-title">Utilisateurs inactifs</div>
                            <div class="stat-value"><?= $inactiveUsers ?></div>
                            <div class="stat-text">Comptes désactivés ou bloqués</div>
                        </div>
                    </div>
                </div>
                
                <!-- Comptes totaux -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card info h-100">
                        <div class="card-body">
                            <div class="icon-circle">
                                <i class="fas fa-university"></i>
                            </div>
                            <div class="stat-title">Comptes bancaires</div>
                            <div class="stat-value"><?= $totalAccounts ?></div>
                            <div class="stat-text">Comptes créés</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <!-- Demandes de prêts en attente -->
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card warning h-100">
                        <div class="card-body">
                            <div class="icon-circle">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                            <div class="stat-title">Demandes de prêts</div>
                            <div class="stat-value"><?= $pendingLoans ?></div>
                            <div class="stat-text">Dossiers en attente</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tableaux -->
            <div class="row">
                <!-- Utilisateurs récents -->
                <div class="col-lg-6 mb-4">
                    <div class="card dashboard-table">
                        <div class="card-header">
                            <h5><i class="fas fa-user-plus me-2"></i>Utilisateurs récemment inscrits</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>Email</th>
                                            <th>Statut</th>
                                            <th>Date d'inscription</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recentUsers)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">Aucun utilisateur récent</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recentUsers as $user): ?>
                                                <tr>
                                                    <td>
                                                        <?php if (!empty($user['avatar'])): ?>
                                                            <img src="../<?= htmlspecialchars($user['avatar']) ?>" 
                                                                 alt="Avatar" class="avatar-sm me-2">
                                                        <?php endif; ?>
                                                        <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= getStatusBadgeClass($user['statut']) ?>">
                                                            <?= htmlspecialchars($user['statut']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= formatDate($user['date_creation']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <a href="users.php" class="btn btn-primary btn-sm">
                                    Voir tous les utilisateurs
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Transactions récentes -->
                <div class="col-lg-6 mb-4">
                    <div class="card dashboard-table">
                        <div class="card-header">
                            <h5><i class="fas fa-exchange-alt me-2"></i>Dernières transactions</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Type</th>
                                            <th>Montant</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recentTransactions)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">Aucune transaction récente</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recentTransactions as $transaction): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($transaction['prenom'] . ' ' . $transaction['nom']) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $transaction['type_transaction'] == 'CREDIT' || $transaction['montant'] > 0 ? 'success' : 'danger' ?>">
                                                            <?= htmlspecialchars($transaction['type_transaction']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="<?= $transaction['montant'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                                        <?= formatMontant($transaction['montant']) ?>
                                                    </td>
                                                    <td><?= formatDate($transaction['date_transaction'], true) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <a href="transactions.php" class="btn btn-primary btn-sm">
                                    Voir toutes les transactions
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Connexions récentes -->
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card dashboard-table">
                        <div class="card-header">
                            <h5><i class="fas fa-sign-in-alt me-2"></i>Dernières connexions</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Utilisateur</th>
                                            <th>Email</th>
                                            <th>Adresse IP</th>
                                            <th>Statut</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recentConnections)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Aucune connexion récente</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recentConnections as $connexion): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($connexion['prenom'] . ' ' . $connexion['nom']) ?></td>
                                                    <td><?= htmlspecialchars($connexion['email']) ?></td>
                                                    <td><?= htmlspecialchars($connexion['ip_adresse']) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= getStatusBadgeClass($connexion['statut']) ?>">
                                                            <?= htmlspecialchars($connexion['statut']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= formatDate($connexion['date_connexion'], true) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <a href="logs.php" class="btn btn-primary btn-sm">
                                    Voir tous les logs de connexion
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Inclure le pied de page -->
<?php include_once '../templates/footer.php'; ?>