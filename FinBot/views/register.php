<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\views\register.php

/**
 * Vue de la page d'inscription
 * 
 * Variables attendues :
 * $error - Message d'erreur éventuel lors de l'inscription
 * $success - Message de succès après inscription
 */
?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-12 col-md-9">
            <div class="card o-hidden border-0 shadow-lg my-5">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-lg-5 d-none d-lg-block bg-register-image"></div>
                        <div class="col-lg-7">
                            <div class="p-5">
                                <div class="text-center">
                                    <h1 class="h4 text-gray-900 mb-4">Créer un compte FinBot+</h1>
                                </div>
                                
                                <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger">
                                        <?= htmlspecialchars($error) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($success)): ?>
                                    <div class="alert alert-success">
                                        <?= htmlspecialchars($success) ?>
                                        <br>
                                        <a href="index.php" class="alert-link">Se connecter</a>
                                    </div>
                                <?php else: ?>
                                    <form class="user" method="post" action="">
                                        <div class="row mb-3">
                                            <div class="col-sm-6 mb-3 mb-sm-0">
                                                <input type="text" class="form-control form-control-user" id="prenom"
                                                    name="prenom" placeholder="Prénom" required>
                                            </div>
                                            <div class="col-sm-6">
                                                <input type="text" class="form-control form-control-user" id="nom"
                                                    name="nom" placeholder="Nom" required>
                                            </div>
                                        </div>
                                        <div class="form-group mb-3">
                                            <input type="email" class="form-control form-control-user" id="email"
                                                name="email" placeholder="Adresse email" required>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-6 mb-3 mb-sm-0">
                                                <input type="password" class="form-control form-control-user"
                                                    id="password" name="password" placeholder="Mot de passe" required>
                                            </div>
                                            <div class="col-sm-6">
                                                <input type="password" class="form-control form-control-user"
                                                    id="password_confirm" name="password_confirm" placeholder="Confirmer le mot de passe" required>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-user btn-block">
                                            Créer un compte
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <hr>
                                <div class="text-center">
                                    <a class="small" href="forgot-password.php">Mot de passe oublié ?</a>
                                </div>
                                <div class="text-center">
                                    <a class="small" href="index.php">Déjà un compte ? Connexion</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>