<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\views\loans.php

// Récupération des données nécessaires
require_once __DIR__ . '/../classes/Pret.php';
require_once __DIR__ . '/../classes/Compte.php';
require_once __DIR__ . '/../includes/helpers.php';

$pretManager = Pret::getInstance();
$compteManager = Compte::getInstance();

// Récupérer l'ID de l'utilisateur connecté et son rôle
$userId = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];

// Récupérer les informations selon le rôle
if ($role === 'CLIENT') {
    // Pour un client, récupérer ses prêts
    $prets = $pretManager->getPretsByUser($userId);
    $comptes = $compteManager->getComptesByUser($userId);
    $typesPrets = $pretManager->getTypePrets();
} elseif (in_array($role, ['CONSEILLER', 'ADMINISTRATEUR'])) {
    // Pour un conseiller ou admin, récupérer les demandes de prêts à traiter
    if ($role === 'CONSEILLER') {
        $prets = $pretManager->getPretsByAdvisor($userId);
    } else {
        $prets = $pretManager->getAllPrets();
    }
    $typesPrets = $pretManager->getTypePrets();
}

// Détails d'un prêt spécifique si demandé
$pretId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$pretDetails = null;

if ($pretId > 0) {
    if ($role === 'CLIENT') {
        $pretDetails = $pretManager->getPretDetails($pretId, $userId);
    } else {
        $pretDetails = $pretManager->getPretDetailsForAdvisor($pretId);
    }
}

// Regrouper les prêts par statut pour l'affichage
$pretsParStatut = [
    'EN_ATTENTE' => [],
    'APPROUVE' => [],
    'REFUSE' => [],
    'ANNULE' => [],
    'ACCEPTE' => [],
    'REJETE' => [],
    'ACTIF' => [],
    'TERMINE' => []
];

foreach ($prets as $pret) {
    $pretsParStatut[$pret['statut']][] = $pret;
}

// Déterminer l'onglet actif
$activeTab = 'all';
if (!empty($_GET['tab']) && in_array($_GET['tab'], ['pending', 'active', 'completed', 'rejected'])) {
    $activeTab = $_GET['tab'];
}

// Récupérer les échéances si un prêt actif est sélectionné
$echeances = [];
if ($pretDetails && $pretDetails['statut'] === 'ACTIF') {
    $echeances = $pretManager->getEcheancesByPret($pretId);
}
?>

<div class="container-fluid py-4">
    <!-- En-tête de la page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Gestion des prêts</h1>
            <p class="text-muted">
                <?php if ($role === 'CLIENT'): ?>
                    Suivez vos prêts en cours et effectuez de nouvelles demandes
                <?php else: ?>
                    Gérez les demandes de prêts de vos clients
                <?php endif; ?>
            </p>
        </div>
        <?php if ($role === 'CLIENT'): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newLoanModal">
                <i class="fas fa-plus-circle me-2"></i>Nouvelle demande
            </button>
        <?php endif; ?>
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
    
    <?php if ($pretDetails): ?>
        <!-- Détails d'un prêt spécifique -->
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="m-0 font-weight-bold text-primary">
                                Détails du prêt #<?= $pretDetails['id'] ?>
                                <span class="badge <?= getStatusBadgeClass($pretDetails['statut']) ?> ms-2"><?= getStatusLabel($pretDetails['statut']) ?></span>
                            </h6>
                        </div>
                        <a href="loans.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Informations générales du prêt -->
                            <div class="col-md-6">
                                <h5 class="mb-3">Informations générales</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <th scope="row" width="40%">Type de prêt</th>
                                        <td><?= htmlspecialchars($pretDetails['type_nom']) ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Montant</th>
                                        <td><?= formatMontant($pretDetails['montant']) ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Durée</th>
                                        <td><?= $pretDetails['duree_mois'] ?> mois (<?= round($pretDetails['duree_mois'] / 12, 1) ?> ans)</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Taux d'intérêt</th>
                                        <td><?= $pretDetails['taux_interet'] ? number_format($pretDetails['taux_interet'], 2) . ' %' : 'Non défini' ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Mensualité</th>
                                        <td><?= $pretDetails['mensualite'] ? formatMontant($pretDetails['mensualite']) : 'Non définie' ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Date de demande</th>
                                        <td><?= formatDate($pretDetails['date_demande']) ?></td>
                                    </tr>
                                    <?php if ($pretDetails['date_decision']): ?>
                                        <tr>
                                            <th scope="row">Date de décision</th>
                                            <td><?= formatDate($pretDetails['date_decision']) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if ($pretDetails['date_debut']): ?>
                                        <tr>
                                            <th scope="row">Date de début</th>
                                            <td><?= formatDate($pretDetails['date_debut']) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if ($pretDetails['date_fin']): ?>
                                        <tr>
                                            <th scope="row">Date de fin</th>
                                            <td><?= formatDate($pretDetails['date_fin']) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <th scope="row">Compte de versement</th>
                                        <td><?= htmlspecialchars($pretDetails['numero_compte']) ?></td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Détails financiers et avancement -->
                            <div class="col-md-6">
                                <?php if (in_array($pretDetails['statut'], ['ACTIF', 'TERMINE'])): ?>
                                    <h5 class="mb-3">Avancement du prêt</h5>
                                    
                                    <!-- Barre de progression -->
                                    <?php
                                    $paiementsEffectues = 0;
                                    $totalPaiements = $pretDetails['duree_mois'];
                                    
                                    foreach ($echeances as $echeance) {
                                        if ($echeance['statut_paiement'] === 'PAYE') {
                                            $paiementsEffectues++;
                                        }
                                    }
                                    
                                    $progression = ($totalPaiements > 0) ? ($paiementsEffectues / $totalPaiements) * 100 : 0;
                                    ?>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Progression</span>
                                            <span><?= $paiementsEffectues ?> / <?= $totalPaiements ?> mensualités payées</span>
                                        </div>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-primary" role="progressbar" 
                                                 style="width: <?= $progression ?>%;" 
                                                 aria-valuenow="<?= $progression ?>" aria-valuemin="0" aria-valuemax="100">
                                                <?= round($progression) ?>%
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Résumé financier -->
                                    <div class="card bg-light mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title">Résumé financier</h6>
                                            <div class="row">
                                                <div class="col-6">
                                                    <p class="mb-1"><strong>Montant emprunté:</strong></p>
                                                    <p class="text-primary h5"><?= formatMontant($pretDetails['montant']) ?></p>
                                                </div>
                                                <div class="col-6">
                                                    <p class="mb-1"><strong>Coût total du crédit:</strong></p>
                                                    <p class="text-danger h5"><?= formatMontant(($pretDetails['mensualite'] * $pretDetails['duree_mois']) - $pretDetails['montant']) ?></p>
                                                </div>
                                            </div>
                                            <div class="row mt-2">
                                                <div class="col-6">
                                                    <p class="mb-1"><strong>Capital remboursé:</strong></p>
                                                    <p class="h6"><?= formatMontant($pretDetails['capital_rembourse']) ?></p>
                                                </div>
                                                <div class="col-6">
                                                    <p class="mb-1"><strong>Capital restant dû:</strong></p>
                                                    <p class="h6"><?= formatMontant($pretDetails['montant'] - $pretDetails['capital_rembourse']) ?></p>
                                                </div>
                                            </div>
                                            <?php if ($pretDetails['prochaine_echeance']): ?>
                                                <div class="mt-3 pt-2 border-top">
                                                    <p class="mb-1"><strong>Prochaine échéance:</strong></p>
                                                    <p class="h6"><?= formatDate($pretDetails['prochaine_echeance']) ?> - <?= formatMontant($pretDetails['mensualite']) ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php elseif ($pretDetails['statut'] === 'APPROUVE'): ?>
                                    <!-- Détails de l'offre -->
                                    <h5 class="mb-3">Détails de l'offre</h5>
                                    <div class="card bg-light mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title">Proposition de financement</h6>
                                            <p><strong>Taux d'intérêt:</strong> <?= number_format($pretDetails['taux_interet'], 2) ?> %</p>
                                            <p><strong>Mensualité:</strong> <?= formatMontant($pretDetails['mensualite']) ?></p>
                                            <p><strong>Coût total du crédit:</strong> <?= formatMontant(($pretDetails['mensualite'] * $pretDetails['duree_mois']) - $pretDetails['montant']) ?></p>
                                            <p><strong>TAEG:</strong> <?= number_format($pretDetails['taeg'], 2) ?> %</p>
                                            
                                            <?php if ($role === 'CLIENT'): ?>
                                                <div class="alert alert-info mt-3">
                                                    <p class="mb-0"><i class="fas fa-info-circle me-2"></i> Cette offre est valable pendant 15 jours. Vous pouvez l'accepter ou la refuser.</p>
                                                </div>
                                                
                                                <div class="d-flex justify-content-between mt-3">
                                                    <form action="" method="post" class="me-2">
                                                        <input type="hidden" name="action" value="reject_offer">
                                                        <input type="hidden" name="pret_id" value="<?= $pretDetails['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir refuser cette offre de prêt ?');">
                                                            <i class="fas fa-times me-1"></i> Refuser l'offre
                                                        </button>
                                                    </form>
                                                    
                                                    <form action="" method="post">
                                                        <input type="hidden" name="action" value="accept_offer">
                                                        <input type="hidden" name="pret_id" value="<?= $pretDetails['id'] ?>">
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="fas fa-check me-1"></i> Accepter l'offre
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php elseif ($pretDetails['statut'] === 'REFUSE'): ?>
                                    <!-- Informations de refus -->
                                    <h5 class="mb-3">Motif du refus</h5>
                                    <div class="card bg-light mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title text-danger">Demande refusée</h6>
                                            <p><?= nl2br(htmlspecialchars($pretDetails['commentaire'])) ?></p>
                                            
                                            <?php if ($role === 'CLIENT'): ?>
                                                <div class="alert alert-info mt-3">
                                                    <p class="mb-0"><i class="fas fa-info-circle me-2"></i> Pour plus d'informations, n'hésitez pas à contacter votre conseiller.</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php elseif ($pretDetails['statut'] === 'EN_ATTENTE' && in_array($role, ['CONSEILLER', 'ADMINISTRATEUR'])): ?>
                                    <!-- Formulaire de décision pour les conseillers -->
                                    <h5 class="mb-3">Traiter cette demande</h5>
                                    <div class="card bg-light mb-3">
                                        <div class="card-body">
                                            <ul class="nav nav-tabs" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link active" id="approve-tab" data-bs-toggle="tab" data-bs-target="#approve" type="button" role="tab" aria-controls="approve" aria-selected="true">Approuver</button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link" id="reject-tab" data-bs-toggle="tab" data-bs-target="#reject" type="button" role="tab" aria-controls="reject" aria-selected="false">Refuser</button>
                                                </li>
                                            </ul>
                                            
                                            <div class="tab-content mt-3">
                                                <!-- Onglet Approuver -->
                                                <div class="tab-pane fade show active" id="approve" role="tabpanel" aria-labelledby="approve-tab">
                                                    <form action="" method="post">
                                                        <input type="hidden" name="action" value="approve_loan">
                                                        <input type="hidden" name="pret_id" value="<?= $pretDetails['id'] ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label for="taux_interet" class="form-label">Taux d'intérêt (%)</label>
                                                            <input type="number" class="form-control" id="taux_interet" name="taux_interet" step="0.01" min="0.1" max="20" required>
                                                            <div class="form-text">Entrez le taux d'intérêt annuel (ex: 3.5 pour 3.5%).</div>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="commentaire" class="form-label">Commentaire (optionnel)</label>
                                                            <textarea class="form-control" id="commentaire" name="commentaire" rows="3"></textarea>
                                                        </div>
                                                        
                                                        <button type="submit" class="btn btn-success">
                                                            <i class="fas fa-check me-1"></i> Approuver le prêt
                                                        </button>
                                                    </form>
                                                </div>
                                                
                                                <!-- Onglet Refuser -->
                                                <div class="tab-pane fade" id="reject" role="tabpanel" aria-labelledby="reject-tab">
                                                    <form action="" method="post">
                                                        <input type="hidden" name="action" value="reject_loan">
                                                        <input type="hidden" name="pret_id" value="<?= $pretDetails['id'] ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label for="motif_refus" class="form-label">Motif du refus</label>
                                                            <textarea class="form-control" id="motif_refus" name="motif_refus" rows="4" required></textarea>
                                                            <div class="form-text">Veuillez expliquer les raisons du refus de manière claire et concise.</div>
                                                        </div>
                                                        
                                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir refuser cette demande de prêt ?');">
                                                            <i class="fas fa-times me-1"></i> Refuser le prêt
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php elseif ($pretDetails['statut'] === 'EN_ATTENTE' && $role === 'CLIENT'): ?>
                                    <!-- Informations sur la demande en attente -->
                                    <h5 class="mb-3">Statut de la demande</h5>
                                    <div class="card bg-light mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title">Demande en cours d'étude</h6>
                                            <p>Votre demande de prêt est en cours d'examen par nos équipes. Un conseiller vous contactera prochainement.</p>
                                            <p><strong>Délai de traitement estimé:</strong> 2 à 5 jours ouvrés</p>
                                            
                                            <div class="alert alert-info mt-3">
                                                <p class="mb-0"><i class="fas fa-info-circle me-2"></i> Vous pouvez annuler votre demande tant qu'elle n'a pas été traitée.</p>
                                            </div>
                                            
                                            <form action="" method="post" class="mt-3">
                                                <input type="hidden" name="action" value="cancel_loan">
                                                <input type="hidden" name="pret_id" value="<?= $pretDetails['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette demande de prêt ?');">
                                                    <i class="fas fa-times me-1"></i> Annuler ma demande
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($echeances)): ?>
                            <!-- Tableau des échéances -->
                            <div class="mt-4">
                                <h5 class="mb-3">Tableau d'amortissement</h5>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Date d'échéance</th>
                                                <th>Mensualité</th>
                                                <th>Capital</th>
                                                <th>Intérêts</th>
                                                <th>Capital restant dû</th>
                                                <th>Statut</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($echeances as $index => $echeance): ?>
                                                <tr>
                                                    <td><?= $index + 1 ?></td>
                                                    <td><?= formatDate($echeance['date_echeance']) ?></td>
                                                    <td><?= formatMontant($echeance['montant_mensualite']) ?></td>
                                                    <td><?= formatMontant($echeance['montant_capital']) ?></td>
                                                    <td><?= formatMontant($echeance['montant_interets']) ?></td>
                                                    <td><?= formatMontant($echeance['capital_restant']) ?></td>
                                                    <td>
                                                        <?php if ($echeance['statut_paiement'] === 'PAYE'): ?>
                                                            <span class="badge bg-success">Payée</span>
                                                        <?php elseif ($echeance['statut_paiement'] === 'EN_ATTENTE'): ?>
                                                            <span class="badge bg-warning">À venir</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">En retard</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($pretDetails['description']): ?>
                            <!-- Description du projet -->
                            <div class="mt-4">
                                <h5 class="mb-3">Description du projet</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <?= nl2br(htmlspecialchars($pretDetails['description'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Liste des prêts -->
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <ul class="nav nav-tabs card-header-tabs">
                            <li class="nav-item">
                                <a class="nav-link <?= $activeTab === 'all' ? 'active' : '' ?>" href="?tab=all">Tous</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $activeTab === 'pending' ? 'active' : '' ?>" href="?tab=pending">
                                    En attente
                                    <?php 
                                    $countPending = count($pretsParStatut['EN_ATTENTE']) + count($pretsParStatut['APPROUVE']);
                                    if ($countPending > 0): 
                                    ?>
                                        <span class="badge bg-primary rounded-pill"><?= $countPending ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $activeTab === 'active' ? 'active' : '' ?>" href="?tab=active">
                                    Actifs
                                    <?php 
                                    $countActive = count($pretsParStatut['ACTIF']);
                                    if ($countActive > 0): 
                                    ?>
                                        <span class="badge bg-success rounded-pill"><?= $countActive ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $activeTab === 'completed' ? 'active' : '' ?>" href="?tab=completed">Terminés</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $activeTab === 'rejected' ? 'active' : '' ?>" href="?tab=rejected">Refusés/Annulés</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <?php if (empty($prets)): ?>
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="fas fa-hand-holding-usd fa-3x text-gray-300"></i>
                                </div>
                                <p>Aucun prêt à afficher</p>
                                <?php if ($role === 'CLIENT'): ?>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newLoanModal">
                                        <i class="fas fa-plus-circle me-2"></i>Faire une demande de prêt
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <!-- Filtrage des prêts selon l'onglet sélectionné -->
                            <?php
                            $filteredPrets = [];
                            
                            switch ($activeTab) {
                                case 'pending':
                                    $filteredPrets = array_merge($pretsParStatut['EN_ATTENTE'], $pretsParStatut['APPROUVE']);
                                    break;
                                case 'active':
                                    $filteredPrets = $pretsParStatut['ACTIF'];
                                    break;
                                case 'completed':
                                    $filteredPrets = $pretsParStatut['TERMINE'];
                                    break;
                                case 'rejected':
                                    $filteredPrets = array_merge($pretsParStatut['REFUSE'], $pretsParStatut['ANNULE'], $pretsParStatut['REJETE']);
                                    break;
                                default:
                                    $filteredPrets = $prets;
                                    break;
                            }
                            ?>
                            
                            <?php if (empty($filteredPrets)): ?>
                                <div class="text-center py-4">
                                    <p>Aucun prêt dans cette catégorie</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Date</th>
                                                <th>Type</th>
                                                <th>Montant</th>
                                                <th>Durée</th>
                                                <th>Statut</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($filteredPrets as $pret): ?>
                                                <tr>
                                                    <td><?= $pret['id'] ?></td>
                                                    <td><?= formatDate($pret['date_demande']) ?></td>
                                                    <td><?= htmlspecialchars($pret['type_nom']) ?></td>
                                                    <td><?= formatMontant($pret['montant']) ?></td>
                                                    <td><?= $pret['duree_mois'] ?> mois</td>
                                                    <td>
                                                        <span class="badge <?= getStatusBadgeClass($pret['statut']) ?>">
                                                            <?= getStatusLabel($pret['statut']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="?id=<?= $pret['id'] ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($pret['statut'] === 'EN_ATTENTE' && $role === 'CLIENT'): ?>
                                                            <form action="" method="post" class="d-inline">
                                                                <input type="hidden" name="action" value="cancel_loan">
                                                                <input type="hidden" name="pret_id" value="<?= $pret['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette demande ?');">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($role === 'CLIENT'): ?>
                <!-- Simulateur de prêt -->
                <div class="col-md-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Simulateur de prêt</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="montant" class="form-label">Montant du prêt (€)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="montant" min="1000" max="500000" step="1000" value="10000">
                                            <span class="input-group-text">€</span>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="duree" class="form-label">Durée (mois)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="duree" min="12" max="360" step="12" value="60">
                                            <span class="input-group-text">mois</span>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="taux_simulation" class="form-label">Taux d'intérêt (%)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="taux_simulation" min="0.1" max="20" step="0.1" value="5">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    
                                    <button type="button" class="btn btn-primary mt-2" onclick="calculateMonthlyPayment()">
                                        <i class="fas fa-calculator me-1"></i> Calculer
                                    </button>
                                </div>
                                
                                <div class="col-md-6">
                                    <div id="simulation_result" class="card bg-light" style="display: none;">
                                        <div class="card-body">
                                            <h5 class="card-title">Résultat de la simulation</h5>
                                            <div class="row mt-3">
                                                <div class="col-6">
                                                    <p class="mb-1">Mensualité:</p>
                                                    <p id="mensualite" class="h4 text-primary">-</p>
                                                </div>
                                                <div class="col-6">
                                                    <p class="mb-1">Coût total du crédit:</p>
                                                    <p id="cout_total" class="h4 text-danger">-</p>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-outline-primary mt-3" data-bs-toggle="modal" data-bs-target="#newLoanModal">
                                                <i class="fas fa-paper-plane me-1"></i> Faire une demande
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($role === 'CLIENT'): ?>
<!-- Modal pour nouvelle demande de prêt -->
<div class="modal fade" id="newLoanModal" tabindex="-1" aria-labelledby="newLoanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newLoanModalLabel">Nouvelle demande de prêt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="post">
                <input type="hidden" name="action" value="new_loan">
                
                <div class="modal-body">
                    <!-- Type de prêt -->
                    <div class="mb-3">
                        <label for="type_pret" class="form-label">Type de prêt</label>
                        <select class="form-select" id="type_pret" name="type_pret" required>
                            <option value="">Sélectionner le type de prêt</option>
                            <?php foreach ($typesPrets as $typePret): ?>
                                <option value="<?= $typePret['id'] ?>" data-description="<?= htmlspecialchars($typePret['description']) ?>">
                                    <?= htmlspecialchars($typePret['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="type_description" class="form-text mt-2"></div>
                    </div>
                    
                    <!-- Montant -->
                    <div class="mb-3">
                        <label for="montant_demande" class="form-label">Montant souhaité (€)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="montant_demande" name="montant" min="1000" step="100" required>
                            <span class="input-group-text">€</span>
                        </div>
                    </div>
                    
                    <!-- Durée -->
                    <div class="mb-3">
                        <label for="duree_demande" class="form-label">Durée souhaitée (mois)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="duree_demande" name="duree" min="12" max="360" step="12" required>
                            <span class="input-group-text">mois</span>
                        </div>
                        <div class="form-text">Entre 1 et 30 ans (12 à 360 mois).</div>
                    </div>
                    
                    <!-- Compte pour le versement -->
                    <div class="mb-3">
                        <label for="compte_id" class="form-label">Compte pour le versement</label>
                        <select class="form-select" id="compte_id" name="compte_id" required>
                            <option value="">Sélectionner un compte</option>
                            <?php foreach ($comptes as $compte): ?>
                                <option value="<?= $compte['id'] ?>">
                                    <?= htmlspecialchars($compte['type_nom'] . ' - ' . $compte['numero_compte']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Description du projet -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description du projet</label>
                        <textarea class="form-control" id="description" name="description" rows="4" placeholder="Décrivez votre projet et l'utilisation prévue de ce prêt..."></textarea>
                        <div class="form-text">Cette description nous aidera à mieux comprendre votre projet et à évaluer votre demande.</div>
                    </div>
                    
                    <!-- Informations légales -->
                    <div class="mb-3">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i> Informations importantes</h6>
                            <p class="small mb-0">
                                Un crédit vous engage et doit être remboursé. Vérifiez vos capacités de remboursement avant de vous engager.
                                Le taux définitif sera déterminé après étude de votre dossier.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Consentement -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="consent" name="consent" required>
                        <label class="form-check-label" for="consent">
                            J'ai lu et j'accepte les conditions générales et la politique de confidentialité.
                        </label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Soumettre ma demande</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Afficher la description du type de prêt sélectionné
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('type_pret');
        const typeDescription = document.getElementById('type_description');
        
        typeSelect.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            
            if (option && option.value !== '') {
                const description = option.getAttribute('data-description');
                typeDescription.innerHTML = `<div class="alert alert-light">${description}</div>`;
            } else {
                typeDescription.innerHTML = '';
            }
        });
    });
</script>
<?php endif; ?>

<?php
/**
 * Retourne la classe CSS pour un badge de statut
 */
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'EN_ATTENTE':
            return 'bg-warning';
        case 'APPROUVE':
            return 'bg-info';
        case 'REFUSE':
        case 'ANNULE':
        case 'REJETE':
            return 'bg-danger';
        case 'ACTIF':
            return 'bg-success';
        case 'TERMINE':
            return 'bg-secondary';
        case 'ACCEPTE':
            return 'bg-primary';
        default:
            return 'bg-secondary';
    }
}

/**
 * Retourne le libellé d'un statut
 */
function getStatusLabel($status) {
    switch ($status) {
        case 'EN_ATTENTE':
            return 'En attente';
        case 'APPROUVE':
            return 'Approuvé';
        case 'REFUSE':
            return 'Refusé';
        case 'ANNULE':
            return 'Annulé';
        case 'ACTIF':
            return 'Actif';
        case 'TERMINE':
            return 'Terminé';
        case 'ACCEPTE':
            return 'Accepté';
        case 'REJETE':
            return 'Rejeté';
        default:
            return $status;
    }
}
?>