<?php
// FinBot/chatbot.php
session_start();
require_once __DIR__ . '/config/app.php';

// Vérifier si l'utilisateur est connecté
require_once __DIR__ . '/includes/auth_functions.php';
try {
    checkAuth();
} catch (Exception $e) {
    header('Location: index.php');
    exit;
}

// Inclure l'en-tête
include __DIR__ . '/templates/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card mt-5">
                <div class="card-header bg-primary text-white">
                    <h3>FinBot Assistant</h3>
                </div>
                <div class="card-body">
                    <div id="chat-messages" class="mb-4" style="height: 400px; overflow-y: auto;">
                        <div class="message bot-message">
                            <p>Bonjour ! Je suis votre assistant FinBot. Comment puis-je vous aider aujourd'hui ?</p>
                        </div>
                    </div>
                    <div class="input-group">
                        <input type="text" id="user-input" class="form-control" placeholder="Posez votre question...">
                        <div class="input-group-append">
                            <button id="send-btn" class="btn btn-primary">Envoyer</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatMessages = document.getElementById('chat-messages');
    const userInput = document.getElementById('user-input');
    const sendBtn = document.getElementById('send-btn');
    
    function addMessage(text, isUser = false) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${isUser ? 'user-message' : 'bot-message'}`;
        
        const paragraph = document.createElement('p');
        paragraph.textContent = text;
        messageDiv.appendChild(paragraph);
        
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    function sendMessage() {
        const message = userInput.value.trim();
        if (message === '') return;
        
        // Afficher le message de l'utilisateur
        addMessage(message, true);
        userInput.value = '';
        
        // Afficher un indicateur de chargement
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'message bot-message loading';
        loadingDiv.innerHTML = '<p>En train de répondre...</p>';
        chatMessages.appendChild(loadingDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        // Envoyer la requête à l'API
        fetch('api/chatbot/send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ message: message }),
        })
        .then(response => response.json())
        .then(data => {
            // Supprimer l'indicateur de chargement
            chatMessages.removeChild(loadingDiv);
            
            // Afficher la réponse du bot
            if (data.error) {
                addMessage('Erreur: ' + data.error);
            } else {
                addMessage(data.response);
            }
        })
        .catch(error => {
            chatMessages.removeChild(loadingDiv);
            addMessage('Une erreur est survenue lors de la communication avec le serveur.');
            console.error('Error:', error);
        });
    }
    
    sendBtn.addEventListener('click', sendMessage);
    userInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
});
</script>

<!-- Ajouter les styles pour le chatbot -->
<style>
.message {
    margin-bottom: 15px;
    padding: 10px 15px;
    border-radius: 10px;
    max-width: 80%;
}

.user-message {
    background-color: #e3f2fd;
    margin-left: auto;
    text-align: right;
}

.bot-message {
    background-color: #f5f5f5;
    margin-right: auto;
}

.loading {
    opacity: 0.7;
}
</style>

<?php
// Inclure le pied de page
include __DIR__ . '/templates/footer.php';
?>
