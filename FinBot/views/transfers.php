<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\views\transfers.php

// Récupérer l'ID de l'utilisateur connecté
$userId = $_SESSION['user']['id'];

// Inclure les classes nécessaires
require_once __DIR__ . '/../classes/Compte.php';
require_once __DIR__ . '/../classes/Beneficiaire.php';
require_once __DIR__ . '/../classes/Transaction.php';
require_once __DIR__ . '/../includes/helpers.php';

// Récupérer les comptes de l'utilisateur
$compteManager = Compte::getInstance();
$comptes = $compteManager->getComptesByUser($userId);

// Récupérer les bénéficiaires de l'utilisateur
$beneficiaireManager = Beneficiaire::getInstance();
$beneficiaires = $beneficiaireManager->getBeneficiairesByUser($userId);

// Récupérer les virements récents
$transactionManager = Transaction::getInstance();
$virementsRecents = $transactionManager->getRecentTransfersByUser($userId, 5);

// Préparation des données à pré-remplir en cas de refaire un virement
$virementData = [
    'type' => '',
    'compte_source' => '',
    'compte_destination' => '',
    'beneficiaire' => '',
    'montant' => '',
    'motif' => ''
];

if (!empty($virementARefaire)) {
    $virementData['compte_source'] = $virementARefaire['compte_id'];
    $virementData['montant'] = $virementARefaire['montant'];
    $virementData['motif'] = $virementARefaire['description'];
    
    if ($virementARefaire['compte_destinataire_id']) {
        // Virement interne
        $virementData['type'] = 'interne';
        $virementData['compte_destination'] = $virementARefaire['compte_destinataire_id'];
    } elseif ($virementARefaire['beneficiaire_id']) {
        // Virement externe
        $virementData['type'] = 'externe';
        $virementData['beneficiaire'] = $virementARefaire['beneficiaire_id'];
    }
}
?>

<div class="container-fluid py-4">
    <!-- En-tête de la page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Effectuer un virement</h1>
            <p class="text-muted">Réalisez des virements entre vos comptes ou vers des bénéficiaires</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Retour au tableau de bord
        </a>
    </div>
    
    <!-- Affichage des messages de succès ou d'erreur -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($success ?? '') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error ?? '') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Contenu principal -->
    <div class="row">
        <!-- Formulaire de virement -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Formulaire de virement</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($comptes)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Vous n'avez aucun compte actif pour effectuer des virements.
                        </div>
                    <?php else: ?>
                        <form action="" method="post" id="virementForm">
                            <!-- Type de virement -->
                            <div class="mb-4">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="type_virement" id="type_interne" value="interne" 
                                        <?= ($virementData['type'] === 'interne' || empty($virementData['type'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="type_interne">
                                        <i class="fas fa-exchange-alt me-1"></i> Virement entre mes comptes
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="type_virement" id="type_externe" value="externe" 
                                        <?= $virementData['type'] === 'externe' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="type_externe">
                                        <i class="fas fa-paper-plane me-1"></i> Virement vers un bénéficiaire
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Compte source -->
                            <div class="mb-3">
                                <label for="compte_source" class="form-label">Compte à débiter</label>
                                <select class="form-select" id="compte_source" name="compte_source" required>
                                    <option value="">Choisir un compte</option>
                                    <?php foreach ($comptes as $compte): ?>
                                        <option value="<?= $compte['id'] ?>" data-solde="<?= $compte['solde'] ?>"
                                            <?= $virementData['compte_source'] == $compte['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($compte['type_nom'] . ' - ' . ($compte['numero_compte'] ?? '')) ?> (<?= formatMontant($compte['solde']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="soldeInfo" class="form-text mt-2"></div>
                            </div>
                            
                            <!-- Conteneur virement interne -->
                            <div id="virementInterneContainer" class="mb-3">
                                <label for="compte_destination" class="form-label">Compte à créditer</label>
                                <select class="form-select" id="compte_destination" name="compte_destination">
                                    <option value="">Choisir un compte</option>
                                    <?php foreach ($comptes as $compte): ?>
                                        <option value="<?= $compte['id'] ?>"
                                            <?= $virementData['compte_destination'] == $compte['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($compte['type_nom'] . ' - ' . ($compte['numero_compte'] ?? '')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Le compte à créditer doit être différent du compte à débiter.</div>
                            </div>
                            
                            <!-- Conteneur virement externe -->
                            <div id="virementExterneContainer" class="mb-3" style="display: none;">
                                <label for="beneficiaire" class="form-label">Bénéficiaire</label>
                                <?php if (empty($beneficiaires)): ?>
                                    <div class="alert alert-info mb-2">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Vous n'avez aucun bénéficiaire enregistré.
                                        <a href="beneficiaires.php?action=new" class="alert-link">Ajouter un bénéficiaire</a>
                                    </div>
                                    <select class="form-select" id="beneficiaire" name="beneficiaire" disabled>
                                        <option value="">Aucun bénéficiaire disponible</option>
                                    </select>
                                <?php else: ?>
                                    <select class="form-select" id="beneficiaire" name="beneficiaire">
                                        <option value="">Choisir un bénéficiaire</option>
                                        <?php foreach ($beneficiaires as $beneficiaire): ?>
                                            <option value="<?= $beneficiaire['id'] ?>"
                                                <?= $virementData['beneficiaire'] == $beneficiaire['id'] ? 'selected' : '' ?>
                                                data-iban="<?= htmlspecialchars($beneficiaire['iban'] ?? '') ?>">
                                                <?= htmlspecialchars($beneficiaire['nom'] ?? '') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="beneficiaireInfo" class="form-text mt-2"></div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-end mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#newDestinataireModal">
                                        <i class="fas fa-plus me-1"></i> Ajouter un bénéficiaire
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Montant -->
                            <div class="mb-3">
                                <label for="montant" class="form-label">Montant (€)</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0.01" class="form-control" id="montant" 
                                           name="montant" placeholder="0.00" required
                                           value="<?= !empty($virementData['montant']) ? abs($virementData['montant']) : '' ?>">
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                            
                            <!-- Motif -->
                            <div class="mb-3">
                                <label for="motif" class="form-label">Motif</label>
                                <input type="text" class="form-control" id="motif" name="motif" 
                                       placeholder="Ex: Remboursement, Cadeau, etc." required
                                       value="<?= htmlspecialchars($virementData['motif'] ?? '') ?>">
                            </div>
                            
                            <!-- Date d'exécution (pour les virements différés - fonctionnalité optionnelle) -->
                            <div class="mb-3">
                                <label for="date_execution" class="form-label">Date d'exécution</label>
                                <input type="date" class="form-control" id="date_execution" name="date_execution"
                                       min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
                                <div class="form-text">Laisser la date du jour pour un virement immédiat.</div>
                            </div>
                            
                            <!-- Boutons d'action -->
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-outline-secondary me-md-2" onclick="resetForm()">Annuler</button>
                                <button type="submit" class="btn btn-primary">Valider le virement</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Colonne de droite -->
        <div class="col-lg-4">
            <!-- Virements récents -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Virements récents</h6>
                    <a href="transactions.php?type=VIREMENT" class="btn btn-sm btn-outline-primary">Voir tous</a>
                </div>
                <div class="card-body">
                    <?php if (empty($virementsRecents)): ?>
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-exchange-alt fa-3x text-gray-300"></i>
                            </div>
                            <p>Aucun virement récent</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($virementsRecents as $virement): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= htmlspecialchars($virement['beneficiaire_nom'] ?? $virement['compte_destinataire'] ?? '') ?></h6>
                                        <small><?= formatDate($virement['date_transaction'], true) ?></small>
                                    </div>
                                    <p class="mb-1 fw-bold"><?= formatMontant(abs($virement['montant'])) ?></p>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($virement['description'] ?? '') ?>
                                    </small>
                                    <div class="mt-2">
                                        <a href="?refaire=<?= $virement['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-redo-alt me-1"></i> Refaire ce virement
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Aide -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informations</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6><i class="fas fa-exchange-alt me-2"></i> Virement interne</h6>
                        <p class="small">Transfert d'argent entre vos propres comptes. L'opération est instantanée.</p>
                    </div>
                    <div class="mb-3">
                        <h6><i class="fas fa-paper-plane me-2"></i> Virement externe</h6>
                        <p class="small">Transfert d'argent vers un bénéficiaire. Le délai d'exécution dépend du bénéficiaire (généralement 1 jour ouvré).</p>
                    </div>
                    <div class="mb-3">
                        <h6><i class="fas fa-info-circle me-2"></i> À savoir</h6>
                        <ul class="small">
                            <li>Aucun frais pour les virements entre vos comptes</li>
                            <li>Virements gratuits dans la zone SEPA</li>
                            <li>Montant minimum : 0,01 €</li>
                            <li>Vous pouvez annuler un virement programmé jusqu'à la veille de son exécution</li>
                        </ul>
                    </div>
                    <div class="mb-0">
                        <h6><i class="fas fa-shield-alt me-2"></i> Sécurité</h6>
                        <p class="small">Vérifiez toujours les coordonnées de vos bénéficiaires avant de valider un virement externe.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter un destinataire par email -->
<div class="modal fade" id="newDestinataireModal" tabindex="-1" aria-labelledby="newDestinataireModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="newDestinataireModalLabel">Ajouter un destinataire</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="emailDestinataireForm">
          <div class="mb-3">
            <label for="email_destinataire" class="form-label">Adresse email du destinataire</label>
            <input type="email" class="form-control" id="email_destinataire" name="email_destinataire" 
                   placeholder="example@email.com" required>
            <div class="form-text">Le destinataire doit avoir un compte sur FinBot+.</div>
          </div>
          <div id="destinataireSearchResult"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="button" class="btn btn-primary" id="searchDestinataireBtn">Rechercher</button>
        <button type="button" class="btn btn-success" id="addDestinataireBtn" style="display: none;">Ajouter comme bénéficiaire</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion de l'affichage du formulaire selon le type de virement
    const typeInterne = document.getElementById('type_interne');
    const typeExterne = document.getElementById('type_externe');
    const virementInterneContainer = document.getElementById('virementInterneContainer');
    const virementExterneContainer = document.getElementById('virementExterneContainer');
    
    function toggleVirementType() {
        if (typeInterne.checked) {
            virementInterneContainer.style.display = 'block';
            virementExterneContainer.style.display = 'none';
            document.getElementById('compte_destination').setAttribute('required', 'required');
            document.getElementById('beneficiaire') && document.getElementById('beneficiaire').removeAttribute('required');
        } else {
            virementInterneContainer.style.display = 'none';
            virementExterneContainer.style.display = 'block';
            document.getElementById('compte_destination').removeAttribute('required');
            document.getElementById('beneficiaire') && document.getElementById('beneficiaire').setAttribute('required', 'required');
        }
    }
    
    // Initialisation
    toggleVirementType();
    
    // Écouteurs d'événements
    typeInterne.addEventListener('change', toggleVirementType);
    typeExterne.addEventListener('change', toggleVirementType);
    
    // Afficher le solde du compte source
    const compteSource = document.getElementById('compte_source');
    const soldeInfo = document.getElementById('soldeInfo');
    
    compteSource.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        if (option && option.value !== '') {
            const solde = parseFloat(option.getAttribute('data-solde'));
            soldeInfo.innerHTML = `<strong>Solde disponible:</strong> ${formatMontant(solde)}`;
        } else {
            soldeInfo.innerHTML = '';
        }
    });
    
    // Afficher les infos du bénéficiaire
    const beneficiaire = document.getElementById('beneficiaire');
    const beneficiaireInfo = document.getElementById('beneficiaireInfo');
    
    if (beneficiaire) {
        beneficiaire.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            if (option && option.value !== '') {
                const iban = option.getAttribute('data-iban');
                beneficiaireInfo.innerHTML = `<strong>IBAN:</strong> ${iban}`;
            } else {
                beneficiaireInfo.innerHTML = '';
            }
        });
    }
    
    // Déclencher le changement initial pour afficher les informations si les champs sont pré-remplis
    if (compteSource.value !== '') {
        compteSource.dispatchEvent(new Event('change'));
    }
    
    if (beneficiaire && beneficiaire.value !== '') {
        beneficiaire.dispatchEvent(new Event('change'));
    }
    
    // Validation du formulaire
    const virementForm = document.getElementById('virementForm');
    
    virementForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Empêcher l'envoi par défaut pour valider d'abord
        
        const montantInput = document.getElementById('montant');
        const montant = parseFloat(montantInput.value);
        const compteSourceOption = compteSource.options[compteSource.selectedIndex];
        
        // Vérifier si un compte source a été sélectionné
        if (!compteSource.value) {
            alert('Veuillez sélectionner un compte à débiter.');
            compteSource.focus();
            return false;
        }
        
        // Vérifier le montant
        if (isNaN(montant) || montant <= 0) {
            alert('Veuillez saisir un montant valide (supérieur à 0).');
            montantInput.focus();
            return false;
        }
        
        // Vérifier le solde disponible
        if (compteSourceOption && compteSourceOption.value !== '') {
            const soldeDisponible = parseFloat(compteSourceOption.getAttribute('data-solde'));
            
            if (montant > soldeDisponible) {
                alert('Le montant du virement dépasse le solde disponible sur votre compte.');
                montantInput.focus();
                return false;
            }
        }
        
        // Vérifier le type de virement
        if (typeInterne.checked) {
            const compteDestination = document.getElementById('compte_destination');
            
            // Vérifier si un compte destinataire a été sélectionné
            if (!compteDestination.value) {
                alert('Veuillez sélectionner un compte à créditer.');
                compteDestination.focus();
                return false;
            }
            
            // Vérifier que les comptes sont différents
            if (compteSource.value === compteDestination.value) {
                alert('Le compte à débiter et le compte à créditer ne peuvent pas être identiques.');
                compteDestination.focus();
                return false;
            }
        } else {
            const beneficiaire = document.getElementById('beneficiaire');
            
            // Vérifier si un bénéficiaire a été sélectionné
            if (beneficiaire && !beneficiaire.disabled && !beneficiaire.value) {
                alert('Veuillez sélectionner un bénéficiaire.');
                beneficiaire.focus();
                return false;
            }
        }
        
        // Vérifier le motif
        const motif = document.getElementById('motif').value.trim();
        if (!motif) {
            alert('Veuillez saisir un motif pour le virement.');
            document.getElementById('motif').focus();
            return false;
        }
        
        // Si toutes les validations sont passées, soumettre le formulaire
        this.submit();
    });
});

function resetForm() {
    document.getElementById('virementForm').reset();
    document.getElementById('soldeInfo').innerHTML = '';
    document.getElementById('beneficiaireInfo') && (document.getElementById('beneficiaireInfo').innerHTML = '');
}

function formatMontant(montant) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(montant);
}

// Gestion du modal pour ajouter un destinataire par email
document.addEventListener('DOMContentLoaded', function() {
    const searchDestinataireBtn = document.getElementById('searchDestinataireBtn');
    const addDestinataireBtn = document.getElementById('addDestinataireBtn');
    const destinataireSearchResult = document.getElementById('destinataireSearchResult');
    let foundUserData = null;
    
    // Recherche d'utilisateur par email
    searchDestinataireBtn.addEventListener('click', function() {
        const email = document.getElementById('email_destinataire').value.trim();
        if (!email) {
            alert('Veuillez saisir une adresse email valide.');
            return;
        }
        
        // Afficher le chargement
        destinataireSearchResult.innerHTML = '<div class="text-center py-2"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Recherche en cours...</p></div>';
        
        // Appel AJAX pour rechercher l'utilisateur en utilisant l'API existante
        fetch('api/transactions/verify-beneficiaire.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email: email
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.beneficiaire) {
                foundUserData = data.beneficiaire;
                
                // Créer une liste des comptes disponibles si présents
                let comptesHtml = '';
                if (foundUserData.comptes && foundUserData.comptes.length > 0) {
                    comptesHtml = '<div class="mt-2"><strong>Comptes disponibles :</strong><ul class="mb-0">';
                    foundUserData.comptes.forEach(compte => {
                        comptesHtml += `<li>${compte.numero_compte} (${compte.type_compte})</li>`;
                    });
                    comptesHtml += '</ul></div>';
                }
                
                destinataireSearchResult.innerHTML = `
                    <div class="alert alert-success mt-3">
                        <h6 class="mb-1"><i class="fas fa-user-check me-2"></i>Utilisateur trouvé !</h6>
                        <p class="mb-0"><strong>Nom :</strong> ${foundUserData.prenom} ${foundUserData.nom}</p>
                        ${comptesHtml}
                    </div>`;
                
                // Afficher le bouton d'ajout
                addDestinataireBtn.style.display = 'block';
            } else {
                destinataireSearchResult.innerHTML = `
                    <div class="alert alert-danger mt-3">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        ${data.message || 'Aucun utilisateur trouvé avec cette adresse email.'}
                    </div>`;
                
                // Cacher le bouton d'ajout
                addDestinataireBtn.style.display = 'none';
                foundUserData = null;
            }
        })
        .catch(error => {
            destinataireSearchResult.innerHTML = `
                <div class="alert alert-danger mt-3">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Erreur lors de la recherche. Veuillez réessayer.
                </div>`;
            console.error('Erreur API:', error);
        });
    });
    
    // Gestion du bouton d'ajout de bénéficiaire
    addDestinataireBtn.addEventListener('click', function() {
        if (!foundUserData) return;
        
        // Formulaire pour sélectionner le compte du bénéficiaire
        let compteSelectHtml = '';
        if (foundUserData.comptes && foundUserData.comptes.length > 0) {
            compteSelectHtml = `
                <div class="form-group mt-3">
                    <label for="compte_beneficiaire">Compte à utiliser :</label>
                    <select class="form-control" id="compte_beneficiaire" required>
                        <option value="">-- Sélectionner un compte --</option>
                        ${foundUserData.comptes.map(compte => 
                            `<option value="${compte.numero_compte}">${compte.numero_compte} (${compte.type_compte})</option>`
                        ).join('')}
                    </select>
                </div>`;
        } else {
            destinataireSearchResult.innerHTML += `
                <div class="alert alert-warning mt-2">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Cet utilisateur n'a pas de comptes disponibles.
                </div>`;
            return;
        }
        
        // Afficher un formulaire pour confirmer l'ajout
        destinataireSearchResult.innerHTML = `
            <div class="card mt-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Ajouter comme bénéficiaire</h6>
                </div>
                <div class="card-body">
                    <p><strong>${foundUserData.prenom} ${foundUserData.nom}</strong></p>
                    
                    <div class="form-group">
                        <label for="nom_beneficiaire">Nom du bénéficiaire (pour vos références) :</label>
                        <input type="text" class="form-control" id="nom_beneficiaire" 
                               value="${foundUserData.prenom} ${foundUserData.nom}" required>
                    </div>
                    
                    ${compteSelectHtml}
                    
                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-secondary me-2" id="cancelAddBtn">Annuler</button>
                        <button type="button" class="btn btn-success" id="confirmAddBtn">Confirmer l'ajout</button>
                    </div>
                </div>
            </div>`;
        
        // Gestion des boutons du formulaire
        document.getElementById('cancelAddBtn').addEventListener('click', function() {
            // Revenir à l'affichage précédent
            const comptesList = foundUserData.comptes.map(compte => 
                `<li>${compte.numero_compte} (${compte.type_compte})</li>`
            ).join('');
            
            destinataireSearchResult.innerHTML = `
                <div class="alert alert-success mt-3">
                    <h6 class="mb-1"><i class="fas fa-user-check me-2"></i>Utilisateur trouvé !</h6>
                    <p class="mb-0"><strong>Nom :</strong> ${foundUserData.prenom} ${foundUserData.nom}</p>
                    <div class="mt-2"><strong>Comptes disponibles :</strong><ul class="mb-0">${comptesList}</ul></div>
                </div>`;
        });
        
        document.getElementById('confirmAddBtn').addEventListener('click', function() {
            const nomBeneficiaire = document.getElementById('nom_beneficiaire').value.trim();
            const numeroCompte = document.getElementById('compte_beneficiaire').value;
            
            if (!nomBeneficiaire || !numeroCompte) {
                alert('Veuillez remplir tous les champs');
                return;
            }
            
            // Afficher le chargement
            destinataireSearchResult.innerHTML = '<div class="text-center py-2"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Ajout en cours...</p></div>';
            
            // Appel AJAX pour ajouter le bénéficiaire
            fetch('api/beneficiaires/add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    nom: nomBeneficiaire,
                    numero_compte: numeroCompte,
                    banque: foundUserData.prenom + " " + foundUserData.nom,
                    description: 'Ajouté via recherche par email'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    destinataireSearchResult.innerHTML = `
                        <div class="alert alert-success mt-3">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>${foundUserData.prenom} ${foundUserData.nom}</strong> a été ajouté à vos bénéficiaires avec succès !
                        </div>`;
                    
                    // Cacher le bouton d'ajout après l'opération réussie
                    addDestinataireBtn.style.display = 'none';
                    
                    // Recharger la page après 2 secondes pour mettre à jour la liste des bénéficiaires
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    destinataireSearchResult.innerHTML = `
                        <div class="alert alert-danger mt-3">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            ${data.message || 'Erreur lors de l\'ajout du bénéficiaire.'}
                        </div>`;
                }
            })
            .catch(error => {
                destinataireSearchResult.innerHTML = `
                    <div class="alert alert-danger mt-3">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Erreur technique lors de l'ajout. Veuillez réessayer.
                </div>`;
                console.error('Erreur API:', error);
            });
        });
    });
    
    // Réinitialiser le modal quand il est fermé
    document.getElementById('newDestinataireModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('email_destinataire').value = '';
        destinataireSearchResult.innerHTML = '';
        addDestinataireBtn.style.display = 'none';
        foundUserData = null;
    });
});
</script>