<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\templates\layout.php

/**
 * Template principal de l'application FinBot+
 * 
 * Variables attendues :
 * $pageTitle - Titre de la page
 * $pageCss - Nom du fichier CSS spécifique à la page (sans l'extension)
 * $viewFile - Chemin vers le fichier de vue à inclure
 * $hideNavigation - (optionnel) Si true, la sidebar ne sera pas affichée
 * $headExtras - (optionnel) Code HTML supplémentaire pour la section head
 * $bodyClass - (optionnel) Classes supplémentaires pour la balise body
 * $footerScripts - (optionnel) Scripts JS supplémentaires en fin de page
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinBot+ | <?= htmlspecialchars($pageTitle ?? 'Banque en ligne') ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
   
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- CSS principal -->
    <link rel="stylesheet" href="assets/css/main.css">
    
    <!-- CSS spécifique à cette page -->
    <?php if (isset($pageCss) && file_exists(__DIR__ . '/../assets/css/pages/' . $pageCss . '.css')): ?>
    <link rel="stylesheet" href="assets/css/pages/<?= $pageCss ?>.css">
    <?php endif; ?>
    
    <?php if (isset($headExtras)) echo $headExtras; ?>
</head>
<body class="<?= $bodyClass ?? '' ?>">
    <?php if (!isset($hideNavigation) || !$hideNavigation): ?>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php 
        if (file_exists(__DIR__ . '/sidebar.php')) {
            include __DIR__ . '/sidebar.php';
        } else if (file_exists(__DIR__ . '/navigation.php')) {
            include __DIR__ . '/navigation.php';
        } else {
            echo '<div class="alert alert-warning">Navigation non disponible</div>';
        }
        ?>
        
        <!-- Contenu principal -->
        <div class="main-content">
            <?php 
            if (isset($viewFile) && file_exists(__DIR__ . '/../' . $viewFile)) {
                include __DIR__ . '/../' . $viewFile;
            } else {
                echo '<div class="alert alert-danger mt-4">
                    <h4>Erreur: Fichier de vue non trouvé</h4>
                    <p>Le fichier spécifié (' . ($viewFile ?? 'non défini') . ') n\'existe pas.</p>
                </div>';
            }
            ?>
        </div>
    </div>
    <?php else: ?>
        <!-- Page sans navigation (login, register, etc.) -->
        <?php 
        if (isset($viewFile) && file_exists(__DIR__ . '/../' . $viewFile)) {
            include __DIR__ . '/../' . $viewFile;
        } else {
            echo '<div class="alert alert-danger mt-4">
                <h4>Erreur: Fichier de vue non trouvé</h4>
                <p>Le fichier spécifié (' . ($viewFile ?? 'non défini') . ') n\'existe pas.</p>
            </div>';
        }
        ?>
    <?php endif; ?>
    
    <!-- Scripts JavaScript -->
    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <?php if (file_exists(__DIR__ . '/../assets/js/app.js')): ?>
    <script src="assets/js/app.js"></script>
    <?php endif; ?>

    <?php if (isset($footerScripts)) echo $footerScripts; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialisation des tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    </script>
    <script>
        window.addEventListener('error', function(e) {
            console.error('Erreur JavaScript:', e.message, 'à', e.filename, 'ligne', e.lineno);
        });
    </script>
</body>
</html>