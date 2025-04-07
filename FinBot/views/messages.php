<?php
// Récupération des données nécessaires
require_once __DIR__ . '/../includes/helpers.php';

// Récupérer les informations de l'utilisateur connecté
$userId = $_SESSION['user']['id'];
$userName = $_SESSION['user']['prenom'] . ' ' . $_SESSION['user']['nom'];
$role = $_SESSION['user']['role'];

// Récupérer l'historique des conversations
$chatHistory = $_SESSION['chat_history'] ?? [];

// Questions suggérées pour démarrer la conversation
$suggestedQuestions = [
    "Quels sont les meilleurs produits d'épargne disponibles ?",
    "Comment optimiser mon budget mensuel ?",
    "Quelles sont les options pour un prêt immobilier ?",
    "Que faire en cas de découvert bancaire ?",
    "Comment investir en bourse pour débutant ?",
    "Quelles sont les assurances bancaires recommandées ?"
];
?>

<div class="container-fluid py-4">
    <!-- En-tête de la page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">FinBot - Conseiller Financier Virtuel</h1>
            <p class="text-muted">
                Posez vos questions sur vos finances, les produits bancaires ou demandez des conseils
            </p>
        </div>
        <button type="button" class="btn btn-outline-danger" id="resetChatBtn">
            <i class="fas fa-redo-alt me-2"></i>Nouvelle conversation
        </button>
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
    
    <!-- Interface de chat -->
    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Suggestions de questions</h6>
                </div>
                <div class="card-body">
                    <div class="chat-options">
                        <?php foreach($suggestedQuestions as $question): ?>
                            <div class="chat-option" onclick="sendSuggestedQuestion(this)">
                                <?= htmlspecialchars($question) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex flex-column">
                        <h6 class="font-weight-bold text-primary mb-3">À propos de FinBot</h6>
                        <p class="small">
                            FinBot est votre assistant financier intelligent, disponible 24/7 pour répondre à vos questions bancaires et financières.
                        </p>
                        <p class="small">
                            <i class="fas fa-info-circle text-info me-2"></i> Pour des opérations complexes ou des décisions importantes, consultez votre conseiller bancaire personnel.
                        </p>
                        <p class="small text-muted mt-auto">
                            <i class="fas fa-shield-alt me-2"></i> Vos échanges sont sécurisés et confidentiels
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-9 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-robot me-2"></i> Conversation avec FinBot
                    </h6>
                    <span class="badge bg-success"><i class="fas fa-circle me-1" style="font-size: 0.6rem;"></i> En ligne</span>
                </div>
                <div class="card-body d-flex flex-column" style="min-height: 600px;">
                    <!-- Zone de chat -->
                    <div class="chat-container mb-3" id="chatContainer">
                        <?php foreach($chatHistory as $message): ?>
                            <div class="chat-message <?= $message['role'] ?>">
                                <?php if($message['role'] === 'ai'): ?>
                                    <div class="chat-avatar">
                                        <i class="fas fa-robot"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="message-bubble">
                                    <?= nl2br(htmlspecialchars($message['content'])) ?>
                                    <span class="message-time"><?= $message['time'] ?></span>
                                </div>
                                
                                <?php if($message['role'] === 'user'): ?>
                                    <div class="chat-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Indicateur de frappe (caché par défaut) -->
                        <div class="typing-indicator" id="typingIndicator" style="display: none;">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                    
                    <!-- Zone de saisie -->
                    <div class="chat-input-container mt-auto">
                        <form id="chatForm" class="d-flex">
                            <textarea class="chat-input form-control" id="userMessage" placeholder="Écrivez votre message ici..." rows="2" required></textarea>
                            <button type="submit" class="chat-send-btn btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fonction pour faire défiler la conversation vers le bas
function scrollToBottom() {
    const chatContainer = document.getElementById('chatContainer');
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

// Ajouter un message à la conversation
function addMessage(content, role) {
    const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    const chatContainer = document.getElementById('chatContainer');
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `chat-message ${role}`;
    
    if (role === 'ai') {
        messageDiv.innerHTML = `
            <div class="chat-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="message-bubble">
                ${content.replace(/\n/g, '<br>')}
                <span class="message-time">${time}</span>
            </div>
        `;
    } else {
        messageDiv.innerHTML = `
            <div class="message-bubble">
                ${content.replace(/\n/g, '<br>')}
                <span class="message-time">${time}</span>
            </div>
            <div class="chat-avatar">
                <i class="fas fa-user"></i>
            </div>
        `;
    }
    
    // Insérer avant l'indicateur de frappe
    const typingIndicator = document.getElementById('typingIndicator');
    chatContainer.insertBefore(messageDiv, typingIndicator);
    
    scrollToBottom();
}

// Afficher/masquer l'indicateur de frappe
function toggleTypingIndicator(show) {
    const indicator = document.getElementById('typingIndicator');
    indicator.style.display = show ? 'flex' : 'none';
    if (show) scrollToBottom();
}

// Gestion de la soumission du formulaire
document.getElementById('chatForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const userMessageInput = document.getElementById('userMessage');
    const userMessage = userMessageInput.value.trim();
    
    if (!userMessage) return;
    
    // Ajouter le message utilisateur à la conversation
    addMessage(userMessage, 'user');
    userMessageInput.value = '';
    
    // Afficher l'indicateur de frappe
    toggleTypingIndicator(true);
    
    // Envoyer le message à l'API
    fetch('messages.php?action=chat', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            message: userMessage
        })
    })
    .then(response => response.json())
    .then(data => {
        // Masquer l'indicateur de frappe
        toggleTypingIndicator(false);
        
        if (data.success) {
            // Ajouter la réponse du bot à la conversation
            addMessage(data.response, 'ai');
        } else {
            // Afficher un message d'erreur
            addMessage("Désolé, j'ai rencontré un problème. Veuillez réessayer.", 'ai');
            console.error('Error:', data.error);
        }
    })
    .catch(error => {
        toggleTypingIndicator(false);
        addMessage("Désolé, j'ai rencontré un problème de connexion. Veuillez réessayer.", 'ai');
        console.error('Error:', error);
    });
});

// Gestion des questions suggérées
function sendSuggestedQuestion(element) {
    const question = element.textContent.trim();
    document.getElementById('userMessage').value = question;
    document.getElementById('chatForm').dispatchEvent(new Event('submit'));
}

// Réinitialisation de la conversation
document.getElementById('resetChatBtn').addEventListener('click', function() {
    if (confirm('Êtes-vous sûr de vouloir commencer une nouvelle conversation ? L\'historique actuel sera perdu.')) {
        fetch('messages.php?action=reset_chat', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recharger la page pour afficher la nouvelle conversation
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur lors de la réinitialisation de la conversation.');
        });
    }
});

// Faire défiler vers le bas au chargement
document.addEventListener('DOMContentLoaded', scrollToBottom);
</script>