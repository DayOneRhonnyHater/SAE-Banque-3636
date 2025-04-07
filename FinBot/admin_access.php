<?php
session_start();

// Définir le mot de passe d'accès administrateur (à modifier selon vos besoins)
$admin_password = 'FinBot2025';

// Initialiser les variables
$error = '';
$success = false;

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    
    // Vérifier si le mot de passe est correct
    if ($password === $admin_password) {
        // Créer un jeton d'accès administrateur sans session utilisateur
        $_SESSION['admin_access'] = true;
        $_SESSION['admin_token'] = bin2hex(random_bytes(32));
        
        $success = true;
    } else {
        $error = 'Mot de passe incorrect. Veuillez réessayer.';
    }
}

// Si l'accès administrateur est déjà actif, rediriger
if (isset($_SESSION['admin_access']) && $_SESSION['admin_access'] === true && !$success) {
    header('Location: admin/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès Administrateur - FinBot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .admin-container {
            max-width: 500px;
            width: 100%;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }
        .logo {
            margin-bottom: 25px;
        }
        .logo img {
            max-width: 120px;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .input-group {
            margin-bottom: 20px;
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
            padding: 10px 20px;
            font-weight: 600;
            width: 100%;
        }
        .btn-primary:hover {
            background-color: #375ad3;
            border-color: #375ad3;
        }
        .alert {
            margin-bottom: 25px;
        }
        .admin-notice {
            font-size: 0.85rem;
            color: #999;
            margin-top: 20px;
        }
        .spinner-border {
            width: 3rem;
            height: 3rem;
            margin-bottom: 20px;
        }
    </style>
    <?php if ($success): ?>
    <meta http-equiv="refresh" content="2;url=admin/index.php">
    <?php endif; ?>
</head>
<body>
    <div class="admin-container">
        <?php if ($success): ?>
            <!-- Afficher le message de succès et rediriger -->
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <div class="alert alert-success" role="alert">
                <h4 class="alert-heading">Accès administrateur activé !</h4>
                <p>Vous pouvez maintenant accéder à l'interface d'administration.</p>
                <hr>
                <p class="mb-0">Redirection vers l'interface d'administration...</p>
            </div>
            <p>Si vous n'êtes pas redirigé automatiquement, <a href="admin/index.php" class="btn btn-primary">cliquez ici</a>.</p>
        <?php else: ?>
            <!-- Afficher le formulaire de connexion -->
            <div class="logo">
                <img src="assets/img/logo.png" alt="FinBot Logo" onerror="this.src='https://via.placeholder.com/120x50?text=FinBot'">
                <h4>Accès Administrateur</h4>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="password" class="form-label">Mot de passe administrateur</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Entrez le mot de passe" required autofocus>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-key me-2"></i> Accéder à l'interface d'administration
                </button>
            </form>
            
            <div class="admin-notice">
                <p>Cet accès est réservé au personnel autorisé uniquement.</p>
                <a href="index.php">Retour à l'accueil</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Script pour afficher/masquer le mot de passe
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            if (togglePassword) {
                togglePassword.addEventListener('click', function() {
                    const passwordInput = document.getElementById('password');
                    const icon = this.querySelector('i');
                    
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        passwordInput.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            }
        });
    </script>
</body>
</html>