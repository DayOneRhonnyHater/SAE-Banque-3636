<?php
/**
 * Fonction pour récupérer la connexion à la base de données
 * @param string $env L'environnement à utiliser (default, testing, development, production)
 * @return PDO L'objet PDO de connexion à la base de données
 */
function getDbConnection($env = 'default') {
    static $connections = [];
    
    // Si la connexion pour cet environnement existe déjà, la retourner
    if (isset($connections[$env])) {
        return $connections[$env];
    }
    
    // Charger la configuration
    $config = require __DIR__ . '/../config/database.php';
    
    // Vérifier si l'environnement demandé existe
    if (!isset($config[$env])) {
        throw new Exception("Configuration d'environnement '$env' non trouvée");
    }
    
    // Récupérer les paramètres pour l'environnement demandé
    $dbConfig = $config[$env];
    
    // Construire le DSN
    $dsn = "{$dbConfig['driver']}:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    
    // Créer la connexion PDO
    try {
        $connections[$env] = new PDO(
            $dsn, 
            $dbConfig['username'], 
            $dbConfig['password'], 
            $dbConfig['options']
        );
        return $connections[$env];
    } catch (PDOException $e) {
        // Log l'erreur ou gérer l'exception comme nécessaire
        throw new Exception("Erreur de connexion à la base de données: " . $e->getMessage());
    }
}

/**
 * Récupère la liste des comptes bancaires avec filtres et pagination
 *
 * @param string $search Terme de recherche (nom client ou numéro de compte)
 * @param string $type Type de compte (COURANT, EPARGNE, TITRE)
 * @param string $status Statut du compte (active, frozen, inactive)
 * @param int $page Numéro de page
 * @param int $limit Nombre d'éléments par page
 * @param string $env Environnement de base de données à utiliser
 * @return array Liste des comptes bancaires
 */
function getBankAccounts($search = '', $type = '', $status = '', $page = 1, $limit = 10, $env = 'default') {
    // Obtenir la connexion à la base de données
    $db = getDbConnection($env);
    
    $offset = ($page - 1) * $limit;
    
    // Construction de la requête avec les filtres
    $sql = "SELECT c.*, CONCAT(u.prenom, ' ', u.nom) AS nom_client 
            FROM comptes_bancaires c
            INNER JOIN utilisateurs u ON c.user_id = u.id
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR c.numero LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($type)) {
        $sql .= " AND c.type = ?";
        $params[] = $type;
    }
    
    if (!empty($status)) {
        $sql .= " AND c.statut = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY c.id DESC LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $limit;
    
    // Préparation et exécution de la requête
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Compte le nombre total de comptes bancaires avec les filtres appliqués
 *
 * @param string $search Terme de recherche (nom client ou numéro de compte)
 * @param string $type Type de compte (COURANT, EPARGNE, TITRE)
 * @param string $status Statut du compte (active, frozen, inactive)
 * @param string $env Environnement de base de données à utiliser
 * @return int Nombre total de comptes
 */
function countBankAccounts($search = '', $type = '', $status = '', $env = 'default') {
    // Obtenir la connexion à la base de données
    $db = getDbConnection($env);
    
    // Construction de la requête avec les filtres
    $sql = "SELECT COUNT(*) AS total
            FROM comptes_bancaires c
            INNER JOIN utilisateurs u ON c.user_id = u.id
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR c.numero LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($type)) {
        $sql .= " AND c.type = ?";
        $params[] = $type;
    }
    
    if (!empty($status)) {
        $sql .= " AND c.statut = ?";
        $params[] = $status;
    }
    
    // Préparation et exécution de la requête
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return (int) $result['total'];
}

/**
 * Récupère la liste des demandes de prêt avec filtres et pagination
 *
 * @param string $search Terme de recherche (nom client)
 * @param string $status Statut de la demande (pending, approved, rejected, all)
 * @param int $page Numéro de page
 * @param int $limit Nombre d'éléments par page
 * @param string $env Environnement de base de données à utiliser
 * @return array Liste des demandes de prêt
 */
function getLoanRequests($search = '', $status = 'pending', $page = 1, $limit = 10, $env = 'default') {
    // Obtenir la connexion à la base de données
    $db = getDbConnection($env);
    
    $offset = ($page - 1) * $limit;
    
    // Construction de la requête avec les filtres
    $sql = "SELECT p.*, CONCAT(u.prenom, ' ', u.nom) AS nom_client 
            FROM prets p
            INNER JOIN utilisateurs u ON p.user_id = u.id
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($status !== 'all') {
        $sql .= " AND p.statut = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY p.date_demande DESC LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $limit;
    
    // Préparation et exécution de la requête
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Compte le nombre total de demandes de prêt avec les filtres appliqués
 *
 * @param string $search Terme de recherche (nom client)
 * @param string $status Statut de la demande (pending, approved, rejected, all)
 * @param string $env Environnement de base de données à utiliser
 * @return int Nombre total de demandes de prêt
 */
function countLoanRequests($search = '', $status = 'pending', $env = 'default') {
    // Obtenir la connexion à la base de données
    $db = getDbConnection($env);
    
    // Construction de la requête avec les filtres
    $sql = "SELECT COUNT(*) AS total
            FROM prets p
            INNER JOIN utilisateurs u ON p.user_id = u.id
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($status !== 'all') {
        $sql .= " AND p.statut = ?";
        $params[] = $status;
    }
    
    // Préparation et exécution de la requête
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return (int) $result['total'];
}

/**
 * Récupère la liste des transactions avec filtres et pagination
 *
 * @param string $accountFilter ID du compte bancaire
 * @param string $userFilter ID de l'utilisateur
 * @param string $typeFilter Type de transaction
 * @param float $minAmount Montant minimum
 * @param float $maxAmount Montant maximum 
 * @param string $dateFrom Date de début (format Y-m-d)
 * @param string $dateTo Date de fin (format Y-m-d)
 * @param int $limit Nombre d'éléments par page
 * @param int $offset Offset pour la pagination
 * @param string $env Environnement de base de données à utiliser
 * @return array Liste des transactions
 */
function getTransactions($accountFilter = '', $userFilter = '', $typeFilter = '', $minAmount = '', $maxAmount = '', $dateFrom = '', $dateTo = '', $limit = 10, $offset = 0, $env = 'default') {
    // Obtenir la connexion à la base de données
    $db = getDbConnection($env);
    
    // Construction de la requête avec les filtres
    $sql = "SELECT t.*, c.numero as account_number, CONCAT(u.prenom, ' ', u.nom) AS username
            FROM transactions t
            INNER JOIN comptes_bancaires c ON t.account_id = c.id
            INNER JOIN utilisateurs u ON c.user_id = u.id
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($accountFilter)) {
        $sql .= " AND t.account_id = ?";
        $params[] = $accountFilter;
    }
    
    if (!empty($userFilter)) {
        $sql .= " AND c.user_id = ?";
        $params[] = $userFilter;
    }
    
    if (!empty($typeFilter)) {
        $sql .= " AND t.type = ?";
        $params[] = $typeFilter;
    }
    
    if (!empty($minAmount)) {
        $sql .= " AND t.amount >= ?";
        $params[] = $minAmount;
    }
    
    if (!empty($maxAmount)) {
        $sql .= " AND t.amount <= ?";
        $params[] = $maxAmount;
    }
    
    if (!empty($dateFrom)) {
        $sql .= " AND DATE(t.date) >= ?";
        $params[] = $dateFrom;
    }
    
    if (!empty($dateTo)) {
        $sql .= " AND DATE(t.date) <= ?";
        $params[] = $dateTo;
    }
    
    $sql .= " ORDER BY t.date DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    // Préparation et exécution de la requête
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Compte le nombre total de transactions avec les filtres appliqués
 *
 * @param string $accountFilter ID du compte bancaire
 * @param string $userFilter ID de l'utilisateur
 * @param string $typeFilter Type de transaction
 * @param float $minAmount Montant minimum
 * @param float $maxAmount Montant maximum 
 * @param string $dateFrom Date de début (format Y-m-d)
 * @param string $dateTo Date de fin (format Y-m-d)
 * @param string $env Environnement de base de données à utiliser
 * @return int Nombre total de transactions
 */
function countTransactions($accountFilter = '', $userFilter = '', $typeFilter = '', $minAmount = '', $maxAmount = '', $dateFrom = '', $dateTo = '', $env = 'default') {
    // Obtenir la connexion à la base de données
    $db = getDbConnection($env);
    
    // Construction de la requête avec les filtres
    $sql = "SELECT COUNT(*) AS total
            FROM transactions t
            INNER JOIN comptes_bancaires c ON t.account_id = c.id
            INNER JOIN utilisateurs u ON c.user_id = u.id
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($accountFilter)) {
        $sql .= " AND t.account_id = ?";
        $params[] = $accountFilter;
    }
    
    if (!empty($userFilter)) {
        $sql .= " AND c.user_id = ?";
        $params[] = $userFilter;
    }
    
    if (!empty($typeFilter)) {
        $sql .= " AND t.type = ?";
        $params[] = $typeFilter;
    }
    
    if (!empty($minAmount)) {
        $sql .= " AND t.amount >= ?";
        $params[] = $minAmount;
    }
    
    if (!empty($maxAmount)) {
        $sql .= " AND t.amount <= ?";
        $params[] = $maxAmount;
    }
    
    if (!empty($dateFrom)) {
        $sql .= " AND DATE(t.date) >= ?";
        $params[] = $dateFrom;
    }
    
    if (!empty($dateTo)) {
        $sql .= " AND DATE(t.date) <= ?";
        $params[] = $dateTo;
    }
    
    // Préparation et exécution de la requête
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return (int) $result['total'];
}

/**
 * Récupère la liste de tous les utilisateurs
 * 
 * @param string $env Environnement de base de données à utiliser
 * @return array Liste des utilisateurs
 */
function getAllUsers($env = 'default') {
    // Obtenir la connexion à la base de données
    $db = getDbConnection($env);
    
    // Construction de la requête
    $sql = "SELECT id, prenom, nom, email, role, statut 
            FROM utilisateurs 
            ORDER BY nom, prenom";
    
    // Préparation et exécution de la requête
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère la liste de tous les comptes bancaires
 * 
 * @param string $env Environnement de base de données à utiliser
 * @return array Liste des comptes bancaires
 */
function getAllAccounts($env = 'default') {
    // Obtenir la connexion à la base de données
    $db = getDbConnection($env);
    
    // Construction de la requête
    $sql = "SELECT c.id, c.user_id, c.numero, c.type, c.solde, c.statut, 
                   CONCAT(u.prenom, ' ', u.nom) AS nom_client
            FROM comptes_bancaires c
            INNER JOIN utilisateurs u ON c.user_id = u.id
            ORDER BY u.nom, u.prenom, c.type";
    
    // Préparation et exécution de la requête
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère les types de transactions disponibles
 * 
 * @param string $env Environnement de base de données à utiliser
 * @return array Liste des types de transactions
 */
function getTransactionTypes($env = 'default') {
    // Obtenir la connexion à la base de données
    $db = getDbConnection($env);
    
    // Approche 1: Récupérer les types à partir de la base de données (si stockés dans une table)
    try {
        $sql = "SELECT DISTINCT type FROM transactions ORDER BY type";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $types = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        // Si aucun type n'est trouvé, utiliser les types par défaut
        if (empty($types)) {
            return ['VIREMENT', 'DEPOT', 'RETRAIT', 'PAIEMENT', 'PRELEVEMENT', 'FRAIS', 'INTERET'];
        }
        
        return $types;
    } catch (Exception $e) {
        // En cas d'erreur (par exemple si la table n'existe pas), retourner les types par défaut
        return ['VIREMENT', 'DEPOT', 'RETRAIT', 'PAIEMENT', 'PRELEVEMENT', 'FRAIS', 'INTERET'];
    }
}