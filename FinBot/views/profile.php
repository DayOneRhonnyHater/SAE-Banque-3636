<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\views\profile.php

// Récupérer les données de l'utilisateur
$user = isset($user) ? $user : $_SESSION['user'];

$connexions = isset($connexions) ? $connexions : [];

// Formatage des dates
require_once __DIR__ . '/../includes/helpers.php';
?>

<div class="container-fluid py-4">
    <!-- En-tête de la page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Mon profil</h1>
        <a href="dashboard.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Retour au tableau de bord
        </a>
    </div>
    
    <!-- Affichage des messages de succès ou d'erreur -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Colonne de gauche - Informations et photo -->
        <div class="col-xl-4 col-lg-5">
            <!-- Carte avatar -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Photo de profil</h6>
                </div>
                <div class="card-body text-center">
                    <div class="profile-avatar-container mb-3">
                        <?php if (isset($user['photo_profil']) && !empty($user['photo_profil'])): ?>
                            <img src="<?= htmlspecialchars($user['photo_profil']) ?>" alt="Photo de profil" class="profile-avatar">
                        <?php else: ?>
                            <div class="profile-avatar-placeholder">
                                <?= strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h5 class="mb-1"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h5>
                    <p class="text-muted"><?= htmlspecialchars($user['role'] ?? 'Utilisateur') ?></p>
                    
                    <form action="" method="post" enctype="multipart/form-data" class="mt-4">
                        <input type="hidden" name="action" value="upload_avatar">
                        
                        <div class="mb-3">
                            <label for="avatar" class="form-label">Changer votre photo</label>
                            <input class="form-control" type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif">
                            <div class="form-text">Formats acceptés: JPG, PNG, GIF. Max 5 Mo.</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>Télécharger
                            </button>
                            
                            <?php if (isset($user['photo_profil']) && !empty($user['photo_profil'])): ?>
                                <button type="submit" class="btn btn-outline-danger" 
                                        formaction="?action=delete_avatar" 
                                        name="action" value="delete_avatar" 
                                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer votre photo de profil ?')">
                                    <i class="fas fa-trash-alt me-2"></i>Supprimer la photo
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Carte résumé -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informations de compte</h6>
                </div>
                <div class="card-body">
                    <div class="info-group">
                        <label>ID client</label>
                        <p><?= htmlspecialchars($user['id']) ?></p>
                    </div>
                    
                    <div class="info-group">
                        <label>Statut</label>
                        <p>
                            <?php if (isset($user['statut']) && $user['statut'] === 'ACTIF'): ?>
                                <span class="badge bg-success">Actif</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><?= htmlspecialchars($user['statut'] ?? 'Inconnu') ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div class="info-group">
                        <label>Date d'inscription</label>
                        <p><?= isset($user['date_creation']) ? formatDate($user['date_creation']) : 'Non disponible' ?></p>
                    </div>
                    
                    <div class="info-group">
                        <label>Dernière connexion</label>
                        <p><?= isset($user['derniere_connexion']) ? formatDate($user['derniere_connexion'], true) : 'Non disponible' ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Historique des connexions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Dernières connexions</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($connexions)): ?>
                        <p class="text-center text-muted">Aucun historique de connexion disponible</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>IP</th>
                                        <th>Appareil</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($connexions as $connexion): ?>
                                        <tr>
                                            <td><?= formatDate($connexion['date_connexion'], true) ?></td>
                                            <td><?= htmlspecialchars($connexion['ip_adresse']) ?></td>
                                            <td><?= htmlspecialchars(substr($connexion['user_agent'], 0, 30)) ?>...</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Colonne de droite - Formulaires -->
        <div class="col-xl-8 col-lg-7">
            <!-- Onglets de navigation -->
            <div class="card shadow mb-4">
                <div class="card-header p-0">
                    <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab" aria-controls="personal" aria-selected="true">
                                <i class="fas fa-user me-2"></i>Informations personnelles
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
                                <i class="fas fa-lock me-2"></i>Sécurité
                            </button>
                        </li>
                        
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="profileTabsContent">
                        <!-- Onglet Informations personnelles -->
                        <div class="tab-pane fade show active" id="personal" role="tabpanel" aria-labelledby="personal-tab">
                            <h5 class="card-title mb-4">Mettre à jour vos informations personnelles</h5>
                            
                            <form action="" method="post">
                                <input type="hidden" name="action" value="update_infos">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="prenom" class="form-label">Prénom</label>
                                        <input type="text" class="form-control" id="prenom" value="<?= htmlspecialchars($user['prenom']) ?>" readonly>
                                        <div class="form-text">Le prénom ne peut pas être modifié.</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="nom" class="form-label">Nom</label>
                                        <input type="text" class="form-control" id="nom" value="<?= htmlspecialchars($user['nom']) ?>" readonly>
                                        <div class="form-text">Le nom ne peut pas être modifié.</div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Adresse email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="telephone" class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="adresse" class="form-label">Adresse</label>
                                    <input type="text" class="form-control" id="adresse" name="adresse" value="<?= htmlspecialchars($user['adresse'] ?? '') ?>">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="code_postal" class="form-label">Code postal</label>
                                        <input type="text" class="form-control" id="code_postal" name="code_postal" value="<?= htmlspecialchars($user['code_postal'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="col-md-8 mb-3">
                                        <label for="ville" class="form-label">Ville</label>
                                        <input type="text" class="form-control" id="ville" name="ville" value="<?= htmlspecialchars($user['ville'] ?? '') ?>">
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Enregistrer les modifications
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Onglet Sécurité -->
                        <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                            <h5 class="card-title mb-4">Changer votre mot de passe</h5>
                            
                            <form action="" method="post">
                                <input type="hidden" name="action" value="update_password">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Mot de passe actuel</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <div class="form-text">
                                        Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-key me-2"></i>Changer le mot de passe
                                    </button>
                                </div>
                            </form>
                            
                            <hr class="my-4">
                            
                            <h5 class="card-title mb-4">Connexions actives</h5>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Vous pouvez voir ici les appareils sur lesquels vous êtes actuellement connecté.
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Appareil</th>
                                            <th>Emplacement</th>
                                            <th>Dernière activité</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <i class="fas fa-desktop me-2"></i>
                                                Cet appareil
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'Inconnue') ?>
                                            </td>
                                            <td>
                                                Maintenant
                                            </td>
                                            <td>
                                                <span class="badge bg-success">Session active</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-danger" 
                                        onclick="if(confirm('Êtes-vous sûr de vouloir déconnecter toutes les autres sessions ?')) { window.location.href='logout_all.php'; }">
                                    <i class="fas fa-sign-out-alt me-2"></i>Déconnecter tous les appareils
                                </button>
                            </div>
                        </div>
                                   
            <!-- Carte de sécurité supplémentaire -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Sécurité du compte</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="security-item">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-shield-alt text-success fs-4 me-3"></i>
                                    <div>
                                        <h6 class="mb-0">Niveau de sécurité</h6>
                                        <p class="text-muted mb-0">Fort</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="security-item">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-mobile-alt text-warning fs-4 me-3"></i>
                                    <div>
                                        <h6 class="mb-0">Authentification à deux facteurs</h6>
                                        <p class="text-muted mb-0">Non activée</p>
                                    </div>
                                </div>
                                
                                <a href="#" class="btn btn-sm btn-outline-primary">Activer</a>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="security-item">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-envelope text-primary fs-4 me-3"></i>
                                    <div>
                                        <h6 class="mb-0">Email vérifié</h6>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($user['email']) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="security-item">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-key text-danger fs-4 me-3"></i>
                                    <div>
                                        <h6 class="mb-0">Dernière modification du mot de passe</h6>
                                        <p class="text-muted mb-0"><?= formatDate(date('Y-m-d H:i:s')) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/profile.js"></script>