<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="admin-profile mb-4 text-center">
            <img src="../assets/img/admin-avatar.png" alt="Admin" class="admin-avatar rounded-circle mb-2" width="80">
            
        </div>

        <ul class="nav flex-column mb-4">
            <li class="nav-item">
                <a class="nav-link rounded-pill ps-3 my-1 <?= $currentPage == 'admin/index.php' ? 'active bg-primary text-white' : 'text-white' ?>" href="index.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Tableau de bord
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link rounded-pill ps-3 my-1 <?= $currentPage == 'admin/loan_validation.php' ? 'active bg-primary text-white' : 'text-white' ?>" href="loan_validation.php">
                    <i class="fas fa-check-square me-2"></i>
                    Validation des prêts
                    
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link rounded-pill ps-3 my-1 <?= $currentPage == 'admin/users.php' ? 'active bg-primary text-white' : 'text-white' ?>" href="users.php">
                    <i class="fas fa-users me-2"></i>
                    Gestion utilisateurs
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link rounded-pill ps-3 my-1 <?= $currentPage == 'admin/accounts.php' ? 'active bg-primary text-white' : 'text-white' ?>" href="accounts.php">
                    <i class="fas fa-wallet me-2"></i>
                    Gestion des comptes
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link rounded-pill ps-3 my-1 <?= $currentPage == 'admin/transactions.php' ? 'active bg-primary text-white' : 'text-white' ?>" href="transactions.php">
                    <i class="fas fa-exchange-alt me-2"></i>
                    Transactions
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link rounded-pill ps-3 my-1 <?= $currentPage == 'admin/notifications.php' ? 'active bg-primary text-white' : 'text-white' ?>" href="notifications.php">
                    <i class="fas fa-bell me-2"></i>
                    Notifications
                    <?php
                    // Vérification du nombre de notifications non lues
                    if (isset($totalUnreadNotifications) && $totalUnreadNotifications > 0) {
                        echo '<span class="badge bg-warning text-dark rounded-pill float-end">' . $totalUnreadNotifications . '</span>';
                    }
                    ?>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link rounded-pill ps-3 my-1 <?= $currentPage == 'admin/logs.php' ? 'active bg-primary text-white' : 'text-white' ?>" href="logs.php">
                    <i class="fas fa-history me-2"></i>
                    Journaux d'activité
                </a>
            </li>
        </ul>
        
        <div class="dropdown-divider border-top border-secondary opacity-75 my-3"></div>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mb-2 text-white">
            
        </h6>
        <br>
        <ul class="nav flex-column mb-2">
            
            
            <li class="nav-item">
                <a class="nav-link rounded-pill ps-3 my-1 bg-danger text-white" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Déconnexion
                </a>
            </li>
        </ul>
        <br><br><br><br><br>
        <div class="sidebar-footer mt-5 pt-3 text-center text-white-50">
            <small>FinBot Admin v1.0</small>
            <div class="mt-2">
                <span class="badge bg-success">En ligne</span>
            </div>
        </div>
    </div>
</nav>

<style>
/* Styles supplémentaires pour la sidebar */
#sidebarMenu {
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

#sidebarMenu .nav-link {
    transition: all 0.2s ease-in-out;
    position: relative;
    overflow: hidden;
}

#sidebarMenu .nav-link:hover:not(.active) {
    background-color: rgba(255, 255, 255, 0.1);
    transform: translateX(3px);
}

.admin-profile {
    transition: all 0.3s ease;
}

.admin-profile:hover .admin-avatar {
    transform: scale(1.05);
}

.admin-avatar {
    transition: transform 0.3s ease;
    border: 3px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
}

.sidebar-footer {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}
</style>