<?php
class Database {
    private static $instance;
    private $connection;
    
    // Modifiez votre méthode __construct() pour gérer les erreurs de configuration
    private function __construct() {
        // Vérifier si le fichier de configuration existe
        $configFile = __DIR__ . '/../config/database.php';
        if (!file_exists($configFile)) {
            throw new Exception("Le fichier de configuration de la base de données est manquant.");
        }
        
        // Charger la configuration
        $config = require $configFile;
        
        // Vérifier si la configuration est valide
        if (!is_array($config)) {
            throw new Exception("La configuration de la base de données est invalide.");
        }
        
        // Définir les valeurs par défaut si elles sont manquantes
        $driver   = $config['driver'] ?? 'mysql';
        $host     = $config['host'] ?? 'localhost';
        $database = $config['database'] ?? 'finbot';
        $username = $config['username'] ?? 'root';
        $password = $config['password'] ?? '';
        $charset  = $config['charset'] ?? 'utf8mb4';
        
        // Tentative de connexion
        try {
            $dsn = "{$driver}:host={$host};dbname={$database};charset={$charset}";
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new Exception("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Méthodes de requête standards
    public function select($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function selectOne($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $stmt = $this->connection->prepare($query);
        $stmt->execute(array_values($data));
        
        return $this->connection->lastInsertId();
    }
    
    public function update($table, $data, $where, $params = []) {
        $set = [];
        foreach ($data as $column => $value) {
            $set[] = "{$column} = ?";
        }
        
        $query = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$where}";
        
        $stmt = $this->connection->prepare($query);
        $stmt->execute(array_merge(array_values($data), $params));
        
        return $stmt->rowCount();
    }
    
    public function delete($table, $where, $params = []) {
        $query = "DELETE FROM {$table} WHERE {$where}";
        
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
    
    // Méthodes de transaction
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollBack() {
        return $this->connection->rollBack();
    }
    
    // Méthode pour exécuter une requête directe
    public function query($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Exécute une requête SQL sans retourner de résultat
     * 
     * @param string $query Requête SQL
     * @param array $params Paramètres pour la requête préparée
     * @return bool Succès de l'exécution
     */
    public function execute($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('Erreur SQL : ' . $e->getMessage());
            throw new Exception('Erreur lors de l\'exécution de la requête: ' . $e->getMessage());
        }
    }
    
    /**
     * Retourne l'ID de la dernière ligne insérée
     *
     * @return string L'ID de la dernière ligne insérée
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}