<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-piggy-bank me-2"></i>Ouvrir un compte épargne</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                        </div>
                    <?php endif; ?>

                    <form action="create_epargne.php" method="post">
                        <input type="hidden" name="action" value="compte_epargne">
                        
                        <div class="mb-4">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Pourquoi ouvrir un compte d'épargne ?</strong>
                                <p class="mb-0 mt-2">Les comptes d'épargne vous permettent de faire fructifier votre argent grâce à des taux d'intérêt avantageux. Chaque type de compte répond à des besoins spécifiques d'épargne.</p>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="type_compte" class="form-label">Type de compte épargne</label>
                            <select name="type_compte" id="type_compte" class="form-select" required>
                                <option value="">Sélectionnez un type de compte</option>
                                <?php foreach ($typesEpargne as $type): ?>
                                    <option value="<?= htmlspecialchars($type['id']) ?>">
                                        <?= htmlspecialchars($type['nom']) ?> 
                                        (<?= number_format($type['taux_interet'], 2, ',', ' ') ?>% - 
                                        Plafond: <?= number_format($type['plafond'], 0, ',', ' ') ?>€)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Chaque type de compte a ses propres conditions (taux d'intérêt, plafond, etc.)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="depot_initial" class="form-label">Dépôt initial (min. 10€)</label>
                            <div class="input-group">
                                <input type="number" name="depot_initial" id="depot_initial" class="form-control" 
                                       min="10" step="0.01" required placeholder="Montant du dépôt initial">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="compte_source" class="form-label">Compte à débiter</label>
                            <select name="compte_source" id="compte_source" class="form-select" required>
                                <option value="">Sélectionnez un compte</option>
                                <?php foreach ($comptesCourants as $compte): ?>
                                    <option value="<?= $compte['id'] ?>">
                                        <?= htmlspecialchars($compte['numero_compte']) ?> - 
                                        Solde: <?= number_format($compte['solde'], 2, ',', ' ') ?>€
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Le montant du dépôt initial sera prélevé sur ce compte.</div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="accounts.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Retour aux comptes
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check-circle me-2"></i>Ouvrir le compte
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>