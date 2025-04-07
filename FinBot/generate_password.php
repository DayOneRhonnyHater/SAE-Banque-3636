<?php
// Ce fichier est temporaire et doit être supprimé après utilisation
$password = 'Admin2023!';
$hash = password_hash($password, PASSWORD_BCRYPT);
echo "Mot de passe : $password<br>";
echo "Hash : $hash<br>";