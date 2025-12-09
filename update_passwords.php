<?php
include '_conf.php';

$bdd = mysqli_connect($serveurBDD, $userBDD, $mdpBDD, $nomBDD);
if (!$bdd) die("Erreur connexion BDD");

$password = 'password';
$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $bdd->prepare("UPDATE utilisateur SET motdepasse = ?");
$stmt->bind_param("s", $hash);
$stmt->execute();

$affected = $stmt->affected_rows;
echo "✅ $affected utilisateurs ont été mis à jour avec le nouveau hash de mot de passe (BCRYPT)";
echo "\n\nLe mot de passe par défaut pour tous les utilisateurs est maintenant : password\n";
mysqli_close($bdd);
?>
