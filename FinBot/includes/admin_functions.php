<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\admin\includes\admin_functions.php

require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Database.php';

/**
 * Récupère tous les utilisateurs avec pagination
 * 
 * @param int $page Page courante
 * @param int $perPage Nombre d'éléments par page
 * @param string|null $search Terme de recherche
 * @param string|null $role Filtrer par rôle
 * @param string|null $status Filtrer par statut
 * @return array Utilisateurs et informations de pagination
 */
function getAdminUsers($page = 1, $perPage = 10, $search = null, $role = null, $status = null) {
    $db = Database::getInstance();
    
    // Construire la requête de base
    $sql = "SELECT u.*, 
                   CONCAT(c.prenom, ' ', c.nom) as conseiller_nom 
            FROM utilisateurs u 
            LEFT JOIN utilisateurs c ON u.conseiller_id = c.id";
    
    // Construire les conditions WHERE
    $conditions = [];
    $params = [];
    
    if ($search) {
        $conditions[] = "(u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($role) {
        $conditions[] = "u.role = ?";
        $params[] = $role;
    }
    
    if ($status) {
        $conditions[] = "u.statut = ?";
        $params[] = $status;
    }
    
    // Ajouter les conditions à la requête
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    // Compter le nombre total d'utilisateurs
    $countSql = "SELECT COUNT(*) as total FROM utilisateurs u";
    if (!empty($conditions)) {
        $countSql .= " WHERE " . implode(" AND ", $conditions);
    }
    $totalResult = $db->selectOne($countSql, $params);
    $total = $totalResult ? $totalResult['total'] : 0;
    
    // Calculer le nombre de pages
    $totalPages = ceil($total / $perPage);
    
    // Ajuster la page courante si nécessaire
    $page = max(1, min($page, $totalPages));
    
    // Calculer l'offset pour la pagination
    $offset = ($page - 1) * $perPage;
    
    // Ajouter l'ordre et la pagination
    $sql .= " ORDER BY u.nom, u.prenom LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    // Exécuter la requête
    $users = $db->select($sql, $params);
    
    // Retourner les utilisateurs et les informations de pagination
    return [
        'users' => $users,
        'pagination' => [
            'total' => $total,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ]
    ];
}

/**
 * Récupère les journaux d'activité avec pagination
 * 
 * @param int $page Page courante
 * @param int $perPage Nombre d'éléments par page
 * @param string|null $type Type de journal
 * @param string|null $search Terme de recherche
 * @return array Journaux et informations de pagination
 */
function getAdminLogs($page = 1, $perPage = 20, $type = null, $search = null) {
    $db = Database::getInstance();
    
    // Construire la requête de base
    $sql = "SELECT l.*, u.prenom, u.nom, u.email
            FROM logs l
            LEFT JOIN utilisateurs u ON l.utilisateur_id = u.id";
    
    // Construire les conditions WHERE
    $conditions = [];
    $params = [];
    
    if ($type) {
        $conditions[] = "l.type = ?";
        $params[] = $type;
    }
    
    if ($search) {
        $conditions[] = "(l.message LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Ajouter les conditions à la requête
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    // Compter le nombre total de journaux
    $countSql = "SELECT COUNT(*) as total FROM logs l";
    if (!empty($conditions)) {
        $countSql .= " WHERE " . implode(" AND ", $conditions);
    }
    $totalResult = $db->selectOne($countSql, $params);
    $total = $totalResult ? $totalResult['total'] : 0;
    
    // Calculer le nombre de pages
    $totalPages = ceil($total / $perPage);
    
    // Ajuster la page courante si nécessaire
    $page = max(1, min($page, $totalPages));
    
    // Calculer l'offset pour la pagination
    $offset = ($page - 1) * $perPage;
    
    // Ajouter l'ordre et la pagination
    $sql .= " ORDER BY l.date_creation DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    // Exécuter la requête
    $logs = $db->select($sql, $params);
    
    // Retourner les journaux et les informations de pagination
    return [
        'logs' => $logs,
        'pagination' => [
            'total' => $total,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ]
    ];
}

/**
 * Enregistre une action administrative dans les journaux
 * 
 * @param int $userId ID de l'utilisateur
 * @param string $action Action effectuée
 * @param string $details Détails de l'action
 * @return bool Succès de l'opération
 */
function logAdminAction($userId, $action, $details = '') {
    $db = Database::getInstance();
    
    try {
        $db->insert('logs', [
            'utilisateur_id' => $userId,
            'type' => 'ADMIN',
            'action' => $action,
            'message' => $details,
            'ip_adresse' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'date_creation' => date('Y-m-d H:i:s')
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log('Erreur lors de l\'enregistrement de l\'action admin: ' . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les statistiques pour le tableau de bord admin
 * 
 * @return array Statistiques du système
 */
function getAdminStats() {
    $db = Database::getInstance();
    
    $stats = [];
    
    // Nombre total d'utilisateurs par rôle
    $userStats = $db->select("
        SELECT role, COUNT(*) as count
        FROM utilisateurs
        GROUP BY role
    ");
    
    $stats['users'] = [
        'total' => 0,
        'roles' => []
    ];
    
    foreach ($userStats as $stat) {
        $stats['users']['total'] += $stat['count'];
        $stats['users']['roles'][$stat['role']] = $stat['count'];
    }
    
    // Nombre total de comptes
    $accountStats = $db->selectOne("
        SELECT COUNT(*) as count
        FROM comptes
    ");
    
    $stats['accounts'] = [
        'total' => $accountStats ? $accountStats['count'] : 0
    ];
    
    // Montant total des comptes
    $accountBalanceStats = $db->selectOne("
        SELECT SUM(solde) as total
        FROM comptes
    ");
    
    $stats['accounts']['balance'] = $accountBalanceStats ? $accountBalanceStats['total'] : 0;
    
    // Nombre de transactions aujourd'hui
    $todayStart = date('Y-m-d 00:00:00');
    $todayEnd = date('Y-m-d 23:59:59');
    
    $todayTransactions = $db->selectOne("
        SELECT COUNT(*) as count
        FROM transactions
        WHERE date_transaction BETWEEN ? AND ?
    ", [$todayStart, $todayEnd]);
    
    $stats['transactions'] = [
        'today' => $todayTransactions ? $todayTransactions['count'] : 0
    ];
    
    // Nombre de prêts en cours
    $activeLoans = $db->selectOne("
        SELECT COUNT(*) as count
        FROM prets
        WHERE statut = 'ACTIF'
    ");
    
    $stats['loans'] = [
        'active' => $activeLoans ? $activeLoans['count'] : 0
    ];
    
    // Nombre de connexions aujourd'hui
    $todayLogins = $db->selectOne("
        SELECT COUNT(*) as count
        FROM connexions
        WHERE date_connexion BETWEEN ? AND ?
    ", [$todayStart, $todayEnd]);
    
    $stats['logins'] = [
        'today' => $todayLogins ? $todayLogins['count'] : 0
    ];
    
    return $stats;
}

/**
 * Récupère les journaux d'activité avec filtres
 * 
 * @param int|string $userId Filtre par ID d'utilisateur
 * @param string $action Filtre par type d'action
 * @param string $dateFrom Filtre par date de début (YYYY-MM-DD)
 * @param string $dateTo Filtre par date de fin (YYYY-MM-DD)
 * @param int $limit Limite de résultats
 * @param int $offset Offset pour pagination
 * @return array Liste des journaux
 */
function getLogs($userId = '', $action = '', $dateFrom = '', $dateTo = '', $limit = 20, $offset = 0) {
    $db = Database::getInstance();
    
    $query = "SELECT l.*, CONCAT(u.prenom, ' ', u.nom) as username 
              FROM logs l
              LEFT JOIN utilisateurs u ON l.user_id = u.id
              WHERE 1=1";
    $params = [];
    
    // Appliquer les filtres
    if (!empty($userId)) {
        $query .= " AND l.user_id = ?";
        $params[] = $userId;
    }
    
    if (!empty($action)) {
        $query .= " AND l.action = ?";
        $params[] = $action;
    }
    
    if (!empty($dateFrom)) {
        $query .= " AND DATE(l.date_action) >= ?";
        $params[] = $dateFrom;
    }
    
    if (!empty($dateTo)) {
        $query .= " AND DATE(l.date_action) <= ?";
        $params[] = $dateTo;
    }
    
    $query .= " ORDER BY l.date_action DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    // Utiliser la méthode select() au lieu de prepare()
    return $db->select($query, $params);
}

/**
 * Compte le nombre total de journaux avec filtres
 */
function countLogs($userId = '', $action = '', $dateFrom = '', $dateTo = '') {
    $db = Database::getInstance();
    
    $query = "SELECT COUNT(*) as total FROM logs WHERE 1=1";
    $params = [];
    
    // Appliquer les filtres
    if (!empty($userId)) {
        $query .= " AND user_id = ?";
        $params[] = $userId;
    }
    
    if (!empty($action)) {
        $query .= " AND action = ?";
        $params[] = $action;
    }
    
    if (!empty($dateFrom)) {
        $query .= " AND DATE(date_action) >= ?";
        $params[] = $dateFrom;
    }
    
    if (!empty($dateTo)) {
        $query .= " AND DATE(date_action) <= ?";
        $params[] = $dateTo;
    }
    
    // Utiliser selectOne() au lieu de prepare()
    $result = $db->selectOne($query, $params);
    return $result ? $result['total'] : 0;
}

/**
 * Récupère tous les types d'actions distincts dans les journaux
 */
function getActionTypes() {
    $db = Database::getInstance();
    
    $query = "SELECT DISTINCT action FROM logs ORDER BY action";
    return $db->select($query, []);
}

/**
 * Récupère tous les utilisateurs pour le filtre
 */
function getAllUsers() {
    $db = Database::getInstance();
    
    $query = "SELECT id, prenom, nom FROM utilisateurs ORDER BY nom, prenom";
    return $db->select($query, []);
}

/**
 * Retourne la classe CSS pour le badge du type d'action
 * 
 * @param string $action Type d'action
 * @return string Classe CSS à utiliser
 */
function getActionBadgeClass($action) {
    switch ($action) {
        case 'LOGIN':
        case 'LOGOUT':
            return 'info';
        case 'USER_CREATE':
        case 'ACCOUNT_CREATE':
            return 'success';
        case 'USER_UPDATE':
        case 'ACCOUNT_UPDATE':
        case 'SETTINGS_UPDATE':
            return 'warning';
        case 'USER_DELETE':
        case 'ACCOUNT_DELETE':
            return 'danger';
        case 'TRANSACTION_CREATE':
            return 'primary';
        default:
            return 'secondary';
    }
}

/**
 * Retourne la classe CSS pour le badge du rôle utilisateur
 * 
 * @param string $role Rôle de l'utilisateur
 * @return string Classe CSS à utiliser
 */
function getRoleBadgeClass($role) {
    switch ($role) {
        case 'ADMINISTRATEUR':
            return 'danger';
        case 'CONSEILLER':
            return 'warning';
        case 'CLIENT':
            return 'success';
        default:
            return 'secondary';
    }
}

/**
 * Récupère le nombre total de transactions
 */
function getTotalTransactions() {
    $db = Database::getInstance();
    
    $result = $db->selectOne("SELECT COUNT(*) as total FROM transactions");
    return $result ? $result['total'] : 0;
}

/**
 * Récupère les activités récentes du journal
 * 
 * @param int $limit Nombre d'activités à récupérer
 * @return array Liste des activités récentes
 */
function getRecentActivities($limit = 10) {
    $db = Database::getInstance();
    
    $query = "SELECT l.*, CONCAT(u.prenom, ' ', u.nom) as username 
              FROM logs l
              LEFT JOIN utilisateurs u ON l.user_id = u.id
              ORDER BY l.date_action DESC LIMIT ?";
    
    return $db->select($query, [$limit]);
}

/**
 * Récupère les utilisateurs récemment inscrits
 * 
 * @param int $limit Nombre d'utilisateurs à récupérer
 * @return array Liste des utilisateurs récents
 */
function getRecentUsers($limit = 5) {
    $db = Database::getInstance();
    
    $query = "SELECT * FROM utilisateurs ORDER BY date_creation DESC LIMIT ?";
    return $db->select($query, [$limit]);
}

/**
 * Formate une date au format français
 * 
 * @param string $date Date à formater
 * @param bool $withTime Inclure l'heure
 * @return string Date formatée
 */
function formatDate($date, $withTime = true) {
    if (empty($date)) return '';
    
    $timestamp = strtotime($date);
    
    if ($withTime) {
        return date('d/m/Y à H:i', $timestamp);
    } else {
        return date('d/m/Y', $timestamp);
    }
}