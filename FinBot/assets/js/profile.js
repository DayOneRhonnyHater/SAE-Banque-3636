/**
 * Script pour gérer les fonctionnalités interactives de la page profil
 */
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du mode sombre
    setupDarkMode();
    
    // Validation des formulaires
    setupFormValidation();
    
    // Prévisualisation de l'avatar
    setupAvatarPreview();
    
    // Gestion des mots de passe
    setupPasswordStrengthMeter();
});

/**
 * Configuration du mode sombre
 */
function setupDarkMode() {
    // Vérifier si le mode sombre est actif dans les préférences
    const darkModeCheckbox = document.getElementById('mode_sombre');
    
    if (darkModeCheckbox && darkModeCheckbox.checked) {
        document.body.classList.add('dark-mode');
    }
    
    // Ajouter un écouteur d'événement pour le changement
    if (darkModeCheckbox) {
        darkModeCheckbox.addEventListener('change', function() {
            if (this.checked) {
                document.body.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
            } else {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
            }
        });
    }
    
    // Vérifier le paramètre sauvegardé dans localStorage (pour persistance)
    const savedDarkMode = localStorage.getItem('darkMode');
    if (savedDarkMode === 'enabled' && darkModeCheckbox) {
        darkModeCheckbox.checked = true;
        document.body.classList.add('dark-mode');
    }
}

/**
 * Configuration de la validation des formulaires
 */
function setupFormValidation() {
    // Validation du formulaire d'informations personnelles
    const personalForm = document.querySelector('#personal form');
    if (personalForm) {
        personalForm.addEventListener('submit', function(e) {
            const emailInput = this.querySelector('#email');
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (emailInput && !emailPattern.test(emailInput.value)) {
                e.preventDefault();
                showError(emailInput, 'Veuillez entrer une adresse email valide');
                return false;
            }
            
            const phoneInput = this.querySelector('#telephone');
            if (phoneInput && phoneInput.value && !/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/.test(phoneInput.value)) {
                e.preventDefault();
                showError(phoneInput, 'Veuillez entrer un numéro de téléphone valide');
                return false;
            }
            
            const postalCodeInput = this.querySelector('#code_postal');
            if (postalCodeInput && postalCodeInput.value && !/^[0-9]{5}$/.test(postalCodeInput.value)) {
                e.preventDefault();
                showError(postalCodeInput, 'Le code postal doit contenir 5 chiffres');
                return false;
            }
        });
    }
    
    // Validation du formulaire de mot de passe
    const passwordForm = document.querySelector('#security form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const newPassword = this.querySelector('#new_password').value;
            const confirmPassword = this.querySelector('#confirm_password').value;
            
            if (newPassword.length < 8) {
                e.preventDefault();
                showError(this.querySelector('#new_password'), 'Le mot de passe doit contenir au moins 8 caractères');
                return false;
            }
            
            if (!/[A-Z]/.test(newPassword)) {
                e.preventDefault();
                showError(this.querySelector('#new_password'), 'Le mot de passe doit contenir au moins une majuscule');
                return false;
            }
            
            if (!/[a-z]/.test(newPassword)) {
                e.preventDefault();
                showError(this.querySelector('#new_password'), 'Le mot de passe doit contenir au moins une minuscule');
                return false;
            }
            
            if (!/[0-9]/.test(newPassword)) {
                e.preventDefault();
                showError(this.querySelector('#new_password'), 'Le mot de passe doit contenir au moins un chiffre');
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                showError(this.querySelector('#confirm_password'), 'Les mots de passe ne correspondent pas');
                return false;
            }
        });
    }
}

/**
 * Affiche un message d'erreur sous un champ de formulaire
 */
function showError(inputElement, message) {
    // Supprimer les messages d'erreur existants
    const existingError = inputElement.parentNode.querySelector('.text-danger');
    if (existingError) {
        existingError.remove();
    }
    
    // Ajouter la classe d'erreur au champ
    inputElement.classList.add('is-invalid');
    
    // Créer et ajouter le message d'erreur
    const errorDiv = document.createElement('div');
    errorDiv.className = 'text-danger mt-1';
    errorDiv.textContent = message;
    inputElement.parentNode.appendChild(errorDiv);
    
    // Focus sur le champ en erreur
    inputElement.focus();
}

/**
 * Configuration de la prévisualisation de l'avatar
 */
function setupAvatarPreview() {
    const avatarInput = document.getElementById('avatar');
    const avatarContainer = document.querySelector('.profile-avatar-container');
    
    if (avatarInput && avatarContainer) {
        avatarInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    // Vérifier si l'avatar est déjà une image ou un placeholder
                    let avatarImg = avatarContainer.querySelector('img');
                    
                    if (!avatarImg) {
                        // S'il n'y a pas d'image, remplacer le placeholder par une image
                        const placeholder = avatarContainer.querySelector('.profile-avatar-placeholder');
                        if (placeholder) {
                            placeholder.remove();
                        }
                        
                        avatarImg = document.createElement('img');
                        avatarImg.className = 'profile-avatar';
                        avatarImg.alt = 'Photo de profil';
                        avatarContainer.appendChild(avatarImg);
                    }
                    
                    // Mettre à jour l'image avec la nouvelle
                    avatarImg.src = e.target.result;
                };
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
}

/**
 * Configuration de l'indicateur de force du mot de passe
 */
function setupPasswordStrengthMeter() {
    const passwordInput = document.getElementById('new_password');
    
    if (passwordInput) {
        // Créer l'indicateur de force s'il n'existe pas déjà
        let strengthMeter = document.getElementById('password-strength-meter');
        if (!strengthMeter) {
            const meterContainer = document.createElement('div');
            meterContainer.className = 'mt-2';
            
            strengthMeter = document.createElement('div');
            strengthMeter.id = 'password-strength-meter';
            strengthMeter.className = 'progress';
            strengthMeter.style.height = '5px';
            
            const strengthBar = document.createElement('div');
            strengthBar.id = 'password-strength-bar';
            strengthBar.className = 'progress-bar';
            strengthBar.style.width = '0%';
            strengthBar.setAttribute('role', 'progressbar');
            strengthBar.setAttribute('aria-valuenow', '0');
            strengthBar.setAttribute('aria-valuemin', '0');
            strengthBar.setAttribute('aria-valuemax', '100');
            
            strengthMeter.appendChild(strengthBar);
            meterContainer.appendChild(strengthMeter);
            
            const strengthText = document.createElement('div');
            strengthText.id = 'password-strength-text';
            strengthText.className = 'form-text mt-1';
            meterContainer.appendChild(strengthText);
            
            passwordInput.parentNode.insertBefore(meterContainer, passwordInput.nextSibling);
        }
        
        // Ajouter un écouteur d'événement pour calculer la force du mot de passe
        passwordInput.addEventListener('input', function() {
            const strength = calculatePasswordStrength(this.value);
            updatePasswordStrengthUI(strength);
        });
    }
}

/**
 * Calcule la force d'un mot de passe
 * @returns {number} Score de 0 à 100
 */
function calculatePasswordStrength(password) {
    if (!password) return 0;
    
    let score = 0;
    
    // Longueur de base : 5 points par caractère jusqu'à 25 points
    score += Math.min(25, password.length * 5);
    
    // Bonus pour les caractères spéciaux
    if (/[A-Z]/.test(password)) score += 15;
    if (/[a-z]/.test(password)) score += 10;
    if (/[0-9]/.test(password)) score += 10;
    if (/[^A-Za-z0-9]/.test(password)) score += 15;
    
    // Bonus pour la diversité de caractères
    const uniqueChars = new Set(password.split('')).size;
    score += Math.min(25, uniqueChars * 2);
    
    return Math.min(100, score);
}

/**
 * Met à jour l'interface utilisateur avec la force du mot de passe
 */
function updatePasswordStrengthUI(strength) {
    const strengthBar = document.getElementById('password-strength-bar');
    const strengthText = document.getElementById('password-strength-text');
    
    if (strengthBar && strengthText) {
        // Mettre à jour la barre de progression
        strengthBar.style.width = `${strength}%`;
        strengthBar.setAttribute('aria-valuenow', strength);
        
        // Déterminer la classe et le texte en fonction de la force
        let barClass, text;
        
        if (strength < 25) {
            barClass = 'bg-danger';
            text = 'Très faible';
        } else if (strength < 50) {
            barClass = 'bg-warning';
            text = 'Faible';
        } else if (strength < 75) {
            barClass = 'bg-info';
            text = 'Moyen';
        } else {
            barClass = 'bg-success';
            text = 'Fort';
        }
        
        // Supprimer toutes les classes de couleur et ajouter la nouvelle
        strengthBar.className = 'progress-bar ' + barClass;
        strengthText.textContent = `Force du mot de passe : ${text}`;
    }
}

/**
 * Fonction pour confirmer les actions sensibles
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Gestionnaire pour le déplacement entre les onglets
const tabLinks = document.querySelectorAll('.nav-link[data-bs-toggle="tab"]');
tabLinks.forEach(link => {
    link.addEventListener('click', function() {
        // Animation de transition lors du changement d'onglet
        document.querySelector(this.dataset.bsTarget).classList.add('animate-fade-in');
        setTimeout(() => {
            document.querySelector(this.dataset.bsTarget).classList.remove('animate-fade-in');
        }, 300);
    });
});

// Ajout d'une animation CSS
document.head.insertAdjacentHTML('beforeend', `
<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}
</style>
`);