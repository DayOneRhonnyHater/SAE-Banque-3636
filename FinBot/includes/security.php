<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\includes\security.php

/**
 * Fonctions de sécurité pour l'application FinBot
 */

// Pas besoin d'inclusions externes pour les fonctions de base de sécurité

/**
 * Nettoie les données d'entrée pour prévenir les attaques XSS
 * 
 * @param string|array $data Données à nettoyer
 * @return string|array Données nettoyées
 */
function cleanInput($data) {
    if (is_array($data)) {
        $cleaned = [];
        foreach ($data as $key => $value) {
            $cleaned[$key] = cleanInput($value);
        }
        return $cleaned;
    }
    
    // Convertir les caractères spéciaux en entités HTML
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Génère un jeton CSRF pour les formulaires
 * 
 * @return string Jeton CSRF
 */
function generateCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un jeton CSRF
 * 
 * @param string $token Jeton à vérifier
 * @return bool True si le jeton est valide
 */
function verifyCsrfToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Régénère le jeton CSRF
 */
function regenerateCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

/**
 * Affiche un champ caché avec un jeton CSRF pour les formulaires
 * 
 * @return string Champ HTML contenant le jeton CSRF
 */
function csrfField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Hache un mot de passe de manière sécurisée
 * 
 * @param string $password Mot de passe en clair
 * @return string Mot de passe haché
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
}

/**
 * Vérifie un mot de passe
 * 
 * @param string $password Mot de passe en clair
 * @param string $hash Hash stocké
 * @return bool True si le mot de passe correspond
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Génère un mot de passe aléatoire
 * 
 * @param int $length Longueur du mot de passe
 * @return string Mot de passe généré
 */
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    return $password;
}

/**
 * Vérifie la force d'un mot de passe
 * 
 * @param string $password Mot de passe à vérifier
 * @return array Score et message de force du mot de passe
 */
function checkPasswordStrength($password) {
    $score = 0;
    $message = '';
    
    // Longueur
    if (strlen($password) < 8) {
        $message = 'Mot de passe trop court (minimum 8 caractères)';
    } else {
        $score++;
    }
    
    // Mélange de caractères
    if (preg_match('/[A-Z]/', $password)) $score++;
    if (preg_match('/[a-z]/', $password)) $score++;
    if (preg_match('/[0-9]/', $password)) $score++;
    if (preg_match('/[^A-Za-z0-9]/', $password)) $score++;
    
    // Évaluation finale
    if ($score < 3) {
        $message = 'Mot de passe faible';
    } else if ($score < 5) {
        $message = 'Mot de passe moyen';
    } else {
        $message = 'Mot de passe fort';
    }
    
    return [
        'score' => $score,
        'message' => $message
    ];
}

/**
 * Valide une adresse email
 * 
 * @param string $email Adresse email à valider
 * @return bool True si l'email est valide
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Génère un token sécurisé pour la réinitialisation de mot de passe
 * 
 * @return string Token généré
 */
function generateResetToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Protège contre les tentatives d'injection SQL
 * 
 * @param string $input Chaîne à vérifier
 * @return bool True si l'entrée est sûre
 */
function isSqlSafe($input) {
    $blacklist = ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', ';', '--', '/*', '*/'];
    $input = strtoupper($input);
    
    foreach ($blacklist as $blackWord) {
        if (strpos($input, $blackWord) !== false) {
            return false;
        }
    }
    
    return true;
}

/**
 * Ajoute des en-têtes de sécurité
 */
function addSecurityHeaders() {
    // Protection contre le clickjacking
    header('X-Frame-Options: DENY');
    
    // Protection contre le MIME-sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Protection XSS pour les navigateurs modernes
    header('X-XSS-Protection: 1; mode=block');
    
    // Politique de sécurité du contenu
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net");
    
    // Politique de référence
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Transport sécurisé strict
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    
    // Cache contrôle pour les données sensibles
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

/**
 * Vérifie la validité d'un numéro de téléphone
 * 
 * @param string $phone Numéro de téléphone
 * @return bool True si le format est valide
 */
function validatePhone($phone) {
    // Format international ou français
    return preg_match('/^\+?[0-9]{10,15}$/', preg_replace('/[\s-]/', '', $phone));
}

/**
 * Échappe les caractères spéciaux dans une chaîne pour utilisation dans une requête SQL
 * 
 * @param string $string Chaîne à échapper
 * @param PDO $pdo Instance PDO à utiliser
 * @return string Chaîne échappée
 */
function escapeSql($string, $pdo) {
    return substr($pdo->quote($string), 1, -1);
}

/**
 * Protège contre les attaques par force brute
 * 
 * @param string $action Action à surveiller (login, reset_pwd, etc.)
 * @param string $identifier Identifiant de l'utilisateur (email, IP, etc.)
 * @param int $maxAttempts Nombre maximum de tentatives autorisées
 * @param int $timeout Temps d'attente en secondes après trop de tentatives
 * @return bool True si l'action est autorisée, False sinon
 */
function rateLimiter($action, $identifier, $maxAttempts = 5, $timeout = 300) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $key = "ratelimit_{$action}_{$identifier}";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'last_attempt' => 0
        ];
    }
    
    $now = time();
    $data = $_SESSION[$key];
    
    // Si le timeout est passé, réinitialiser le compteur
    if ($data['attempts'] >= $maxAttempts && ($now - $data['last_attempt']) > $timeout) {
        $data['attempts'] = 0;
    }
    
    // Vérifier si l'utilisateur a dépassé la limite
    if ($data['attempts'] >= $maxAttempts) {
        $waitTime = $timeout - ($now - $data['last_attempt']);
        throw new Exception("Trop de tentatives. Veuillez réessayer dans {$waitTime} secondes.");
    }
    // Validation des requêtes Botpress
    if (!verify_request_signature($_SERVER['HTTP_X_BOTPRESS_SIGNATURE'])) {
        http_response_code(403);
        die(json_encode(['error' => 'Signature invalide']));
    }

    function verify_request_signature($signature) {
        $secret = getenv('BOTPRESS_SECRET');
        $payload = file_get_contents('php://input');
        return hash_equals(hash_hmac('sha256', $payload, $secret), $signature);
}

    
    // Incrémenter le compteur
    $data['attempts']++;
    $data['last_attempt'] = $now;
    $_SESSION[$key] = $data;
    
    return true;
}

/**
 * Réinitialise le limiteur de tentatives
 * 
 * @param string $action Action à réinitialiser
 * @param string $identifier Identifiant de l'utilisateur
 */
function resetRateLimiter($action, $identifier) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $key = "ratelimit_{$action}_{$identifier}";
    if (isset($_SESSION[$key])) {
        unset($_SESSION[$key]);
    }
}