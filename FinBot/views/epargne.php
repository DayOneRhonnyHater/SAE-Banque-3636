<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/auth.php';
require_once '../classes/Database.php';
checkAuth();

// Debug des informations
echo "<pre>";
echo "Session user ID: " . ($_SESSION['user']['id'] ?? 'Non défini') . "\n";

// Récupération des comptes épargne avec leurs types
$db = Database::getInstance();
$query = "SELECT c.id, c.numero_compte, c.solde, 
                 t.id as type_id, t.nom as type_nom, t.taux_interet, t.plafond
          FROM comptes c
          JOIN types_comptes t ON c.type_compte_id = t.id
          WHERE c.utilisateur_id = ? 
          AND t.id IN ('LIVRET_A', 'PEL', 'LDDS')
          AND t.actif = true";
$params = [$_SESSION['user']['id']];

$comptes = $db->select($query, $params);

// Affichage des données de debug
echo "Query: " . $query . "\n";
echo "Params: "; print_r($params);
echo "Résultat comptes: "; print_r($comptes);
echo "</pre>";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Opération Épargne</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
</head>
<body class="p-4">
    <div class="container">
        <h1>Test Opération Épargne</h1>
        
        <?php if (!empty($comptes)): ?>
            <div class="row mt-4">
                <?php foreach ($comptes as $compte): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($compte['type_nom']) ?></h5>
                            <p class="card-text">
                                N° : <?= htmlspecialchars($compte['numero_compte']) ?><br>
                                Solde : <span class="solde" data-compte-id="<?= $compte['id'] ?>">
                                    <?= number_format($compte['solde'], 2, ',', ' ') ?>
                                </span> €<br>
                                Taux d'intérêt : <?= $compte['taux_interet'] ?>%
                                <?php if ($compte['plafond']): ?>
                                    <br>Plafond : <?= number_format($compte['plafond'], 2, ',', ' ') ?> €
                                <?php endif; ?>
                            </p>
                            <button class="btn btn-primary" 
                                    onclick="showOperationModal(<?= $compte['id'] ?>, 
                                                             '<?= htmlspecialchars($compte['type_nom']) ?>', 
                                                             <?= $compte['solde'] ?>,
                                                             <?= $compte['plafond'] ?? 'null' ?>)">
                                Faire une opération
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-4">
                Aucun compte épargne trouvé.
            </div>
        <?php endif; ?>

        <!-- Modal modifié -->
        <div class="modal fade" id="operationModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouvelle opération</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formOperation">
                            <input type="hidden" id="compteId">
                            <div class="alert alert-info">
                                Compte : <span id="compteType"></span><br>
                                Solde actuel : <span id="compteSolde"></span> €
                            </div>
                            <div class="form-group">
                                <label>Type d'opération</label>
                                <select class="form-control" id="typeOperation" required>
                                    <option value="DEPOT">Dépôt</option>
                                    <option value="RETRAIT">Retrait</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Montant</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="montantOperation"
                                       min="0.01"
                                       step="0.01"
                                       required>
                            </div>
                            <div class="form-group">
                                <label>Description (optionnelle)</label>
                                <textarea class="form-control" 
                                          id="descriptionOperation">
                                </textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" 
                                class="btn btn-secondary" 
                                data-dismiss="modal">Annuler</button>
                        <button type="button" 
                                class="btn btn-primary"
                                onclick="effectuerOperation()">Valider</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
    let compteActuel = null;

    function showOperationModal(compteId, type, solde, plafond) {
        compteActuel = {
            id: compteId,
            type: type,
            solde: solde,
            plafond: plafond
        };
        
        $('#compteId').val(compteId);
        $('#compteType').text(type);
        $('#compteSolde').text(solde.toFixed(2));
        $('#operationModal').modal('show');
    }

    function sauvegarderOperation() {
        const data = {
            compte_id: compteActuel.id,
            type: $('#typeOperation').val(),
            montant: parseFloat($('#montantOperation').val()),
            description: $('#descriptionOperation').val().trim()
        };

        // Envoi de la requête AJAX
        $.ajax({
            url: '../api/epargnes/operation.php',
            method: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    alert('Opération enregistrée avec succès');
                    location.reload(); // Recharge la page pour afficher le nouveau solde
                } else {
                    alert('Erreur : ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Erreur lors de l\'opération : ' + xhr.responseJSON?.message || 'Erreur inconnue');
            }
        });
    }

    function effectuerOperation() {
        if (!compteActuel) {
            alert('Erreur : aucun compte sélectionné');
            return;
        }

        const type = $('#typeOperation').val();
        const montant = parseFloat($('#montantOperation').val());
        const description = $('#descriptionOperation').val().trim();
        
        if (isNaN(montant) || montant <= 0) {
            alert('Montant invalide');
            return;
        }
        
        // Vérifications supplémentaires
        if (type === 'RETRAIT' && montant > compteActuel.solde) {
            alert('Solde insuffisant');
            return;
        }

        if (type === 'DEPOT' && compteActuel.plafond !== null) {
            const soldeApresDepot = compteActuel.solde + montant;
            if (soldeApresDepot > compteActuel.plafond) {
                alert('Ce dépôt dépasserait le plafond autorisé');
                return;
            }
        }
        
        // Au lieu de simuler, on sauvegarde réellement l'opération
        sauvegarderOperation();
    }
    </script>
</body>
</html>