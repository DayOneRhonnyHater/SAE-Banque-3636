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
$totalNotifications = 0;
$totalPages = 0;

// Récupération des paramètres de filtrage/pagination
$type = isset($_GET['type']) ? $_GET['type'] : '';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 15; // Nombre de notifications par page

// Traitement des actions (suppression, envoi de notification)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance();
    
    // Action de suppression d'une notification
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['notification_id'])) {
        $notificationId = intval($_POST['notification_id']);
        
        try {
            $db->delete('notifications', ['id' => $notificationId]);
            
            // Log de l'action administrateur
            $adminId = $_SESSION['user']['id'];
            $db->insert('logs_administrateur', [
                'administrateur_id' => $adminId,
                'action' => 'DELETE_NOTIFICATION',
                'details' => "Suppression de la notification #$notificationId",
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'date_action' => date('Y-m-d H:i:s')
            ]);
            
            $success_message = "La notification #$notificationId a été supprimée avec succès.";
        } catch (Exception $e) {
            $error_message = "Erreur lors de la suppression de la notification : " . $e->getMessage();
        }
    }
    
    // Action d'envoi d'une nouvelle notification
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $destinataires = isset($_POST['destinataires']) ? $_POST['destinataires'] : [];
        $titre = isset($_POST['titre']) ? trim($_POST['titre']) : '';
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        $type_notif = isset($_POST['type_notif']) ? $_POST['type_notif'] : 'AUTRE';
        $lien = isset($_POST['lien']) ? trim($_POST['lien']) : '';
        
        // Validation
        if (empty($destinataires)) {
            $error_message = "Veuillez sélectionner au moins un destinataire.";
        } elseif (empty($titre)) {
            $error_message = "Le titre est obligatoire.";
        } elseif (empty($message)) {
            $error_message = "Le message est obligatoire.";
        } else {
            try {
                $now = date('Y-m-d H:i:s');
                $adminId = $_SESSION['user']['id'];
                $countNotifications = 0;
                
                // Traitement des destinataires
                if (in_array('all', $destinataires)) {
                    // Envoyer à tous les utilisateurs
                    $users = $db->select("SELECT id FROM utilisateurs WHERE statut = 'ACTIF'");
                    
                    foreach ($users as $user) {
                        $db->insert('notifications', [
                            'utilisateur_id' => $user['id'],
                            'titre' => $titre,
                            'message' => $message,
                            'type' => $type_notif,
                            'date_creation' => $now,
                            'lue' => 0,
                            'lien' => $lien
                        ]);
                        $countNotifications++;
                    }
                } else {
                    // Envoyer aux utilisateurs spécifiques
                    foreach ($destinataires as $userId) {
                        if ($userId != 'all') {
                            $db->insert('notifications', [
                                'utilisateur_id' => intval($userId),
                                'titre' => $titre,
                                'message' => $message,
                                'type' => $type_notif,
                                'date_creation' => $now,
                                'lue' => 0,
                                'lien' => $lien
                            ]);
                            $countNotifications++;
                        }
                    }
                }
                
                // Log de l'action administrateur
                $db->insert('logs_administrateur', [
                    'administrateur_id' => $adminId,
                    'action' => 'CREATE_NOTIFICATION',
                    'details' => "Envoi de $countNotifications notification(s) - Titre: $titre",
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'date_action' => $now
                ]);
                
                $success_message = "$countNotifications notification(s) ont été envoyées avec succès.";
            } catch (Exception $e) {
                $error_message = "Erreur lors de l'envoi des notifications : " . $e->getMessage();
            }
        }
    }
}

// Récupération des données depuis la base de données
try {
    $db = Database::getInstance();
    
    // Construction de la requête de base
    $baseQuery = "FROM notifications n 
                 JOIN utilisateurs u ON n.utilisateur_id = u.id";
    
    $whereConditions = [];
    $params = [];
    
    // Ajout des conditions de filtrage
    if (!empty($search)) {
        $whereConditions[] = "(n.titre LIKE ? OR n.message LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($type)) {
        $whereConditions[] = "n.type = ?";
        $params[] = $type;
    }
    
    if ($user_id > 0) {
        $whereConditions[] = "n.utilisateur_id = ?";
        $params[] = $user_id;
    }
    
    if (!empty($date_debut)) {
        $whereConditions[] = "DATE(n.date_creation) >= ?";
        $params[] = $date_debut;
    }
    
    if (!empty($date_fin)) {
        $whereConditions[] = "DATE(n.date_creation) <= ?";
        $params[] = $date_fin;
    }
    
    if (!empty($status)) {
        if ($status === 'read') {
            $whereConditions[] = "n.lue = 1";
        } elseif ($status === 'unread') {
            $whereConditions[] = "n.lue = 0";
        }
    }
    
    // Finalisation de la clause WHERE
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = " WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Compter le nombre total de notifications qui correspondent aux critères
    $countQuery = "SELECT COUNT(*) as total " . $baseQuery . $whereClause;
    $result = $db->selectOne($countQuery, $params);
    $totalNotifications = $result ? $result['total'] : 0;
    $totalPages = ceil($totalNotifications / $limit);
    
    // S'assurer que la page demandée est valide
    if ($page < 1) $page = 1;
    if ($totalPages > 0 && $page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $limit;
    
    // Récupération des notifications avec pagination
    $selectQuery = "SELECT n.*, u.nom, u.prenom, u.email 
                    " . $baseQuery . $whereClause . " 
                    ORDER BY n.date_creation DESC 
                    LIMIT ?, ?";
    
    // Créer une copie des paramètres existants pour la pagination
    $paginationParams = array_merge($params, [$offset, $limit]);
    
    $notifications = $db->select($selectQuery, $paginationParams);
    
    // Statistiques pour le tableau de bord
    $notifByType = $db->select("SELECT type, COUNT(*) as count FROM notifications GROUP BY type ORDER BY count DESC");
    $totalUnread = $db->selectOne("SELECT COUNT(*) as count FROM notifications WHERE lue = 0");
    $totalRead = $db->selectOne("SELECT COUNT(*) as count FROM notifications WHERE lue = 1");
    $recentNotifs = $db->selectOne("SELECT COUNT(*) as count FROM notifications WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    
    // Récupérer la liste des utilisateurs pour le filtre et le formulaire d'envoi
    $users = $db->select("SELECT id, nom, prenom, email FROM utilisateurs WHERE statut = 'ACTIF' ORDER BY nom, prenom");
    
} catch (Exception $e) {
    $error_message = "Une erreur est survenue lors du chargement des données: " . $e->getMessage();
    $notifications = [];
    $users = [];
    $notifByType = [];
    $totalUnread = ['count' => 0];
    $totalRead = ['count' => 0];
    $recentNotifs = ['count' => 0];
}

// Fonction d'aide pour obtenir la classe de badge selon le type
function getTypeBadgeClass($type) {
    switch ($type) {
        case 'TRANSACTION':
            return 'success';
        case 'PRET':
            return 'primary';
        case 'FACTURE':
            return 'warning';
        case 'SECURITE':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Titre de la page
$pageTitle = 'Gestion des notifications';
$currentPage = 'admin/notifications.php';

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
                <h1 class="h2">Gestion des notifications</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#createNotificationModal">
                        <i class="fas fa-bell"></i> Nouvelle notification
                    </button>
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
            
            <!-- Statistiques de notifications -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-primary text-white mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">Total notifications</h5>
                                    <h2 class="mb-0"><?= number_format($totalNotifications) ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-bell fa-3x opacity-50"></i>
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
                                    <h5 class="mb-0">Non lues</h5>
                                    <h2 class="mb-0"><?= number_format($totalUnread['count'] ?? 0) ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-bell-slash fa-3x opacity-50"></i>
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
                                    <h5 class="mb-0">Lues</h5>
                                    <h2 class="mb-0"><?= number_format($totalRead['count'] ?? 0) ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-check-circle fa-3x opacity-50"></i>
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
                                    <h5 class="mb-0">Dernières 24h</h5>
                                    <h2 class="mb-0"><?= number_format($recentNotifs['count'] ?? 0) ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-clock fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filtres avancés -->
            <div class="card mb-4" id="filtersCard" style="<?= !empty($search) || !empty($type) || !empty($status) || $user_id > 0 || !empty($date_debut) || !empty($date_fin) ? '' : 'display: none;' ?>">
                <div class="card-body">
                    <form action="" method="GET" class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Rechercher dans les notifications..." value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="type" name="type">
                                <option value="">Tous les types</option>
                                <option value="TRANSACTION" <?= $type === 'TRANSACTION' ? 'selected' : '' ?>>Transaction</option>
                                <option value="PRET" <?= $type === 'PRET' ? 'selected' : '' ?>>Prêt</option>
                                <option value="FACTURE" <?= $type === 'FACTURE' ? 'selected' : '' ?>>Facture</option>
                                <option value="SECURITE" <?= $type === 'SECURITE' ? 'selected' : '' ?>>Sécurité</option>
                                <option value="AUTRE" <?= $type === 'AUTRE' ? 'selected' : '' ?>>Autre</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="status" name="status">
                                <option value="">Tous les statuts</option>
                                <option value="read" <?= $status === 'read' ? 'selected' : '' ?>>Lues</option>
                                <option value="unread" <?= $status === 'unread' ? 'selected' : '' ?>>Non lues</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" id="user_id" name="user_id">
                                <option value="0">Tous les utilisateurs</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= $user_id == $user['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>
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
                        <div class="col-md-12 text-end mt-2">
                            <a href="notifications.php" class="btn btn-outline-secondary">
                                <i class="fas fa-redo"></i> Réinitialiser les filtres
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tableau des notifications -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-bell me-1"></i>
                    Liste des notifications (<?= $totalNotifications ?>)
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Utilisateur</th>
                                    <th>Type</th>
                                    <th>Titre</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($notifications)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Aucune notification trouvée</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notification): ?>
                                        <tr class="<?= $notification['lue'] ? '' : 'table-light fw-bold' ?>">
                                            <td><?= $notification['id'] ?></td>
                                            <td><?= formatDate($notification['date_creation'], true) ?></td>
                                            <td>
                                                <a href="users.php?id=<?= $notification['utilisateur_id'] ?>">
                                                    <?= htmlspecialchars($notification['prenom'] . ' ' . $notification['nom']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= getTypeBadgeClass($notification['type']) ?>">
                                                    <?= htmlspecialchars($notification['type']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($notification['titre']) ?>
                                            </td>
                                            <td>
                                                <?php if ($notification['lue']): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i> Lue
                                                        <?php if ($notification['date_lecture']): ?>
                                                            le <?= formatDate($notification['date_lecture'], true) ?>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times me-1"></i> Non lue
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        data-bs-toggle="modal" data-bs-target="#notificationModal<?= $notification['id'] ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette notification?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                
                                                <!-- Modal détail notification -->
                                                <div class="modal fade" id="notificationModal<?= $notification['id'] ?>" tabindex="-1" 
                                                     aria-labelledby="notificationModalLabel<?= $notification['id'] ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="notificationModalLabel<?= $notification['id'] ?>">
                                                                    Détails de la notification #<?= $notification['id'] ?>
                                                                </h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <div class="d-flex justify-content-between mb-2">
                                                                        <span class="badge bg-<?= getTypeBadgeClass($notification['type']) ?> mb-2">
                                                                            <?= htmlspecialchars($notification['type']) ?>
                                                                        </span>
                                                                        <small class="text-muted">
                                                                            <?= formatDate($notification['date_creation'], true) ?>
                                                                        </small>
                                                                    </div>
                                                                    <h5 class="card-title"><?= htmlspecialchars($notification['titre']) ?></h5>
                                                                    <p class="card-text"><?= nl2br(htmlspecialchars($notification['message'])) ?></p>
                                                                    
                                                                    <?php if (!empty($notification['lien'])): ?>
                                                                    <div class="mt-3">
                                                                        <strong>Lien:</strong> 
                                                                        <a href="<?= htmlspecialchars($notification['lien']) ?>" target="_blank">
                                                                            <?= htmlspecialchars($notification['lien']) ?>
                                                                        </a>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <div class="mt-3">
                                                                        <strong>Destinataire:</strong> 
                                                                        <?= htmlspecialchars($notification['prenom'] . ' ' . $notification['nom']) ?> 
                                                                        (<?= htmlspecialchars($notification['email']) ?>)
                                                                    </div>
                                                                    
                                                                    <div class="mt-2">
                                                                        <strong>Statut:</strong> 
                                                                        <?php if ($notification['lue']): ?>
                                                                            <span class="text-success">
                                                                                <i class="fas fa-check me-1"></i> Lue
                                                                                <?php if ($notification['date_lecture']): ?>
                                                                                    le <?= formatDate($notification['date_lecture'], true) ?>
                                                                                <?php endif; ?>
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <span class="text-danger">
                                                                                <i class="fas fa-times me-1"></i> Non lue
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <form method="POST">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                                                    <button type="submit" class="btn btn-danger" 
                                                                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette notification?')">
                                                                        <i class="fas fa-trash me-1"></i> Supprimer
                                                                    </button>
                                                                </form>
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                            </div>
                                                        </div>
                                                    </div>
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
                    <nav aria-label="Pagination des notifications">
                        <ul class="pagination justify-content-center mt-4">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type) ?>&status=<?= urlencode($status) ?>&user_id=<?= $user_id ?>&date_debut=<?= urlencode($date_debut) ?>&date_fin=<?= urlencode($date_fin) ?>">Précédent</a>
                            </li>
                            
                            <?php 
                            // Afficher un nombre limité de liens de pagination
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            if ($startPage > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '&type=' . urlencode($type) . '&status=' . urlencode($status) . '&user_id=' . $user_id . '&date_debut=' . urlencode($date_debut) . '&date_fin=' . urlencode($date_fin) . '">1</a></li>';
                                if ($startPage > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type) ?>&status=<?= urlencode($status) ?>&user_id=<?= $user_id ?>&date_debut=<?= urlencode($date_debut) ?>&date_fin=<?= urlencode($date_fin) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor;
                            
                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&search=' . urlencode($search) . '&type=' . urlencode($type) . '&status=' . urlencode($status) . '&user_id=' . $user_id . '&date_debut=' . urlencode($date_debut) . '&date_fin=' . urlencode($date_fin) . '">' . $totalPages . '</a></li>';
                            }
                            ?>
                            
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type) ?>&status=<?= urlencode($status) ?>&user_id=<?= $user_id ?>&date_debut=<?= urlencode($date_debut) ?>&date_fin=<?= urlencode($date_fin) ?>">Suivant</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal pour créer une nouvelle notification -->
<div class="modal fade" id="createNotificationModal" tabindex="-1" aria-labelledby="createNotificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createNotificationModalLabel">
                    <i class="fas fa-bell me-2"></i> Envoyer une nouvelle notification
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="destinataires" class="form-label">Destinataires</label>
                        <div class="d-flex mb-2">
                            <div class="form-check me-3">
                                <input class="form-check-input" type="checkbox" id="dest_all" name="destinataires[]" value="all">
                                <label class="form-check-label" for="dest_all">
                                    <strong>Tous les utilisateurs</strong>
                                </label>
                            </div>
                        </div>
                        <div class="user-select-scroll border p-3 rounded" style="max-height: 150px; overflow-y: auto;">
                            <?php foreach ($users as $user): ?>
                                <div class="form-check">
                                    <input class="form-check-input user-select" type="checkbox" id="user<?= $user['id'] ?>" name="destinataires[]" value="<?= $user['id'] ?>">
                                    <label class="form-check-label" for="user<?= $user['id'] ?>">
                                        <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="type_notif" class="form-label">Type de notification</label>
                        <select class="form-select" id="type_notif" name="type_notif" required>
                            <option value="TRANSACTION">Transaction</option>
                            <option value="PRET">Prêt</option>
                            <option value="FACTURE">Facture</option>
                            <option value="SECURITE">Sécurité</option>
                            <option value="AUTRE" selected>Autre</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="titre" class="form-label">Titre</label>
                        <input type="text" class="form-control" id="titre" name="titre" maxlength="100" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="lien" class="form-label">Lien (optionnel)</label>
                        <input type="text" class="form-control" id="lien" name="lien" maxlength="191">
                        <small class="form-text text-muted">URL à laquelle l'utilisateur sera redirigé en cliquant sur la notification.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i> Envoyer la notification
                    </button>
                </div>
            </form>
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
    
    // Gestion de la sélection "Tous les utilisateurs"
    const destAll = document.getElementById('dest_all');
    const userCheckboxes = document.querySelectorAll('.user-select');
    
    if (destAll && userCheckboxes.length > 0) {
        destAll.addEventListener('change', function() {
            if (this.checked) {
                userCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                    checkbox.disabled = true;
                });
            } else {
                userCheckboxes.forEach(checkbox => {
                    checkbox.disabled = false;
                });
            }
        });
        
        userCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    destAll.checked = false;
                }
            });
        });
    }
});
</script>

<?php include_once '../templates/footer.php'; ?>