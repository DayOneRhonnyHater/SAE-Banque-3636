<?php
// Inclusion des fichiers nécessaires
require_once '../includes/init.php';
require_once '../classes/Database.php';
require_once '../includes/functions.php';

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
$totalLogs = 0;
$totalPages = 0;

// Récupération des paramètres de filtrage/pagination
$action = isset($_GET['action']) ? $_GET['action'] : '';
$admin_id = isset($_GET['admin_id']) ? intval($_GET['admin_id']) : 0;
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20; // Nombre de logs par page

// Récupération des données depuis la base de données
try {
    $db = Database::getInstance();
    
    // Construction de la requête de base
    $baseQuery = "FROM logs_administrateur l 
                 JOIN utilisateurs u ON l.administrateur_id = u.id";
    
    $whereConditions = [];
    $params = [];
    
    // Ajout des conditions de filtrage
    if (!empty($search)) {
        $whereConditions[] = "(l.action LIKE ? OR l.details LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($action)) {
        $whereConditions[] = "l.action = ?";
        $params[] = $action;
    }
    
    if ($admin_id > 0) {
        $whereConditions[] = "l.administrateur_id = ?";
        $params[] = $admin_id;
    }
    
    if (!empty($date_debut)) {
        $whereConditions[] = "DATE(l.date_action) >= ?";
        $params[] = $date_debut;
    }
    
    if (!empty($date_fin)) {
        $whereConditions[] = "DATE(l.date_action) <= ?";
        $params[] = $date_fin;
    }
    
    // Finalisation de la clause WHERE
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = " WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Compter le nombre total de logs qui correspondent aux critères
    $countQuery = "SELECT COUNT(*) as total " . $baseQuery . $whereClause;
    $result = $db->selectOne($countQuery, $params);
    $totalLogs = $result ? $result['total'] : 0;
    
    // Calcul du nombre total de pages
    $totalPages = ceil($totalLogs / $limit);
    
    // S'assurer que la page demandée est valide
    if ($page < 1) $page = 1;
    if ($totalPages > 0 && $page > $totalPages) $page = $totalPages;
    
    // Calcul de l'offset pour la pagination
    $offset = ($page - 1) * $limit;
    
    // Récupération des logs avec pagination
    $selectQuery = "SELECT l.*, u.nom, u.prenom
                    " . $baseQuery . $whereClause . " 
                    ORDER BY l.date_action DESC 
                    LIMIT ?, ?";
    
    // Créer une copie des paramètres existants pour la pagination
    $paginationParams = array_merge($params, [$offset, $limit]);
    
    $logs = $db->select($selectQuery, $paginationParams);
    
    // Récupérer la liste des actions disponibles
    $actions = $db->select("SELECT DISTINCT action FROM logs_administrateur ORDER BY action");
    
    // Récupérer la liste des administrateurs
    $admins = $db->select("SELECT id, nom, prenom FROM utilisateurs WHERE role = 'ADMINISTRATEUR' ORDER BY nom, prenom");
    
    // Statistiques pour le tableau de bord
    $logsByAction = $db->select("SELECT action, COUNT(*) as count FROM logs_administrateur GROUP BY action ORDER BY count DESC LIMIT 5");
    $logsByAdmin = $db->select("SELECT u.id, u.nom, u.prenom, COUNT(*) as count 
                               FROM logs_administrateur l 
                               JOIN utilisateurs u ON l.administrateur_id = u.id 
                               GROUP BY l.administrateur_id 
                               ORDER BY count DESC 
                               LIMIT 5");
    $todayLogs = $db->selectOne("SELECT COUNT(*) as count FROM logs_administrateur WHERE DATE(date_action) = CURDATE()");
    $weekLogs = $db->selectOne("SELECT COUNT(*) as count FROM logs_administrateur WHERE date_action >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    
} catch (Exception $e) {
    $error_message = "Une erreur est survenue lors du chargement des données: " . $e->getMessage();
    $logs = [];
    $actions = [];
    $admins = [];
    $logsByAction = [];
    $logsByAdmin = [];
    $todayLogs = ['count' => 0];
    $weekLogs = ['count' => 0];
}

// Fonction pour obtenir la classe CSS en fonction de l'action (à garder, mais renommer pour éviter conflit)
function getActionBadgeClass($action) {
    switch ($action) {
        case 'LOGIN':
        case 'LOGOUT':
            return 'bg-primary';
        case 'DELETE_NOTIFICATION':
        case 'DELETE_USER':
        case 'DELETE_ACCOUNT':
            return 'bg-danger';
        case 'APPROVE_LOAN':
        case 'CREATE_NOTIFICATION':
        case 'CREATE_ACCOUNT':
        case 'CREATE_USER':
            return 'bg-success';
        case 'REJECT_LOAN':
        case 'BLOCK_USER':
        case 'UNBLOCK_USER':
            return 'bg-warning text-dark';
        case 'UPDATE_SETTINGS':
        case 'UPDATE_USER':
        case 'UPDATE_ACCOUNT':
            return 'bg-info text-dark';
        default:
            return 'bg-secondary';
    }
}

// Fonction pour obtenir l'icône en fonction de l'action (à garder, mais renommer pour éviter conflit)
function getActionIcon($action) {
    switch ($action) {
        case 'LOGIN':
            return 'sign-in-alt';
        case 'LOGOUT':
            return 'sign-out-alt';
        case 'DELETE_NOTIFICATION':
        case 'DELETE_USER':
        case 'DELETE_ACCOUNT':
            return 'trash-alt';
        case 'APPROVE_LOAN':
            return 'check-circle';
        case 'CREATE_NOTIFICATION':
            return 'bell';
        case 'CREATE_ACCOUNT':
        case 'CREATE_USER':
            return 'plus-circle';
        case 'REJECT_LOAN':
            return 'times-circle';
        case 'BLOCK_USER':
            return 'user-lock';
        case 'UNBLOCK_USER':
            return 'user-check';
        case 'UPDATE_SETTINGS':
            return 'cogs';
        case 'UPDATE_USER':
        case 'UPDATE_ACCOUNT':
            return 'edit';
        default:
            return 'history';
    }
}

// Titre de la page
$pageTitle = 'Journaux d\'activité administrateur';
$currentPage = 'admin/logs.php';

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
                <h1 class="h2">Journaux d'activité administrateur</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-download"></i> Exporter
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#">CSV</a>
                            <a class="dropdown-item" href="#">Excel</a>
                            <a class="dropdown-item" href="#">PDF</a>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnToggleFilters">
                        <i class="fas fa-filter"></i> Filtres
                    </button>
                </div>
            </div>
            
            <!-- Statistiques des logs -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-primary text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">Total actions</h5>
                                    <h2 class="mb-0"><?= number_format($totalLogs) ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-history fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-success text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">Aujourd'hui</h5>
                                    <h2 class="mb-0"><?= number_format($todayLogs['count'] ?? 0) ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-calendar-day fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-info text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">7 derniers jours</h5>
                                    <h2 class="mb-0"><?= number_format($weekLogs['count'] ?? 0) ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-calendar-week fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-warning text-dark mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">Administrateurs</h5>
                                    <h2 class="mb-0"><?= count($admins) ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-user-shield fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filtres avancés -->
            <div class="card mb-4" id="filtersCard" style="<?= !empty($search) || !empty($action) || $admin_id > 0 || !empty($date_debut) || !empty($date_fin) ? '' : 'display: none;' ?>">
                <div class="card-body">
                    <form action="" method="GET" class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Rechercher dans les logs..." value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="action" name="action">
                                <option value="">Toutes les actions</option>
                                <?php foreach ($actions as $act): ?>
                                    <option value="<?= htmlspecialchars($act['action']) ?>" <?= $action === $act['action'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($act['action']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="admin_id" name="admin_id">
                                <option value="0">Tous les administrateurs</option>
                                <?php foreach ($admins as $admin): ?>
                                    <option value="<?= $admin['id'] ?>" <?= $admin_id == $admin['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text">De</span>
                                <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?= htmlspecialchars($date_debut) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text">À</span>
                                <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?= htmlspecialchars($date_fin) ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                        </div>
                        <div class="col-md-4">
                            <a href="logs.php" class="btn btn-outline-secondary">
                                <i class="fas fa-redo"></i> Réinitialiser les filtres
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tableau des logs -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-history me-1"></i>
                    Journaux d'activité (<?= $totalLogs ?>)
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Administrateur</th>
                                    <th>Action</th>
                                    <th>Détails</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Aucun journal d'activité trouvé</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?= $log['id'] ?></td>
                                            <td><?= formatDate($log['date_action'], true) ?></td>
                                            <td>
                                                <a href="?admin_id=<?= $log['administrateur_id'] ?>">
                                                    <?= htmlspecialchars($log['prenom'] . ' ' . $log['nom']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <a href="?action=<?= urlencode($log['action']) ?>" class="text-decoration-none">
                                                    <span class="badge <?= getActionBadgeClass($log['action']) ?>">
                                                        <i class="fas fa-<?= getActionIcon($log['action']) ?> me-1"></i>
                                                        <?= htmlspecialchars($log['action']) ?>
                                                    </span>
                                                </a>
                                            </td>
                                            <td>
                                                <?php if (strlen($log['details']) > 100): ?>
                                                    <span data-bs-toggle="tooltip" title="<?= htmlspecialchars($log['details']) ?>">
                                                        <?= htmlspecialchars(substr($log['details'], 0, 100)) ?>...
                                                    </span>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($log['details']) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Pagination des logs">
                        <ul class="pagination justify-content-center mt-4">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&action=<?= urlencode($action) ?>&admin_id=<?= $admin_id ?>&date_debut=<?= urlencode($date_debut) ?>&date_fin=<?= urlencode($date_fin) ?>">Précédent</a>
                            </li>
                            
                            <?php 
                            // Afficher un nombre limité de liens de pagination
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            if ($startPage > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '&action=' . urlencode($action) . '&admin_id=' . $admin_id . '&date_debut=' . urlencode($date_debut) . '&date_fin=' . urlencode($date_fin) . '">1</a></li>';
                                if ($startPage > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&action=<?= urlencode($action) ?>&admin_id=<?= $admin_id ?>&date_debut=<?= urlencode($date_debut) ?>&date_fin=<?= urlencode($date_fin) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor;
                            
                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&search=' . urlencode($search) . '&action=' . urlencode($action) . '&admin_id=' . $admin_id . '&date_debut=' . urlencode($date_debut) . '&date_fin=' . urlencode($date_fin) . '">' . $totalPages . '</a></li>';
                            }
                            ?>
                            
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&action=<?= urlencode($action) ?>&admin_id=<?= $admin_id ?>&date_debut=<?= urlencode($date_debut) ?>&date_fin=<?= urlencode($date_fin) ?>">Suivant</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Graphiques et statistiques supplémentaires -->
            <div class="row">
                <!-- Top Actions -->
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-chart-pie me-1"></i>
                            Top 5 des actions
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <?php foreach ($logsByAction as $index => $actionStat): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge <?= getActionBadgeClass($actionStat['action']) ?> me-2">
                                                <i class="fas fa-<?= getActionIcon($actionStat['action']) ?>"></i>
                                            </span>
                                            <?= htmlspecialchars($actionStat['action']) ?>
                                        </div>
                                        <span class="badge bg-primary rounded-pill"><?= $actionStat['count'] ?></span>
                                    </li>
                                <?php endforeach; ?>
                                <?php if (empty($logsByAction)): ?>
                                    <li class="list-group-item text-center">Aucune donnée disponible</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Top Admins -->
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-users-cog me-1"></i>
                            Top 5 des administrateurs actifs
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <?php foreach ($logsByAdmin as $index => $adminStat): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-dark me-2">
                                                <?= $index + 1 ?>
                                            </span>
                                            <a href="?admin_id=<?= $adminStat['id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($adminStat['prenom'] . ' ' . $adminStat['nom']) ?>
                                            </a>
                                        </div>
                                        <span class="badge bg-primary rounded-pill"><?= $adminStat['count'] ?></span>
                                    </li>
                                <?php endforeach; ?>
                                <?php if (empty($logsByAdmin)): ?>
                                    <li class="list-group-item text-center">Aucune donnée disponible</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du toggle des filtres
    const btnToggleFilters = document.getElementById('btnToggleFilters');
    const filtersCard = document.getElementById('filtersCard');
    
    if (btnToggleFilters && filtersCard) {
        btnToggleFilters.addEventListener('click', function() {
            if (filtersCard.style.display === 'none') {
                filtersCard.style.display = 'block';
            } else {
                filtersCard.style.display = 'none';
            }
        });
    }
    
    // Initialiser les tooltips Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include_once '../templates/footer.php'; ?>