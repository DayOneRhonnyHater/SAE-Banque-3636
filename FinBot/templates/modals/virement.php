
<!-- Modal Nouveau Virement -->
<div class="modal fade" id="virementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouveau virement</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formVirement" method="POST">
                    <input type="hidden" name="action" value="virement">
                    
                    <div class="form-group">
                        <label>Compte source</label>
                        <select name="compte_source" class="form-control" required>
                            <option value="">Sélectionnez un compte</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Montant</label>
                        <div class="input-group">
                            <input type="number" name="montant" class="form-control" 
                                   required min="0.01" step="0.01">
                            <div class="input-group-append">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Effectuer le virement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

