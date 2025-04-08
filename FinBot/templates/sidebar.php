<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\templates\sidebar.php

// Récupérer l'URL actuelle pour mettre en évidence l'élément de menu actif
$currentPage = basename($_SERVER['PHP_SELF']);

// Récupérer l'utilisateur connecté
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
$role = $user ? $user['role'] : 'VISITEUR';

/**
 * Vérifie si un élément du menu doit être marqué comme actif
 */
function isActive($page) {
    global $currentPage;
    return ($currentPage === $page) ? 'active' : '';
}
?>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <h3 class="sidebar-title">FinBot+</h3>
    </div>
    
    <div class="sidebar-user">

            <div class="user-avatar">
                                    
                    <div class="avatar-placeholder">
                        <?= strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)) ?>
                    </div>
               
            </div>
            <div class="user-info">
                <p class="user-name"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></p>
                <p class="user-role"><?= htmlspecialchars($role) ?></p>
            </div>

            
      
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <!-- Menu pour tous les utilisateurs -->
            <li class="nav-item <?= isActive('index.php') ?>">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Accueil</span>
                </a>
            </li>
            
                            
                
                
                <li class="nav-item <?= isActive('accounts.php') ?>">
                    <a href="accounts.php" class="nav-link">
                        <i class="fas fa-wallet"></i>
                        <span>Mes comptes</span>
                    </a>
                </li>
                
                <li class="nav-item <?= isActive('transactions.php') ?>">
                    <a href="transactions.php" class="nav-link">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Transactions</span>
                    </a>
                </li>
                
                <li class="nav-item <?= isActive('transfers.php') ?>">
                    <a href="transfers.php" class="nav-link">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Virements</span>
                    </a>
                </li>
                
                <li class="nav-item <?= isActive('chatbot.php') ?>">
                    <a class="nav-link" href="chatbot.php">
                        <i class="fas fa-robot"></i> 
                    </a>
                    <span>FinBot Chat</span>
                </li>

                        <?php 
                        // Afficher un badge si l'utilisateur a des messages non lus
                        if (function_exists('countUnreadMessages') && $unreadMessages = countUnreadMessages($user['id'])): 
                        ?>
                            <span class="badge badge-danger"><?= $unreadMessages ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
      
                    <li class="nav-item <?= isActive('loans.php') ?>">
                        <a href="loans.php" class="nav-link">
                            <i class="fas fa-hand-holding-usd"></i>
                            <span>Prêts</span>
                        </a>
                    </li>
                              
                                   <li class="nav-section">
                        <span>support</span>
                    </li>
                    
                    
                    <li class="nav-item <?= isActive('profile.php') ?>">
                        <a href="profile.php" class="nav-link">
                            <i class="fas fa-user-circle"></i>
                            <span>Mon profil</span>
                        </a>
                    </li>
                    <li class="nav-item <?= isActive('index.php') ?>">
                        <a href="index.php" class="nav-link">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Déconnexion</span>
                        </a>
                    </li>
                        
                        
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <div class="version">FinBot+ v1.0</div>
        <div class="copyright">&copy; <?= date('Y') ?> FinBot+</div>
    </div>
</div>
<!-- Fin Sidebar -->

<!-- Bouton d'ouverture/fermeture de la sidebar en mode mobile -->
<button class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
</button>

<script>
    // Script pour activer/désactiver la sidebar en mode mobile
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('sidebar-open');
    });
</script>