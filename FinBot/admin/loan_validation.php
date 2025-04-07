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
$totalRequests = 0;
$totalPages = 0;

// Récupération des paramètres de filtrage/pagination
$status = isset($_GET['status']) ? $_GET['status'] : 'pending'; // par défaut: demandes en attente
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Nombre de demandes par page
$offset = ($page - 1) * $limit;

// Traitement des actions sur les demandes de prêts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['request_id'])) {
    $db = Database::getInstance();
    $requestId = intval($_POST['request_id']);
    $action = $_POST['action'];
    $motif = isset($_POST['motif']) ? trim($_POST['motif']) : '';
    
    try {
        // Obtenir l'ID de l'administrateur qui valide/refuse la demande (ici fixé à 1 pour l'exemple)
        $adminId = 1; // Dans un système réel, cela serait l'ID de l'administrateur connecté
        $now = date('Y-m-d H:i:s');
        
        if ($action === 'approve') {
            // Approuver la demande de prêt
            $db->update('demandes_prets', [
                'statut' => 'APPROUVE',
                'date_traitement' => $now,
                'administrateur_id' => $adminId
            ], ['id' => $requestId]);
            
            // Récupérer les informations de la demande pour créer le prêt
            $demande = $db->selectOne("SELECT * FROM demandes_prets WHERE id = ?", [$requestId]);
            
            if ($demande) {
                // Enregistrer dans les logs administrateur
                $db->insert('logs_administrateur', [
                    'administrateur_id' => $adminId,
                    'action' => 'APPROVE_LOAN',
                    'details' => "Approbation de la demande de prêt #$requestId pour un montant de {$demande['montant']}€",
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'date_action' => $now
                ]);
                
                // Créer une notification pour l'utilisateur
                $db->insert('notifications', [
                    'utilisateur_id' => $demande['utilisateur_id'],
                    'titre' => 'Demande de prêt approuvée',
                    'message' => "Votre demande de prêt d'un montant de {$demande['montant']}€ a été approuvée. Consultez votre espace client pour plus de détails.",
                    'type' => 'PRET',
                    'date_creation' => $now,
                    'lue' => 0
                ]);
                
                $success_message = "La demande de prêt #$requestId a été approuvée avec succès.";
            } else {
                $error_message = "Impossible de trouver la demande de prêt #$requestId.";
            }
        } elseif ($action === 'reject') {
            // Vérifier que le motif de refus est fourni
            if (empty($motif)) {
                $error_message = "Vous devez fournir un motif de refus.";
            } else {
                // Refuser la demande de prêt
                $db->update('demandes_prets', [
                    'statut' => 'REFUSE',
                    'motif_refus' => $motif,
                    'date_traitement' => $now,
                    'administrateur_id' => $adminId
                ], ['id' => $requestId]);
                
                // Récupérer les informations de la demande
                $demande = $db->selectOne("SELECT * FROM demandes_prets WHERE id = ?", [$requestId]);
                
                if ($demande) {
                    // Enregistrer dans les logs administrateur
                    $db->insert('logs_administrateur', [
                        'administrateur_id' => $adminId,
                        'action' => 'REJECT_LOAN',
                        'details' => "Refus de la demande de prêt #$requestId - Motif: $motif",
                        'ip_address' => $_SERVER['REMOTE_ADDR'],
                        'date_action' => $now
                    ]);
                    
                    // Créer une notification pour l'utilisateur
                    $db->insert('notifications', [
                        'utilisateur_id' => $demande['utilisateur_id'],
                        'titre' => 'Demande de prêt refusée',
                        'message' => "Votre demande de prêt d'un montant de {$demande['montant']}€ a été refusée. Motif: $motif",
                        'type' => 'PRET',
                        'date_creation' => $now,
                        'lue' => 0
                    ]);
                    
                    $success_message = "La demande de prêt #$requestId a été refusée.";
                } else {
                    $error_message = "Impossible de trouver la demande de prêt #$requestId.";
                }
            }
        } else {
            $error_message = "Action non reconnue.";
        }
    } catch (Exception $e) {
        $error_message = "Erreur lors du traitement de la demande : " . $e->getMessage();
    }
}

// Récupération des données depuis la base de données
try {
    $db = Database::getInstance();
    
    // Construction de la requête de base
    $baseQuery = "FROM demandes_prets dp 
                 JOIN utilisateurs u ON dp.utilisateur_id = u.id";
    
    $whereConditions = [];
    $params = [];
    
    // Ajout des conditions de filtrage
    if (!empty($search)) {
        $whereConditions[] = "(u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    // Filtrage par statut
    if ($status === 'pending') {
        $whereConditions[] = "dp.statut = 'EN_ATTENTE'";
    } elseif ($status === 'approved') {
        $whereConditions[] = "dp.statut = 'APPROUVE'";
    } elseif ($status === 'rejected') {
        $whereConditions[] = "dp.statut = 'REFUSE'";
    }
    
    // Finalisation de la clause WHERE
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = " WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Compter le nombre total de demandes qui correspondent aux critères
    $countQuery = "SELECT COUNT(*) as total " . $baseQuery . $whereClause;
    $result = $db->selectOne($countQuery, $params);
    $totalRequests = $result ? $result['total'] : 0;
    $totalPages = ceil($totalRequests / $limit);
    
    // S'assurer que la page demandée est valide
    if ($page < 1) $page = 1;
    if ($totalPages > 0 && $page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $limit;
    
    // Récupération des demandes de prêts avec pagination
    $selectQuery = "SELECT dp.*, u.nom, u.prenom, u.email, u.telephone 
                    " . $baseQuery . $whereClause . " 
                    ORDER BY dp.date_demande DESC 
                    LIMIT ?, ?";
    
    $params[] = $offset;
    $params[] = $limit;
    
    $loanRequests = $db->select($selectQuery, $params);
    
} catch (Exception $e) {
    $error_message = "Une erreur est survenue lors du chargement des données: " . $e->getMessage();
    $loanRequests = [];
}

// Titre de la page
$pageTitle = 'Gestion des demandes de prêts';
$currentPage = 'admin/loan_requests.php';

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
                <h1 class="h2">Gestion des demandes de prêts</h1>
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
                        <div class="col-md-5">
                            <div class="input-group">
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Rechercher un client..." value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <select class="form-select" id="status" name="status">
                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>En attente</option>
                                <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approuvées</option>
                                <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Refusées</option>
                                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Toutes les demandes</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tableau des demandes de prêts -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-money-check-alt me-1"></i>
                    <?php
                    $statusLabel = 'Toutes les demandes';
                    if ($status === 'pending') $statusLabel = 'Demandes en attente';
                    if ($status === 'approved') $statusLabel = 'Demandes approuvées';
                    if ($status === 'rejected') $statusLabel = 'Demandes refusées';
                    ?>
                    <?= $statusLabel ?> (<?= $totalRequests ?>)
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th>Montant</th>
                                    <th>Durée</th>
                                    <th>Taux</th>
                                    <th>Mensualité</th>
                                    <th>Motif</th>
                                    <th>Date demande</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($loanRequests)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center">Aucune demande de prêt trouvée</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($loanRequests as $request): ?>
                                        <tr>
                                            <td><?= $request['id'] ?></td>
                                            <td>
                                                <a href="users.php?id=<?= $request['utilisateur_id'] ?>">
                                                    <?= htmlspecialchars($request['prenom'] . ' ' . $request['nom']) ?>
                                                </a>
                                                <div class="small text-muted"><?= htmlspecialchars($request['email']) ?></div>
                                            </td>
                                            <td>
                                                <strong><?= number_format($request['montant'], 2, ',', ' ') ?> €</strong>
                                            </td>
                                            <td><?= $request['duree'] ?> mois</td>
                                            <td><?= number_format($request['taux'], 2) ?> %</td>
                                            <td><?= number_format($request['mensualite'], 2, ',', ' ') ?> €</td>
                                            <td>
                                                <?php if (!empty($request['motif'])): ?>
                                                    <?= htmlspecialchars(substr($request['motif'], 0, 30)) ?>
                                                    <?= strlen($request['motif']) > 30 ? '...' : '' ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= formatDate($request['date_demande'], true) ?></td>
                                            <td>
                                                <?php
                                                switch ($request['statut']) {
                                                    case 'EN_ATTENTE':
                                                        echo '<span class="badge bg-warning">En attente</span>';
                                                        break;
                                                    case 'APPROUVE':
                                                        echo '<span class="badge bg-success">Approuvée</span>';
                                                        break;
                                                    case 'REFUSE':
                                                        echo '<span class="badge bg-danger">Refusée</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge bg-secondary">Inconnu</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="view_loan_request.php?id=<?= $request['id'] ?>" 
                                                       class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <?php if ($request['statut'] === 'EN_ATTENTE'): ?>
                                                        <button type="button" class="btn btn-sm btn-success" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#approveModal<?= $request['id'] ?>">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#rejectModal<?= $request['id'] ?>">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                        
                                                        <!-- Modal d'approbation -->
                                                        <div class="modal fade" id="approveModal<?= $request['id'] ?>" 
                                                             tabindex="-1" aria-labelledby="approveModalLabel<?= $request['id'] ?>" 
                                                             aria-hidden="true">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="approveModalLabel<?= $request['id'] ?>">
                                                                            Approuver la demande de prêt
                                                                        </h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <p>Êtes-vous sûr de vouloir approuver cette demande de prêt ?</p>
                                                                        <div class="alert alert-info">
                                                                            <h6>Détails de la demande :</h6>
                                                                            <ul class="mb-0">
                                                                                <li>Client : <?= htmlspecialchars($request['prenom'] . ' ' . $request['nom']) ?></li>
                                                                                <li>Montant : <?= number_format($request['montant'], 2, ',', ' ') ?> €</li>
                                                                                <li>Durée : <?= $request['duree'] ?> mois</li>
                                                                                <li>Taux : <?= number_format($request['taux'], 2) ?> %</li>
                                                                                <li>Mensualité : <?= number_format($request['mensualite'], 2, ',', ' ') ?> €</li>
                                                                            </ul>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                        <form action="" method="POST">
                                                                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                                                            <input type="hidden" name="action" value="approve">
                                                                            <button type="submit" class="btn btn-success">Approuver</button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Modal de refus -->
                                                        <div class="modal fade" id="rejectModal<?= $request['id'] ?>" 
                                                             tabindex="-1" aria-labelledby="rejectModalLabel<?= $request['id'] ?>" 
                                                             aria-hidden="true">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="rejectModalLabel<?= $request['id'] ?>">
                                                                            Refuser la demande de prêt
                                                                        </h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                                                    </div>
                                                                    <form action="" method="POST">
                                                                        <div class="modal-body">
                                                                            <p>Veuillez indiquer le motif de refus de cette demande :</p>
                                                                            <div class="alert alert-info mb-3">
                                                                                <h6>Détails de la demande :</h6>
                                                                                <ul class="mb-0">
                                                                                    <li>Client : <?= htmlspecialchars($request['prenom'] . ' ' . $request['nom']) ?></li>
                                                                                    <li>Montant : <?= number_format($request['montant'], 2, ',', ' ') ?> €</li>
                                                                                    <li>Durée : <?= $request['duree'] ?> mois</li>
                                                                                </ul>
                                                                            </div>
                                                                            <div class="form-group">
                                                                                <label for="motif<?= $request['id'] ?>" class="form-label">Motif du refus <span class="text-danger">*</span></label>
                                                                                <textarea class="form-control" id="motif<?= $request['id'] ?>" name="motif" rows="3" required></textarea>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                                                            <input type="hidden" name="action" value="reject">
                                                                            <button type="submit" class="btn btn-danger">Refuser</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
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
                    <nav aria-label="Pagination des demandes de prêt">
                        <ul class="pagination justify-content-center mt-4">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">Précédent</a>
                            </li>
                            
                            <?php 
                            // Afficher un nombre limité de liens de pagination
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            if ($startPage > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '&status=' . urlencode($status) . '">1</a></li>';
                                if ($startPage > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor;
                            
                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&search=' . urlencode($search) . '&status=' . urlencode($status) . '">' . $totalPages . '</a></li>';
                            }
                            ?>
                            
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">Suivant</a>
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