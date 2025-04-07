<?php
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Vérification du rôle administrateur :</h3>";
if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'ADMINISTRATEUR') {
    echo "<p style='color:green'>Vous avez bien le rôle ADMINISTRATEUR</p>";
} else {
    echo "<p style='color:red'>Vous n'avez PAS le rôle ADMINISTRATEUR</p>";
    if (isset($_SESSION['user'])) {
        echo "<p>Votre rôle actuel est : " . ($_SESSION['user']['role'] ?? 'non défini') . "</p>";
    } else {
        echo "<p>Vous n'êtes pas connecté</p>";
    }
}
?>
<p><a href="index.php">Retour à la page d'accueil</a></p>
<p><a href="admin/index.php">Tenter d'accéder à l'espace administrateur</a></p>