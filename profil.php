<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();
date_default_timezone_set("Europe/Paris");

$date_FR = new IntlDateFormatter(
    'fr_FR',
    IntlDateFormatter::FULL,
    IntlDateFormatter::FULL,
    "Europe/Paris",
    IntlDateFormatter::GREGORIAN,
    "EEEE d MMMM y HH:mm"
);

$format_date = new DateTime();

if (!isset($_SESSION['user'])) {
    header("Location: connexion.php");
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nouveau_login = htmlspecialchars(trim($_POST['nouveau_login'] ?? ''), ENT_QUOTES, 'UTF-8');
    $mot_de_passe_actuel = $_POST['mot_de_passe_actuel'] ?? '';
    $nouveau_mot_de_passe = trim($_POST['nouveau_mot_de_passe'] ?? '');
    $confirmer_mot_de_passe = trim($_POST['confirmer_mot_de_passe'] ?? '');

    if (empty($nouveau_login)) {
        $message = "Le nouveau login ne peut pas être vide.";
    } elseif (!empty($nouveau_mot_de_passe) && empty($mot_de_passe_actuel)) {
        $message = "Veuillez saisir votre mot de passe actuel pour changer de mot de passe.";
    } elseif (!empty($nouveau_mot_de_passe) && $nouveau_mot_de_passe !== $confirmer_mot_de_passe) {
        $message = "Les nouveaux mots de passe ne correspondent pas.";
    } elseif (!empty($nouveau_mot_de_passe) && strlen($nouveau_mot_de_passe) < 12) {
        $message = "Le nouveau mot de passe doit contenir au moins 12 caractères.";
    } else {
        $conn = new mysqli("localhost", "root", "", "livreor");
        
        if ($conn->connect_error) {
            $message = "Erreur de connexion à la base de données : " . $conn->connect_error;
        } else {
            $conn->set_charset('utf8mb4');
            
            if (!empty($nouveau_mot_de_passe)) {
                $stmt = $conn->prepare("SELECT password FROM utilisateurs WHERE id = ?");
                $stmt->bind_param("i", $_SESSION['user']['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    if (!password_verify($mot_de_passe_actuel, $user['password'])) {
                        $message = "Mot de passe actuel incorrect.";
                        $stmt->close();
                        $conn->close();
                    }
                } else {
                    $message = "Erreur lors de la vérification du mot de passe.";
                    $stmt->close();
                    $conn->close();
                }
                $stmt->close();
            }
            
            if (empty($message)) {
                $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE login = ? AND id != ?");
                $stmt->bind_param("si", $nouveau_login, $_SESSION['user']['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $message = "Ce login est déjà utilisé par un autre utilisateur.";
                } else {
                    if (!empty($nouveau_mot_de_passe)) {
                        $hash_nouveau_mot_de_passe = password_hash($nouveau_mot_de_passe, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE utilisateurs SET login = ?, password = ? WHERE id = ?");
                        $stmt->bind_param("ssi", $nouveau_login, $hash_nouveau_mot_de_passe, $_SESSION['user']['id']);
                    } else {
                        $stmt = $conn->prepare("UPDATE utilisateurs SET login = ? WHERE id = ?");
                        $stmt->bind_param("si", $nouveau_login, $_SESSION['user']['id']);
                    }
                    
                    if ($stmt->execute()) {
                        $_SESSION['user']['login'] = $nouveau_login;
                        $message = "Profil mis à jour avec succès !";
                    } else {
                        $message = "Erreur lors de la mise à jour : " . $stmt->error;
                    }
                }
                $stmt->close();
            }
            $conn->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier mon profil</title>
    <link rel="stylesheet" href="style_or.css">
</head>
<body>
<header>
    <div>
        <p><?php echo "Bienvenue, " . htmlspecialchars($_SESSION['user']['login']); ?></p>
        <p><?php echo "Nous sommes le " . $date_FR->format($format_date); ?></p>
    </div>
</header>
<main>
    <nav id="nav-bar">
        <a href="index.php">L'index</a>
        <a href="profil.php">Mon profil</a>
        <a href="livre-or.php">Le livre</a>
        <a href="commentaire.php">Faire un commentaire</a>
        <a href="deconnexion.php">Se déconnecter</a>
    </nav>
    
    <div class="conteneur_centrage_page">
        <article class="formulaire">
            <h2>Modifier mon profil</h2>
            
            <?php if (!empty($message)): ?>
                <div class="message">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="formulaire-groupe">
                    <label for="nouveau_login">Nouveau login :</label>
                    <input type="text" id="nouveau_login" name="nouveau_login" 
                           value="<?php echo htmlspecialchars($_POST['nouveau_login'] ?? $_SESSION['user']['login']); ?>" required>
                </div>

                <div class="formulaire-groupe">
                    <label for="mot_de_passe_actuel">Mot de passe actuel :</label>
                    <input type="password" id="mot_de_passe_actuel" name="mot_de_passe_actuel">
                    <small>Obligatoire pour changer le mot de passe</small>
                </div>

                <div class="formulaire-groupe">
                    <label for="nouveau_mot_de_passe">Nouveau mot de passe :</label>
                    <input type="password" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe">
                    <small>Minimum 12 caractères </small>
                </div>

                <div class="formulaire-groupe">
                    <label for="confirmer_mot_de_passe">Confirmer le nouveau mot de passe :</label>
                    <input type="password" id="confirmer_mot_de_passe" name="confirmer_mot_de_passe">
                </div>

                <button type="submit" class="bouton_ins">Mettre à jour le profil</button>
            </form>
            
            <div class="retour_livre">
                <a href="livre-or.php">Retour au livre d'or</a>
            </div>
        </article>
    </div>
</main>
</body>
</html>