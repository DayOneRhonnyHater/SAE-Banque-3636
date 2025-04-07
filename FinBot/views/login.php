<?php
// filepath: c:\wamp\www\projetBUT\SAE.04\SAE-Banque\FinBot\views\login.php

/**
 * Vue de la page de connexion
 * 
 * Variables attendues :
 * $error - Message d'erreur éventuel lors de la connexion
 */
?>
<div class="container">
    <!-- Outer Row -->
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-12 col-md-9">
            <div class="card o-hidden border-0 shadow-lg my-5">
                <div class="card-body p-0">
                    <!-- Nested Row within Card Body -->
                    <div class="row">
                        <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>
                        <div class="col-lg-6">
                            <div class="p-5">
                                <div class="text-center">
                                    <h1 class="h4 text-gray-900 mb-4">FinBot+</h1>
                                    <p class="mb-4">Votre banque en ligne sécurisée</p>
                                </div>
                                
                                <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger">
                                        <?= htmlspecialchars($error) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <form class="user" method="post" action="">
                                    <div class="form-group mb-3">
                                        <input type="email" class="form-control form-control-user"
                                            id="email" name="email" aria-describedby="emailHelp"
                                            placeholder="Adresse email" required>
                                    </div>
                                    <div class="form-group mb-3">
                                        <input type="password" class="form-control form-control-user"
                                            id="password" name="password" placeholder="Mot de passe" required>
                                    </div>
                                    <div class="form-group mb-3">
                                        <div class="custom-control custom-checkbox small">
                                            <input type="checkbox" class="custom-control-input" id="remember" name="remember">
                                            <label class="custom-control-label" for="remember">Se souvenir de moi</label>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-user btn-block">
                                        Connexion
                                    </button>
                                </form>
                                <hr>
        
                                <div class="text-center">
                                    <a class="small" href="register.php">Créer un compte</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>