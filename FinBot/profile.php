<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\profile.php
session_start();
require_once __DIR__ . '/config/app.php';

// Vérifier si l'utilisateur est connecté
require_once __DIR__ . '/includes/auth_functions.php';
try {
    checkAuth();
} catch (Exception $e) {
    header('Location: index.php');
    exit;
}

// Récupérer l'ID et le rôle de l'utilisateur connecté
$userId = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];

// Messages de succès ou d'erreur
$success = '';
$error = '';

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/classes/User.php';
    $userManager = User::getInstance();
    
    // Déterminer l'action demandée
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'update_infos':
            // Mise à jour des informations personnelles
            try {
                // Récupérer les données du formulaire
                $userData = [
                    'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
                    'telephone' => isset($_POST['telephone']) ? trim($_POST['telephone']) : '',
                    'adresse' => isset($_POST['adresse']) ? trim($_POST['adresse']) : '',
                    'ville' => isset($_POST['ville']) ? trim($_POST['ville']) : '',
                    'code_postal' => isset($_POST['code_postal']) ? trim($_POST['code_postal']) : ''
                ];
                
                // Validation des données
                if (empty($userData['email'])) {
                    throw new Exception("L'adresse email est obligatoire.");
                }
                
                if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("L'adresse email n'est pas valide.");
                }
                
                // Validation du téléphone (format français)
                if (!empty($userData['telephone']) && !preg_match('/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/', $userData['telephone'])) {
                    throw new Exception("Le numéro de téléphone n'est pas valide.");
                }
                
                // Validation du code postal
                if (!empty($userData['code_postal']) && !preg_match('/^[0-9]{5}$/', $userData['code_postal'])) {
                    throw new Exception("Le code postal n'est pas valide.");
                }
                
                // Mettre à jour les informations
                if ($userManager->updateUser($userId, $userData)) {
                    // Mettre à jour les informations en session
                    $_SESSION['user']['email'] = $userData['email'];
                    $_SESSION['user']['telephone'] = $userData['telephone'];
                    $_SESSION['user']['adresse'] = $userData['adresse'];
                    $_SESSION['user']['ville'] = $userData['ville'];
                    $_SESSION['user']['code_postal'] = $userData['code_postal'];
                    
                    $success = "Vos informations personnelles ont été mises à jour avec succès.";
                } else {
                    throw new Exception("Une erreur est survenue lors de la mise à jour de vos informations.");
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
            
        case 'update_password':
            // Mise à jour du mot de passe
            try {
                // Récupérer les données du formulaire
                $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
                $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
                $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
                
                // Validation des données
                if (empty($current_password)) {
                    throw new Exception("Le mot de passe actuel est obligatoire.");
                }
                
                if (empty($new_password)) {
                    throw new Exception("Le nouveau mot de passe est obligatoire.");
                }
                
                if ($new_password !== $confirm_password) {
                    throw new Exception("Les nouveaux mots de passe ne correspondent pas.");
                }
                
                // Vérifier que le nouveau mot de passe est suffisamment fort
                if (strlen($new_password) < 8) {
                    throw new Exception("Le nouveau mot de passe doit contenir au moins 8 caractères.");
                }
                
                if (!preg_match('/[A-Z]/', $new_password)) {
                    throw new Exception("Le nouveau mot de passe doit contenir au moins une lettre majuscule.");
                }
                
                if (!preg_match('/[a-z]/', $new_password)) {
                    throw new Exception("Le nouveau mot de passe doit contenir au moins une lettre minuscule.");
                }
                
                if (!preg_match('/[0-9]/', $new_password)) {
                    throw new Exception("Le nouveau mot de passe doit contenir au moins un chiffre.");
                }
                
                // Changer le mot de passe
                if ($userManager->changePassword($userId, $current_password, $new_password)) {
                    $success = "Votre mot de passe a été changé avec succès.";
                } else {
                    throw new Exception("Le mot de passe actuel est incorrect ou une erreur est survenue.");
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
                   
            
        case 'upload_avatar':
            // Téléchargement d'une photo de profil
            try {
                // Vérifier si un fichier a été envoyé
                if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
                    throw new Exception("Aucun fichier n'a été sélectionné.");
                }
                
                // Télécharger la photo de profil
                if ($userManager->uploadProfilePicture($userId, $_FILES['avatar'])) {
                    // Mettre à jour l'avatar en session
                    $_SESSION['user']['photo_profil'] = $userManager->getProfilePicture($userId);
                    
                    $success = "Votre photo de profil a été mise à jour avec succès.";
                } else {
                    throw new Exception("Une erreur est survenue lors de la mise à jour de votre photo de profil.");
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
            
        case 'delete_avatar':
            // Suppression de la photo de profil
            try {
                // Récupérer le chemin actuel de l'avatar
                $avatarPath = $userManager->getProfilePicture($userId);
                
                if (!$avatarPath) {
                    throw new Exception("Aucune photo de profil à supprimer.");
                }
                
                // Supprimer l'ancien fichier s'il existe
                $fullPath = __DIR__ . '/' . $avatarPath;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
                
                // Mettre à jour la base de données
                if ($userManager->updateUser($userId, ['photo_profil' => null])) {
                    // Mettre à jour l'avatar en session
                    $_SESSION['user']['photo_profil'] = null;
                    
                    $success = "Votre photo de profil a été supprimée avec succès.";
                } else {
                    throw new Exception("Une erreur est survenue lors de la suppression de votre photo de profil.");
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
    }
}

// Récupérer les informations à jour de l'utilisateur
require_once __DIR__ . '/classes/User.php';
$userManager = User::getInstance();
$user = $userManager->getUserById($userId);


// Récupérer l'historique des connexions
// Comme la méthode n'existe pas encore, on laisse un tableau vide pour l'instant
$connexions = [];

// Configurer les variables pour le layout
$pageTitle = 'Mon profil';
$pageCss = 'profile';
$viewFile = '/views/profile.php';

// Inclure le layout principal
include __DIR__ . '/templates/layout.php';