<?php
// Vérifier que les variables nécessaires sont définies
if (!isset($transactions) || !isset($userId)) {
    die("Erreur: variables nécessaires non définies.");
}
?>

<div class="container-fluid py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <?php if (isset($compteSelectionne)): ?>
                Transactions du compte <?= htmlspecialchars($compteSelectionne['numero_compte'] ?? '') ?>
            <?php else: ?>
                Toutes les transactions
            <?php endif; ?>
        </h1>
        <div>
            <button class="btn btn-primary" id="toggleFilterBtn">
                <i class="fas fa-filter me-1"></i> Filtrer
            </button>
            <?php if (isset($compteId) || !empty($dateDebut) || !empty($dateFin) || !empty($typeTransaction) || !empty($montantMin) || !empty($montantMax) || !empty($search)): ?>
                <a href="transactions.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i> Réinitialiser les filtres
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Panneau de filtres (caché par défaut) -->
    <div class="card shadow mb-4" id="filterPanel" style="display: none;">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Filtrer les transactions</h6>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="closeFilterBtn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="card-body">
            <form action="transactions.php" method="get" id="filterForm">
                <?php if ($compteId): ?>
                    <input type="hidden" name="compte_id" value="<?= $compteId ?>">
                <?php endif; ?>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="date_debut" class="form-label">Date de début</label>
                        <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?= $dateDebut ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="date_fin" class="form-label">Date de fin</label>
                        <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?= $dateFin ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="type" class="form-label">Type de transaction</label>
                        <select class="form-select" id="type" name="type">
                            <option value="">Tous</option>
                            <option value="CREDIT" <?= $typeTransaction == 'CREDIT' ? 'selected' : '' ?>>Crédit</option>
                            <option value="DEBIT" <?= $typeTransaction == 'DEBIT' ? 'selected' : '' ?>>Débit</option>
                            <option value="VIREMENT" <?= $typeTransaction == 'VIREMENT' ? 'selected' : '' ?>>Virement</option>
                            <option value="INTERET" <?= $typeTransaction == 'INTERET' ? 'selected' : '' ?>>Intérêts</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="montant_min" class="form-label">Montant minimum</label>
                        <input type="number" class="form-control" id="montant_min" name="montant_min" value="<?= $montantMin ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="montant_max" class="form-label">Montant maximum</label>
                        <input type="number" class="form-control" id="montant_max" name="montant_max" value="<?= $montantMax ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="search" class="form-label">Recherche</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher dans les descriptions...">
                    </div>
                </div>
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> Appliquer les filtres
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des transactions -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <?php if (empty($transactions)): ?>
                <div class="alert alert-info">
                    Aucune transaction à afficher pour le moment.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="transactionsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Compte</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Montant</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr class="transaction-row" data-id="<?= $transaction['id'] ?>">
                                    <td><?= date('d/m/Y H:i', strtotime($transaction['date_transaction'])) ?></td>
                                    <td><?= htmlspecialchars($transaction['numero_compte']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $transaction['type_transaction'] == 'CREDIT' || $transaction['montant'] > 0 ? 'success' : 'danger' ?>">
                                            <?= htmlspecialchars($transaction['type_transaction']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($transaction['description']) ?></td>
                                    <td class="text-<?= ($transaction['montant'] >= 0) ? 'success' : 'danger' ?>">
                                        <?= number_format(abs($transaction['montant']), 2, ',', ' ') ?> €
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary toggle-details-btn" data-id="<?= $transaction['id'] ?>">
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                    </td>
                                </tr>
                                <!-- Ligne de détails cachée par défaut -->
                                <tr class="transaction-details d-none" id="details-<?= $transaction['id'] ?>">
                                    <td colspan="6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <h6 class="fw-bold">Informations générales</h6>
                                                        <p><strong>Date:</strong> <?= date('d/m/Y H:i', strtotime($transaction['date_transaction'])) ?></p>
                                                        <p><strong>Type:</strong> 
                                                            <span class="badge bg-<?= $transaction['type_transaction'] == 'CREDIT' || $transaction['montant'] > 0 ? 'success' : 'danger' ?>">
                                                                <?= htmlspecialchars($transaction['type_transaction']) ?>
                                                            </span>
                                                        </p>
                                                        <p><strong>Montant:</strong> 
                                                            <span class="text-<?= ($transaction['montant'] >= 0) ? 'success' : 'danger' ?>">
                                                                <?= number_format(abs($transaction['montant']), 2, ',', ' ') ?> €
                                                            </span>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6 class="fw-bold">Détails additionnels</h6>
                                                        <p><strong>Description:</strong> <?= htmlspecialchars($transaction['description']) ?></p>
                                                        <?php if (!empty($transaction['beneficiaire'])): ?>
                                                            <p><strong>Bénéficiaire:</strong> <?= htmlspecialchars($transaction['beneficiaire']) ?></p>
                                                        <?php endif; ?>
                                                        <?php if (!empty($transaction['compte_destinataire'])): ?>
                                                            <p><strong>Compte destinataire:</strong> <?= htmlspecialchars($transaction['compte_destinataire']) ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <?php if ($transaction['type_transaction'] == 'VIREMENT'): ?>
                                                    <div class="text-end mt-3">
                                                        <a href="transfers.php?refaire=<?= $transaction['id'] ?>" class="btn btn-success btn-sm">
                                                            <i class="fas fa-redo me-1"></i> Refaire ce virement
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Script pour gérer l'affichage du panneau de filtres -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du panneau de filtres
    const toggleFilterBtn = document.getElementById('toggleFilterBtn');
    const closeFilterBtn = document.getElementById('closeFilterBtn');
    const filterPanel = document.getElementById('filterPanel');
    
    toggleFilterBtn.addEventListener('click', function() {
        if (filterPanel.style.display === 'none') {
            filterPanel.style.display = 'block';
            // Animation simple
            filterPanel.style.opacity = 0;
            setTimeout(() => {
                filterPanel.style.transition = 'opacity 0.3s ease';
                filterPanel.style.opacity = 1;
            }, 10);
        } else {
            filterPanel.style.opacity = 0;
            setTimeout(() => {
                filterPanel.style.display = 'none';
            }, 300);
        }
    });
    
    closeFilterBtn.addEventListener('click', function() {
        filterPanel.style.opacity = 0;
        setTimeout(() => {
            filterPanel.style.display = 'none';
        }, 300);
    });
    
    // Si des filtres sont déjà appliqués, afficher directement le panneau
    <?php if (!empty($dateDebut) || !empty($dateFin) || !empty($typeTransaction) || !empty($montantMin) || !empty($montantMax) || !empty($search)): ?>
    filterPanel.style.display = 'block';
    <?php endif; ?>
    
    // Gestion des boutons pour afficher/masquer les détails
    const toggleButtons = document.querySelectorAll('.toggle-details-btn');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const transactionId = this.getAttribute('data-id');
            const detailsRow = document.getElementById('details-' + transactionId);
            
            // Basculer l'affichage de la ligne de détails
            detailsRow.classList.toggle('d-none');
            
            // Changer l'icône du bouton
            const icon = this.querySelector('i');
            if (icon.classList.contains('fa-chevron-down')) {
                icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            } else {
                icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            }
        });
    });
});
</script>

<style>
/* Styles pour le panneau de filtres */
#filterPanel {
    transition: opacity 0.3s ease;
    margin-bottom: 1.5rem;
}

/* Styles pour améliorer l'affichage des détails */
.transaction-details .card {
    border-left: 4px solid #4e73df;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    margin: 0.5rem 0;
}
.transaction-details td {
    background-color: #f8f9fc;
    padding: 0;
}
.toggle-details-btn:focus {
    box-shadow: none;
}
</style>