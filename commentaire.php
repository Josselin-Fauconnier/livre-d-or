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
    $commentaire = htmlspecialchars(trim($_POST['commentaire'] ?? ''), ENT_QUOTES, 'utf8mb4');
    
    if (empty($commentaire)) {
        $message = "Le commentaire ne peut pas être vide.";
    } else {
        $conn = new mysqli("localhost", "root", "", "livreor");
        
        if ($conn->connect_error) {
            $message = "Erreur de connexion à la base de données : " . $conn->connect_error;
        } else {
            $conn->set_charset("utf8");
            
            $stmt = $conn->prepare("INSERT INTO commentaires (commentaire, id_utilisateur, date) VALUES (?, ?, NOW())");
            $stmt->bind_param("si", $commentaire, $_SESSION['user']['id']);
            
            if ($stmt->execute()) {
                header("Location: livre-or.php");
                exit();
            } else {
                $message = "Erreur lors de l'ajout du commentaire : " . $stmt->error;
            }
            
            $stmt->close();
            $conn->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajout d'un commentaire sur le livre d'or </title>
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
            <h2>Ajouter un commentaire</h2>
            
            <?php if (!empty($message)): ?>
                <div class="message">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" >
                <div class="formulaire-groupe">
                    <label for="commentaire">Votre commentaire :</label>
                    <textarea  id="commentaire" name="commentaire" rows="5" placeholder="Poster ici un commentaire pertinent"><?php echo htmlspecialchars($_POST['commentaire'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" class="bouton_ins">Publier le commentaire</button>
            </form>
            
            <div class="retour_livre">
                <a href="livre-or.php">Retour au livre d'or</a>
            </div>
        </article>
    </div>
</main>
</body>
</html>