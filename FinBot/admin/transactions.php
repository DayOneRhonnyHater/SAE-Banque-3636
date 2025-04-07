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
$totalTransactions = 0;
$totalPages = 0;

// Récupération des paramètres de filtrage/pagination
$type = isset($_GET['type']) ? $_GET['type'] : '';
$compte_id = isset($_GET['compte_id']) ? intval($_GET['compte_id']) : 0;
$montant_min = isset($_GET['montant_min']) && !empty($_GET['montant_min']) ? floatval($_GET['montant_min']) : 0;
$montant_max = isset($_GET['montant_max']) && !empty($_GET['montant_max']) ? floatval($_GET['montant_max']) : 0;
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';
$categorie = isset($_GET['categorie']) ? trim($_GET['categorie']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20; // Nombre de transactions par page

// Récupération des données depuis la base de données
try {
    $db = Database::getInstance();
    
    // Construction de la requête de base
    $baseQuery = "FROM transactions t 
                 JOIN comptes c ON t.compte_id = c.id
                 JOIN utilisateurs u ON c.utilisateur_id = u.id";
    
    $whereConditions = [];
    $params = [];
    
    // Ajout des conditions de filtrage
    if (!empty($search)) {
        $whereConditions[] = "(t.description LIKE ? OR t.beneficiaire LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($type)) {
        $whereConditions[] = "t.type_transaction = ?";
        $params[] = $type;
    }
    
    if ($compte_id > 0) {
        $whereConditions[] = "t.compte_id = ?";
        $params[] = $compte_id;
    }
    
    if (!empty($categorie)) {
        $whereConditions[] = "t.categorie = ?";
        $params[] = $categorie;
    }
    
    if ($montant_min > 0) {
        $whereConditions[] = "t.montant >= ?";
        $params[] = $montant_min;
    }
    
    if ($montant_max > 0) {
        $whereConditions[] = "t.montant <= ?";
        $params[] = $montant_max;
    }
    
    if (!empty($date_debut)) {
        $whereConditions[] = "DATE(t.date_transaction) >= ?";
        $params[] = $date_debut;
    }
    
    if (!empty($date_fin)) {
        $whereConditions[] = "DATE(t.date_transaction) <= ?";
        $params[] = $date_fin;
    }
    
    // Finalisation de la clause WHERE
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = " WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Compter le nombre total de transactions qui correspondent aux critères
    $countQuery = "SELECT COUNT(*) as total " . $baseQuery . $whereClause;
    $result = $db->selectOne($countQuery, $params);
    $totalTransactions = $result ? $result['total'] : 0;
    
    // Calcul du nombre total de pages
    $totalPages = ceil($totalTransactions / $limit);
    
    // S'assurer que la page demandée est valide
    if ($page < 1) $page = 1;
    if ($totalPages > 0 && $page > $totalPages) $page = $totalPages;
    
    // Calcul de l'offset pour la pagination
    $offset = ($page - 1) * $limit;
    
    // Récupération des transactions avec pagination
    $selectQuery = "SELECT t.*, c.numero_compte, c.utilisateur_id, u.nom, u.prenom
                    " . $baseQuery . $whereClause . " 
                    ORDER BY t.date_transaction DESC 
                    LIMIT ?, ?";
    
    // Créer une copie des paramètres existants pour la pagination
    $paginationParams = array_merge($params, [$offset, $limit]);
    
    $transactions = $db->select($selectQuery, $paginationParams);
    
    // Récupérer la liste des comptes pour le filtre
    $comptes = $db->select("SELECT c.id, c.numero_compte, u.nom, u.prenom 
                           FROM comptes c 
                           JOIN utilisateurs u ON c.utilisateur_id = u.id 
                           ORDER BY u.nom, u.prenom");
    
    // Récupérer la liste des catégories pour le filtre
    $categories = $db->select("SELECT DISTINCT categorie FROM transactions WHERE categorie IS NOT NULL AND categorie != '' ORDER BY categorie");
    
    // Calculer des statistiques avec des conditions WHERE pour éviter les erreurs
    $totalDebit = $db->selectOne("SELECT SUM(montant) as total FROM transactions WHERE type_transaction = 'DEBIT'");
    $totalCredit = $db->selectOne("SELECT SUM(montant) as total FROM transactions WHERE type_transaction = 'CREDIT'");
    $totalVirement = $db->selectOne("SELECT SUM(montant) as total FROM transactions WHERE type_transaction = 'VIREMENT'");
    $totalInteret = $db->selectOne("SELECT SUM(montant) as total FROM transactions WHERE type_transaction = 'INTERET'");
    
} catch (Exception $e) {
    $error_message = "Une erreur est survenue lors du chargement des données: " . $e->getMessage();
    $transactions = [];
    $comptes = [];
    $categories = [];
    $totalDebit = ['total' => 0];
    $totalCredit = ['total' => 0];
    $totalVirement = ['total' => 0];
    $totalInteret = ['total' => 0];
}

// Titre de la page
$pageTitle = 'Transactions';
$currentPage = 'admin/transactions.php';

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
                <h1 class="h2">Gestion des transactions</h1>
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
            
            <!-- Statistiques des transactions -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-success text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">Crédits</h5>
                                    <h2 class="mb-0"><?= number_format($totalCredit['total'] ?? 0, 2, ',', ' ') ?> €</h2>
                                </div>
                                <div>
                                    <i class="fas fa-arrow-circle-up fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-danger text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">Débits</h5>
                                    <h2 class="mb-0"><?= number_format($totalDebit['total'] ?? 0, 2, ',', ' ') ?> €</h2>
                                </div>
                                <div>
                                    <i class="fas fa-arrow-circle-down fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-primary text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">Virements</h5>
                                    <h2 class="mb-0"><?= number_format($totalVirement['total'] ?? 0, 2, ',', ' ') ?> €</h2>
                                </div>
                                <div>
                                    <i class="fas fa-exchange-alt fa-3x opacity-50"></i>
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
                                    <h5 class="mb-0">Intérêts</h5>
                                    <h2 class="mb-0"><?= number_format($totalInteret['total'] ?? 0, 2, ',', ' ') ?> €</h2>
                                </div>
                                <div>
                                    <i class="fas fa-percentage fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filtres avancés -->
            <div class="card mb-4" id="filtersCard" style="<?= !empty($search) || !empty($type) || !empty($categorie) || $compte_id > 0 || $montant_min > 0 || $montant_max > 0 || !empty($date_debut) || !empty($date_fin) ? '' : 'display: none;' ?>">
                <div class="card-body">
                    <form action="" method="GET" class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Rechercher dans les transactions..." value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="type" name="type">
                                <option value="">Tous les types</option>
                                <option value="CREDIT" <?= $type === 'CREDIT' ? 'selected' : '' ?>>Crédit</option>
                                <option value="DEBIT" <?= $type === 'DEBIT' ? 'selected' : '' ?>>Débit</option>
                                <option value="VIREMENT" <?= $type === 'VIREMENT' ? 'selected' : '' ?>>Virement</option>
                                <option value="INTERET" <?= $type === 'INTERET' ? 'selected' : '' ?>>Intérêt</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="categorie" name="categorie">
                                <option value="">Toutes les catégories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['categorie']) ?>" <?= $categorie === $cat['categorie'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['categorie']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" id="compte_id" name="compte_id">
                                <option value="0">Tous les comptes</option>
                                <?php foreach ($comptes as $compte): ?>
                                    <option value="<?= $compte['id'] ?>" <?= $compte_id == $compte['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($compte['numero_compte']) ?> - 
                                        <?= htmlspecialchars($compte['prenom'] . ' ' . $compte['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">Min €</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="montant_min" name="montant_min" value="<?= $montant_min > 0 ? $montant_min : '' ?>">
                                <span class="input-group-text">Max €</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="montant_max" name="montant_max" value="<?= $montant_max > 0 ? $montant_max : '' ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="input-group">
                                <span class="input-group-text">De</span>
                                <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?= htmlspecialchars($date_debut) ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="input-group">
                                <span class="input-group-text">À</span>
                                <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?= htmlspecialchars($date_fin) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-1"></i> Filtrer
                            </button>
                            <a href="transactions.php" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-redo me-1"></i> Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tableau des transactions -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-exchange-alt me-1"></i>
                    Transactions (<?= $totalTransactions ?>)
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Compte</th>
                                    <th>Client</th>
                                    <th>Montant</th>
                                    <th>Catégorie</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transactions)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">Aucune transaction trouvée</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td><?= $transaction['id'] ?></td>
                                            <td><?= formatDate($transaction['date_transaction'], true) ?></td>
                                            <td>
                                                <?php
                                                $typeClass = '';
                                                $typeIcon = '';
                                                switch ($transaction['type_transaction']) {
                                                    case 'CREDIT':
                                                        $typeClass = 'success';
                                                        $typeIcon = 'arrow-circle-up';
                                                        break;
                                                    case 'DEBIT':
                                                        $typeClass = 'danger';
                                                        $typeIcon = 'arrow-circle-down';
                                                        break;
                                                    case 'VIREMENT':
                                                        $typeClass = 'primary';
                                                        $typeIcon = 'exchange-alt';
                                                        break;
                                                    case 'INTERET':
                                                        $typeClass = 'info';
                                                        $typeIcon = 'percentage';
                                                        break;
                                                    default:
                                                        $typeClass = 'secondary';
                                                        $typeIcon = 'exchange-alt';
                                                }
                                                ?>
                                                <span class="badge bg-<?= $typeClass ?>">
                                                    <i class="fas fa-<?= $typeIcon ?> me-1"></i>
                                                    <?= $transaction['type_transaction'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="accounts.php?id=<?= $transaction['compte_id'] ?>">
                                                    <?= htmlspecialchars($transaction['numero_compte']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <a href="users.php?id=<?= $transaction['utilisateur_id'] ?? 0 ?>">
                                                    <?= htmlspecialchars($transaction['prenom'] . ' ' . $transaction['nom']) ?>
                                                </a>
                                            </td>
                                            <td class="<?= $transaction['type_transaction'] === 'DEBIT' ? 'text-danger' : ($transaction['type_transaction'] === 'CREDIT' || $transaction['type_transaction'] === 'INTERET' ? 'text-success' : '') ?>">
                                                <?= ($transaction['type_transaction'] === 'DEBIT' ? '-' : '') . number_format($transaction['montant'], 2, ',', ' ') ?> €
                                            </td>
                                            <td>
                                                <?php if (!empty($transaction['categorie'])): ?>
                                                    <span class="badge bg-secondary">
                                                        <?= htmlspecialchars($transaction['categorie']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Non catégorisé</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($transaction['description'])): ?>
                                                    <?php if (strlen($transaction['description']) > 40): ?>
                                                        <?= htmlspecialchars(substr($transaction['description'], 0, 40)) ?>...
                                                        <button type="button" class="btn btn-sm btn-link p-0" data-bs-toggle="modal" data-bs-target="#detailsModal" 
                                                                data-id="<?= $transaction['id'] ?>" 
                                                                data-description="<?= htmlspecialchars($transaction['description']) ?>">
                                                            Voir plus
                                                        </button>
                                                    <?php else: ?>
                                                        <?= htmlspecialchars($transaction['description']) ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Pas de description</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detailsModal" 
                                                        data-id="<?= $transaction['id'] ?>" 
                                                        data-description="<?= htmlspecialchars($transaction['description'] ?? '') ?>" 
                                                        data-beneficiaire="<?= htmlspecialchars($transaction['beneficiaire'] ?? '') ?>" 
                                                        data-montant="<?= $transaction['montant'] ?>" 
                                                        data-type="<?= $transaction['type_transaction'] ?>" 
                                                        data-date="<?= formatDate($transaction['date_transaction'], true) ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Pagination des transactions">
                        <ul class="pagination justify-content-center mt-4">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type) ?>&categorie=<?= urlencode($categorie) ?>&compte_id=<?= $compte_id ?>&montant_min=<?= $montant_min ?>&montant_max=<?= $montant_max ?>&date_debut=<?= urlencode($date_debut) ?>&date_fin=<?= urlencode($date_fin) ?>">Précédent</a>
                            </li>
                            
                            <?php 
                            // Afficher un nombre limité de liens de pagination
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            if ($startPage > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '&type=' . urlencode($type) . '&categorie=' . urlencode($categorie) . '&compte_id=' . $compte_id . '&montant_min=' . $montant_min . '&montant_max=' . $montant_max . '&date_debut=' . urlencode($date_debut) . '&date_fin=' . urlencode($date_fin) . '">1</a></li>';
                                if ($startPage > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type) ?>&categorie=<?= urlencode($categorie) ?>&compte_id=<?= $compte_id ?>&montant_min=<?= $montant_min ?>&montant_max=<?= $montant_max ?>&date_debut=<?= urlencode($date_debut) ?>&date_fin=<?= urlencode($date_fin) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor;
                            
                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&search=' . urlencode($search) . '&type=' . urlencode($type) . '&categorie=' . urlencode($categorie) . '&compte_id=' . $compte_id . '&montant_min=' . $montant_min . '&montant_max=' . $montant_max . '&date_debut=' . urlencode($date_debut) . '&date_fin=' . urlencode($date_fin) . '">' . $totalPages . '</a></li>';
                            }
                            ?>
                            
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type) ?>&categorie=<?= urlencode($categorie) ?>&compte_id=<?= $compte_id ?>&montant_min=<?= $montant_min ?>&montant_max=<?= $montant_max ?>&date_debut=<?= urlencode($date_debut) ?>&date_fin=<?= urlencode($date_fin) ?>">Suivant</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal pour afficher les détails d'une transaction -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Détails de la transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="transaction-details">
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">ID :</div>
                        <div class="col-8" id="modal-id"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Type :</div>
                        <div class="col-8" id="modal-type"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Date :</div>
                        <div class="col-8" id="modal-date"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Montant :</div>
                        <div class="col-8" id="modal-montant"></div>
                    </div>
                    <div class="row mb-2" id="row-beneficiaire">
                        <div class="col-4 fw-bold">Bénéficiaire :</div>
                        <div class="col-8" id="modal-beneficiaire"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4 fw-bold">Description :</div>
                        <div class="col-8" id="modal-description"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du toggle des filtres
    const btnToggleFilters = document.getElementById('btnToggleFilters');
    const filtersCard = document.getElementById('filtersCard');
    
    btnToggleFilters.addEventListener('click', function() {
        if (filtersCard.style.display === 'none') {
            filtersCard.style.display = 'block';
        } else {
            filtersCard.style.display = 'none';
        }
    });
    
    // Gestion du modal de détails
    const detailsModal = document.getElementById('detailsModal');
    if (detailsModal) {
        detailsModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const type = button.getAttribute('data-type');
            const date = button.getAttribute('data-date');
            const montant = button.getAttribute('data-montant');
            const beneficiaire = button.getAttribute('data-beneficiaire');
            const description = button.getAttribute('data-description');
            
            document.getElementById('modal-id').textContent = id || '';
            
            let typeDisplay = '';
            let typeClass = '';
            switch (type) {
                case 'CREDIT':
                    typeDisplay = 'Crédit';
                    typeClass = 'success';
                    break;
                case 'DEBIT':
                    typeDisplay = 'Débit';
                    typeClass = 'danger';
                    break;
                case 'VIREMENT':
                    typeDisplay = 'Virement';
                    typeClass = 'primary';
                    break;
                case 'INTERET':
                    typeDisplay = 'Intérêt';
                    typeClass = 'info';
                    break;
                default:
                    typeDisplay = type || '';
                    typeClass = 'secondary';
            }
            
            document.getElementById('modal-type').innerHTML = `<span class="badge bg-${typeClass}">${typeDisplay}</span>`;
            document.getElementById('modal-date').textContent = date || '';
            
            if (montant) {
                const formattedMontant = parseFloat(montant).toLocaleString('fr-FR', {
                    style: 'currency',
                    currency: 'EUR'
                });
                document.getElementById('modal-montant').textContent = (type === 'DEBIT' ? '-' : '') + formattedMontant;
            } else {
                document.getElementById('modal-montant').textContent = '0,00 €';
            }
            
            if (beneficiaire && beneficiaire !== 'null' && beneficiaire !== '') {
                document.getElementById('row-beneficiaire').style.display = 'flex';
                document.getElementById('modal-beneficiaire').textContent = beneficiaire;
            } else {
                document.getElementById('row-beneficiaire').style.display = 'none';
            }
            
            document.getElementById('modal-description').textContent = description || 'Pas de description';
        });
    }
});

// Fonction pour formater le numéro de compte
function formatCompteNumber(numero) {
    if (!numero) return '';
    // Format IBAN
    if (numero.length === 34) {
        return numero.replace(/(.{4})/g, '$1 ').trim();
    }
    // Format numéro de compte classique
    return numero;
}
</script>

<?php include_once '../templates/footer.php'; ?>