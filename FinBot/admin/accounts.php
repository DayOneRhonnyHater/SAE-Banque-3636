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

// Désactiver le mode test - nous utiliserons des données réelles
$test_mode = false;

// Initialisation des variables
$error_message = '';
$success_message = '';
$totalAccounts = 0;
$totalPages = 0;

// Récupération des paramètres de filtrage/pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Nombre de comptes par page
$offset = ($page - 1) * $limit;

// Traitement des actions sur les comptes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['compte_id'])) {
    $db = Database::getInstance();
    $compteId = intval($_POST['compte_id']);
    
    try {
        switch ($_POST['action']) {
            case 'freeze':
                $db->update('comptes', ['statut' => 'BLOQUE'], ['id' => $compteId]);
                $success_message = "Le compte #$compteId a été gelé avec succès.";
                break;
            case 'unfreeze':
                $db->update('comptes', ['statut' => 'ACTIF'], ['id' => $compteId]);
                $success_message = "Le compte #$compteId a été dégelé avec succès.";
                break;
            case 'close':
                $db->update('comptes', ['statut' => 'CLOTURE'], ['id' => $compteId]);
                $success_message = "Le compte #$compteId a été clôturé avec succès.";
                break;
            default:
                $error_message = "Action non reconnue.";
        }
    } catch (Exception $e) {
        $error_message = "Erreur lors de la modification du compte : " . $e->getMessage();
    }
}

// Récupération des données depuis la base de données
try {
    $db = Database::getInstance();
    
    // Construction de la requête de base
    $baseQuery = "FROM comptes c 
                 JOIN utilisateurs u ON c.utilisateur_id = u.id 
                 JOIN types_comptes tc ON c.type_compte_id = tc.id";
    
    $whereConditions = [];
    $params = [];
    
    // Ajout des conditions de filtrage
    if (!empty($search)) {
        $whereConditions[] = "(c.numero_compte LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($type)) {
        $whereConditions[] = "c.type_compte_id = ?";
        $params[] = $type;
    }
    
    if (!empty($status)) {
        // Convertir le statut de l'interface utilisateur au format de la base de données
        $dbStatus = 'ACTIF'; // Par défaut
        if ($status === 'frozen') $dbStatus = 'BLOQUE';
        if ($status === 'inactive') $dbStatus = 'CLOTURE';
        
        $whereConditions[] = "c.statut = ?";
        $params[] = $dbStatus;
    }
    
    // Finalisation de la clause WHERE
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = " WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Compter le nombre total de comptes qui correspondent aux critères
    $countQuery = "SELECT COUNT(*) as total " . $baseQuery . $whereClause;
    $result = $db->selectOne($countQuery, $params);
    $totalAccounts = $result ? $result['total'] : 0;
    $totalPages = ceil($totalAccounts / $limit);
    
    // S'assurer que la page demandée est valide
    if ($page < 1) $page = 1;
    if ($totalPages > 0 && $page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $limit;
    
    // Récupération des comptes avec pagination
    $selectQuery = "SELECT c.id, c.utilisateur_id, c.numero_compte, c.solde, c.date_creation, 
                    c.statut, c.type_compte_id, u.nom, u.prenom, u.email, tc.nom as type_nom 
                    " . $baseQuery . $whereClause . " 
                    ORDER BY c.date_creation DESC 
                    LIMIT ?, ?";
    
    $params[] = $offset;
    $params[] = $limit;
    
    $accounts = $db->select($selectQuery, $params);
    
    // Récupérer la liste des types de comptes pour le filtre
    $typeComptes = $db->select("SELECT id, nom FROM types_comptes WHERE actif = 1 ORDER BY nom");
    
} catch (Exception $e) {
    $error_message = "Une erreur est survenue lors du chargement des données: " . $e->getMessage();
    $accounts = [];
    $typeComptes = [];
}

// Titre de la page
$pageTitle = 'Gestion des comptes bancaires';
$currentPage = 'admin/accounts.php';

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
                <h1 class="h2">Gestion des comptes bancaires</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-download"></i> Exporter
                    </button>
                </div>
            </div>
            
            <!-- Filtres -->
            <div class="card mb-4">
                <div class="card-body">
                    <form action="" method="GET" class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Rechercher un client ou numéro de compte..." value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="type" name="type">
                                <option value="">Tous les types</option>
                                <?php foreach ($typeComptes as $typeCompte): ?>
                                    <option value="<?= htmlspecialchars($typeCompte['id']) ?>" <?= $type === $typeCompte['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($typeCompte['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="status" name="status">
                                <option value="">Tous les statuts</option>
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Actif</option>
                                <option value="frozen" <?= $status === 'frozen' ? 'selected' : '' ?>>Bloqué</option>
                                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Clôturé</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tableau des comptes -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-wallet me-1"></i>
                    Liste des comptes bancaires (<?= $totalAccounts ?>)
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th>Numéro de compte</th>
                                    <th>Type</th>
                                    <th>Solde</th>
                                    <th>Date d'ouverture</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($accounts)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Aucun compte bancaire trouvé</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($accounts as $account): ?>
                                        <tr>
                                            <td><?= $account['id'] ?></td>
                                            <td>
                                                <a href="users.php?id=<?= $account['utilisateur_id'] ?>">
                                                    <?= htmlspecialchars($account['prenom'] . ' ' . $account['nom']) ?>
                                                </a>
                                            </td>
                                            <td><span class="font-monospace"><?= htmlspecialchars($account['numero_compte']) ?></span></td>
                                            <td>
                                                <span class="badge bg-info"><?= htmlspecialchars($account['type_nom']) ?></span>
                                            </td>
                                            <td class="text-end">
                                                <span class="<?= $account['solde'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                                    <?= number_format($account['solde'], 2, ',', ' ') ?> €
                                                </span>
                                            </td>
                                            <td><?= formatDate($account['date_creation']) ?></td>
                                            <td>
                                                <?php
                                                switch ($account['statut']) {
                                                    case 'ACTIF':
                                                        echo '<span class="badge bg-success">Actif</span>';
                                                        break;
                                                    case 'BLOQUE':
                                                        echo '<span class="badge bg-warning">Bloqué</span>';
                                                        break;
                                                    case 'CLOTURE':
                                                        echo '<span class="badge bg-danger">Clôturé</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge bg-secondary">Inconnu</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                                            id="dropdownMenuButton<?= $account['id'] ?>" data-bs-toggle="dropdown" 
                                                            aria-expanded="false">
                                                        Actions
                                                    </button>
                                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?= $account['id'] ?>">
                                                        <li>
                                                            <a class="dropdown-item" href="view_account.php?id=<?= $account['id'] ?>">
                                                                <i class="fas fa-eye me-2"></i> Voir détails
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="account_transactions.php?id=<?= $account['id'] ?>">
                                                                <i class="fas fa-exchange-alt me-2"></i> Voir transactions
                                                            </a>
                                                        </li>
                                                        
                                                        <?php if ($account['statut'] === 'ACTIF'): ?>
                                                            <li>
                                                                <form action="" method="POST" style="display: inline;">
                                                                    <input type="hidden" name="compte_id" value="<?= $account['id'] ?>">
                                                                    <input type="hidden" name="action" value="freeze">
                                                                    <button type="submit" class="dropdown-item text-warning" 
                                                                            onclick="return confirm('Êtes-vous sûr de vouloir bloquer ce compte ?')">
                                                                        <i class="fas fa-ban me-2"></i> Bloquer le compte
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php elseif ($account['statut'] === 'BLOQUE'): ?>
                                                            <li>
                                                                <form action="" method="POST" style="display: inline;">
                                                                    <input type="hidden" name="compte_id" value="<?= $account['id'] ?>">
                                                                    <input type="hidden" name="action" value="unfreeze">
                                                                    <button type="submit" class="dropdown-item text-success" 
                                                                            onclick="return confirm('Êtes-vous sûr de vouloir débloquer ce compte ?')">
                                                                        <i class="fas fa-check-circle me-2"></i> Débloquer le compte
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($account['statut'] !== 'CLOTURE'): ?>
                                                            <li>
                                                                <form action="" method="POST" style="display: inline;">
                                                                    <input type="hidden" name="compte_id" value="<?= $account['id'] ?>">
                                                                    <input type="hidden" name="action" value="close">
                                                                    <button type="submit" class="dropdown-item text-danger" 
                                                                            onclick="return confirm('Êtes-vous sûr de vouloir clôturer ce compte ? Cette action est définitive.')">
                                                                        <i class="fas fa-times-circle me-2"></i> Clôturer le compte
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
                    <nav aria-label="Pagination des comptes">
                        <ul class="pagination justify-content-center mt-4">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type) ?>&status=<?= urlencode($status) ?>">Précédent</a>
                            </li>
                            
                            <?php 
                            // Afficher un nombre limité de liens de pagination
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            if ($startPage > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '&type=' . urlencode($type) . '&status=' . urlencode($status) . '">1</a></li>';
                                if ($startPage > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type) ?>&status=<?= urlencode($status) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor;
                            
                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&search=' . urlencode($search) . '&type=' . urlencode($type) . '&status=' . urlencode($status) . '">' . $totalPages . '</a></li>';
                            }
                            ?>
                            
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type) ?>&status=<?= urlencode($status) ?>">Suivant</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Inclure le pied de page -->
<?php include_once '../templates/footer.php'; ?>