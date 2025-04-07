<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\templates\header.php
// Vérifier si l'utilisateur est connecté (cette variable est utilisée dans le reste du template)
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
$role = $user ? $user['role'] : null;

// Définir le titre de la page s'il n'est pas déjà défini
if (!isset($pageTitle)) {
    $pageTitle = 'FinBot - La banque en ligne';
}

// Détecter la page courante pour activer le bon élément de menu
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle : 'FinBot - Application Bancaire' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Style personnalisé -->
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <!-- Style admin (uniquement pour les pages admin) -->
    <?php if (strpos($currentPage ?? '', 'admin/') === 0): ?>
    <link href="../assets/css/admin.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body class="d-flex flex-column h-100">
    
            <!-- Le contenu principal viendra ici, après la sidebar -->