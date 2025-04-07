// Scripts pour la gestion des utilisateurs

// Fonction pour confirmer la suppression
function confirmDelete(userId, userName) {
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('deleteUserName').textContent = userName;
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

// Fonction pour changer le statut
function toggleStatus(userId) {
    document.getElementById('statusUserId').value = userId;
    
    const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
    statusModal.show();
}

// Validation du formulaire
(function() {
    'use strict';
    
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            // Vérification de la correspondance des mots de passe
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (password && confirmPassword && 
                password.value !== confirmPassword.value && 
                (password.value !== '' || confirmPassword.value !== '')) {
                confirmPassword.setCustomValidity('Les mots de passe ne correspondent pas');
                event.preventDefault();
                event.stopPropagation();
            } else if (confirmPassword) {
                confirmPassword.setCustomValidity('');
            }
            
            form.classList.add('was-validated');
        }, false);
    });
})();