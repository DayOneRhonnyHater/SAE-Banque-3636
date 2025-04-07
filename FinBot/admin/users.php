<?php
// Inclusion des fichiers nécessaires
require_once '../includes/init.php';
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

// Initialisation des variables
$error_message = '';
$success_message = '';
$totalUsers = 0;
$totalPages = 0;

// Récupération des paramètres de filtrage/pagination
$status = isset($_GET['status']) ? $_GET['status'] : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Nombre d'utilisateurs par page
$offset = ($page - 1) * $limit;

// Traitement des actions sur les utilisateurs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['user_id'])) {
    $db = Database::getInstance();
    $userId = intval($_POST['user_id']);
    $action = $_POST['action'];
    
    try {
        $now = date('Y-m-d H:i:s');
        
        if ($action === 'block') {
            // Bloquer l'utilisateur
            $db->update('utilisateurs', ['statut' => 'BLOQUE'], ['id' => $userId]);
            
            // Mettre à jour la table de sécurité
            $db->update('securite', [
                'compte_bloque' => 1,
                'date_blocage' => $now
            ], ['utilisateur_id' => $userId]);
            
            $success_message = "L'utilisateur #$userId a été bloqué avec succès.";
        } elseif ($action === 'unblock') {
            // Débloquer l'utilisateur
            $db->update('utilisateurs', ['statut' => 'ACTIF'], ['id' => $userId]);
            
            // Mettre à jour la table de sécurité
            $db->update('securite', [
                'compte_bloque' => 0,
                'tentatives_connexion' => 0,
                'date_blocage' => null
            ], ['utilisateur_id' => $userId]);
            
            $success_message = "L'utilisateur #$userId a été débloqué avec succès.";
        } elseif ($action === 'deactivate') {
            // Désactiver l'utilisateur
            $db->update('utilisateurs', ['statut' => 'INACTIF'], ['id' => $userId]);
            
            $success_message = "L'utilisateur #$userId a été désactivé avec succès.";
        } elseif ($action === 'activate') {
            // Activer l'utilisateur
            $db->update('utilisateurs', ['statut' => 'ACTIF'], ['id' => $userId]);
            
            $success_message = "L'utilisateur #$userId a été activé avec succès.";
        } else {
            $error_message = "Action non reconnue.";
        }
    } catch (Exception $e) {
        $error_message = "Erreur lors du traitement de l'action : " . $e->getMessage();
    }
}

// Récupération des données depuis la base de données
try {
    $db = Database::getInstance();
    
    // Construction de la requête de base
    $baseQuery = "FROM utilisateurs u LEFT JOIN securite s ON u.id = s.utilisateur_id";
    
    $whereConditions = [];
    $params = [];
    
    // Ajout des conditions de filtrage
    if (!empty($search)) {
        $whereConditions[] = "(u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ? OR u.telephone LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    // Filtrage par statut
    if ($status === 'active') {
        $whereConditions[] = "u.statut = 'ACTIF'";
    } elseif ($status === 'blocked') {
        $whereConditions[] = "u.statut = 'BLOQUE'";
    } elseif ($status === 'inactive') {
        $whereConditions[] = "u.statut = 'INACTIF'";
    }
    
    // Filtrage par rôle
    if ($role === 'admin') {
        $whereConditions[] = "u.role = 'ADMINISTRATEUR'";
    } elseif ($role === 'client') {
        $whereConditions[] = "u.role = 'CLIENT'";
    }
    
    // Finalisation de la clause WHERE
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = " WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Compter le nombre total d'utilisateurs qui correspondent aux critères
    $countQuery = "SELECT COUNT(*) as total " . $baseQuery . $whereClause;
    $result = $db->selectOne($countQuery, $params);
    $totalUsers = $result ? $result['total'] : 0;
    $totalPages = ceil($totalUsers / $limit);
    
    // S'assurer que la page demandée est valide
    if ($page < 1) $page = 1;
    if ($totalPages > 0 && $page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $limit;
    
    // Récupération des utilisateurs avec pagination
    $selectQuery = "SELECT u.*, s.tentatives_connexion, s.compte_bloque, s.date_blocage 
                    " . $baseQuery . $whereClause . " 
                    ORDER BY u.date_creation DESC 
                    LIMIT ?, ?";
    
    $params[] = $offset;
    $params[] = $limit;
    
    $users = $db->select($selectQuery, $params);
    
    // Récupérer des statistiques supplémentaires pour chaque utilisateur
    foreach ($users as &$user) {
        // Nombre de comptes
        $countAccounts = $db->selectOne("SELECT COUNT(*) as total FROM comptes WHERE utilisateur_id = ?", [$user['id']]);
        $user['compte_count'] = $countAccounts ? $countAccounts['total'] : 0;
        
        // Solde total
        $totalBalance = $db->selectOne("SELECT SUM(solde) as total FROM comptes WHERE utilisateur_id = ?", [$user['id']]);
        $user['solde_total'] = $totalBalance && $totalBalance['total'] ? $totalBalance['total'] : 0;
        
        // Dernière connexion
        $lastLogin = $db->selectOne("SELECT date_connexion FROM connexions WHERE utilisateur_id = ? ORDER BY date_connexion DESC LIMIT 1", [$user['id']]);
        $user['derniere_connexion'] = $lastLogin ? $lastLogin['date_connexion'] : null;
    }
    
    // Statistiques pour le tableau de bord
    $activeCount = $db->selectOne("SELECT COUNT(*) as count FROM utilisateurs WHERE statut = 'ACTIF'");
    $activeCount = $activeCount ? $activeCount['count'] : 0;
    
    $blockedCount = $db->selectOne("SELECT COUNT(*) as count FROM utilisateurs WHERE statut = 'BLOQUE'");
    $blockedCount = $blockedCount ? $blockedCount['count'] : 0;
    
    $adminCount = $db->selectOne("SELECT COUNT(*) as count FROM utilisateurs WHERE role = 'ADMINISTRATEUR'");
    $adminCount = $adminCount ? $adminCount['count'] : 0;
    
    $twoFACount = $db->selectOne("SELECT COUNT(*) as count FROM utilisateurs WHERE deux_facteurs = 1");
    $twoFACount = $twoFACount ? $twoFACount['count'] : 0;
    
} catch (Exception $e) {
    $error_message = "Une erreur est survenue lors du chargement des données: " . $e->getMessage();
    $users = [];
    $activeCount = 0;
    $blockedCount = 0;
    $adminCount = 0;
    $twoFACount = 0;
}

// Titre de la page
$pageTitle = 'Gestion des utilisateurs';
$currentPage = 'admin/users.php';

// Inclure l'en-tête
include_once '../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once '../templates/admin_sidebar.php'; ?>
        
        <!-- Contenu principal -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger mt-3" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success mt-3" role="alert">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Gestion des utilisateurs</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add_user.php" class="btn btn-sm btn-outline-primary me-2">
                        <i class="fas fa-user-plus"></i> Ajouter un utilisateur
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-download"></i> Exporter
                    </button>
                </div>
            </div>
            
            <!-- Statistiques -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-primary text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">Utilisateurs actifs</h5>
                                    <h2 class="mb-0"><?= $activeCount ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-users fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a class="small text-white stretched-link" href="?status=active">Voir détails</a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-warning text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">Utilisateurs bloqués</h5>
                                    <h2 class="mb-0"><?= $blockedCount ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-user-lock fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a class="small text-white stretched-link" href="?status=blocked">Voir détails</a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-success text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">Administrateurs</h5>
                                    <h2 class="mb-0"><?= $adminCount ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-user-shield fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a class="small text-white stretched-link" href="?role=admin">Voir détails</a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-danger text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">2FA activée</h5>
                                    <h2 class="mb-0"><?= $twoFACount ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-shield-alt fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a class="small text-white stretched-link" href="#">Voir détails</a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filtres -->
            <div class="card mb-4">
                <div class="card-body">
                    <form action="" method="GET" class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Rechercher un utilisateur..." value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="status" name="status">
                                <option value="">Tous les statuts</option>
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Actifs</option>
                                <option value="blocked" <?= $status === 'blocked' ? 'selected' : '' ?>>Bloqués</option>
                                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactifs</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="role" name="role">
                                <option value="">Tous les rôles</option>
                                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrateurs</option>
                                <option value="client" <?= $role === 'client' ? 'selected' : '' ?>>Clients</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tableau des utilisateurs -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-users me-1"></i>
                    Liste des utilisateurs (<?= $totalUsers ?>)
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom complet</th>
                                    <th>Email / Téléphone</th>
                                    <th>Rôle</th>
                                    <th>Statut</th>
                                    <th>Comptes</th>
                                    <th>Solde total</th>
                                    <th>Inscription</th>
                                    <th>Dernière connexion</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center">Aucun utilisateur trouvé</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= $user['id'] ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm me-2 bg-<?= getStatusBadgeClass($user['statut']) ?>">
                                                        <span><?= strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)) ?></span>
                                                    </div>
                                                    <div>
                                                        <a href="view_user.php?id=<?= $user['id'] ?>" class="text-decoration-none">
                                                            <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div><?= htmlspecialchars($user['email']) ?></div>
                                                <?php if (!empty($user['telephone'])): ?>
                                                    <small class="text-muted"><?= htmlspecialchars($user['telephone']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= getRoleBadgeClass($user['role']) ?>">
                                                    <?= $user['role'] === 'ADMINISTRATEUR' ? 'Admin' : 'Client' ?>
                                                </span>
                                                <?php if ($user['deux_facteurs']): ?>
                                                    <span class="badge bg-info ms-1" title="Authentification à deux facteurs activée">
                                                        <i class="fas fa-shield-alt"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                switch ($user['statut']) {
                                                    case 'ACTIF':
                                                        echo '<span class="badge bg-success">Actif</span>';
                                                        break;
                                                    case 'BLOQUE':
                                                        echo '<span class="badge bg-danger">Bloqué</span>';
                                                        break;
                                                    case 'INACTIF':
                                                        echo '<span class="badge bg-warning">Inactif</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge bg-secondary">Inconnu</span>';
                                                }
                                                ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="accounts.php?user_id=<?= $user['id'] ?>" class="badge bg-primary text-decoration-none">
                                                    <?= $user['compte_count'] ?>
                                                </a>
                                            </td>
                                            <td class="text-end">
                                                <span class="<?= $user['solde_total'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                                    <?= number_format($user['solde_total'], 2, ',', ' ') ?> €
                                                </span>
                                            </td>
                                            <td title="<?= formatDate($user['date_creation'], true) ?>">
                                                <?= formatDate($user['date_creation']) ?>
                                            </td>
                                            <td>
                                                <?= $user['derniere_connexion'] ? formatDate($user['derniere_connexion'], true) : '<span class="text-muted">Jamais</span>' ?>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                                            id="dropdownMenuButton<?= $user['id'] ?>" data-bs-toggle="dropdown" 
                                                            aria-expanded="false">
                                                        Actions
                                                    </button>
                                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?= $user['id'] ?>">
                                                        <li>
                                                            <a class="dropdown-item" href="view_user.php?id=<?= $user['id'] ?>">
                                                                <i class="fas fa-eye me-2"></i> Voir détails
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="edit_user.php?id=<?= $user['id'] ?>">
                                                                <i class="fas fa-edit me-2"></i> Modifier
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="accounts.php?user_id=<?= $user['id'] ?>">
                                                                <i class="fas fa-wallet me-2"></i> Voir comptes
                                                            </a>
                                                        </li>
                                                        
                                                        <?php if ($user['statut'] === 'ACTIF'): ?>
                                                            <li>
                                                                <hr class="dropdown-divider">
                                                            </li>
                                                            <li>
                                                                <form action="" method="POST" style="display: inline;">
                                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                    <input type="hidden" name="action" value="block">
                                                                    <button type="submit" class="dropdown-item text-warning" 
                                                                            onclick="return confirm('Êtes-vous sûr de vouloir bloquer cet utilisateur ?')">
                                                                        <i class="fas fa-ban me-2"></i> Bloquer l'utilisateur
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <form action="" method="POST" style="display: inline;">
                                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                    <input type="hidden" name="action" value="deactivate">
                                                                    <button type="submit" class="dropdown-item text-danger" 
                                                                            onclick="return confirm('Êtes-vous sûr de vouloir désactiver cet utilisateur ?')">
                                                                        <i class="fas fa-user-slash me-2"></i> Désactiver l'utilisateur
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php elseif ($user['statut'] === 'BLOQUE'): ?>
                                                            <li>
                                                                <hr class="dropdown-divider">
                                                            </li>
                                                            <li>
                                                                <form action="" method="POST" style="display: inline;">
                                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                    <input type="hidden" name="action" value="unblock">
                                                                    <button type="submit" class="dropdown-item text-success" 
                                                                            onclick="return confirm('Êtes-vous sûr de vouloir débloquer cet utilisateur ?')">
                                                                        <i class="fas fa-unlock me-2"></i> Débloquer l'utilisateur
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php elseif ($user['statut'] === 'INACTIF'): ?>
                                                            <li>
                                                                <hr class="dropdown-divider">
                                                            </li>
                                                            <li>
                                                                <form action="" method="POST" style="display: inline;">
                                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                    <input type="hidden" name="action" value="activate">
                                                                    <button type="submit" class="dropdown-item text-success" 
                                                                            onclick="return confirm('Êtes-vous sûr de vouloir réactiver cet utilisateur ?')">
                                                                        <i class="fas fa-user-check me-2"></i> Réactiver l'utilisateur
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Pagination des utilisateurs">
                        <ul class="pagination justify-content-center mt-4">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&role=<?= urlencode($role) ?>">Précédent</a>
                            </li>
                            
                            <?php 
                            // Afficher un nombre limité de liens de pagination
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            if ($startPage > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '&status=' . urlencode($status) . '&role=' . urlencode($role) . '">1</a></li>';
                                if ($startPage > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&role=<?= urlencode($role) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor;
                            
                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&search=' . urlencode($search) . '&status=' . urlencode($status) . '&role=' . urlencode($role) . '">' . $totalPages . '</a></li>';
                            }
                            ?>
                            
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&role=<?= urlencode($role) ?>">Suivant</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 0.8rem;
}
</style>

<!-- Inclure le pied de page -->
<?php include_once '../templates/footer.php'; ?>