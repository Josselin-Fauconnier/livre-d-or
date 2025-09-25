<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();
date_default_timezone_get();

$date_FR= new IntlDateFormatter (
    'fr_FR',
    IntlDateFormatter::FULL,
    IntlDateFormatter::FULL,
    "Europe/Paris",
    IntlDateFormatter::GREGORIAN,
    "EEEE d MMMM y HH:mm"
);


$format_date= new DateTime();

if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = htmlspecialchars(trim($_POST['login'] ?? ''), ENT_QUOTES, 'utf8mb4');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        $message = "Tous les champs sont obligatoires.";
    } else {
       
        $conn = new mysqli("localhost", "root", "", "livreor");

        if ($conn->connect_error) {
            $message = "Erreur de connexion à la base de données : " . $conn->connect_error;
        } else {
            $conn->set_charset('utf8mb4');

            $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE login = ?");
            $stmt->bind_param("s", $login);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'login' => $user['login'],
                    ];  
                    header("Location: index.php");
                    exit();
                } else {
                    $message = "Mot de passe incorrect.";
                }
            } else {
                $message = "Login incorrect ou utilisateur inexistant.";
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
    <title>Inscription livre d'or</title>
    <meta name="description" content="Page de connexion du livre d'or github de Josselin Fauconnier" />
    <link rel="stylesheet" href="style_or.css">
</head>
<body>
 <header>
    <div>
        <p> <?php echo"Bienvenue invité";?>
    </p>
    <p>
      <?php  echo "Nous sommes le " .$date_FR->format($format_date)  ?>
    </p>
   </div> 
 </header>
  <main>
<nav id="nav-bar">
        <?php if (isset($_SESSION['user'])):?>
            <a href="index.php">L'index</a>
            <a href="profil.php">Mon profil</a>
            <a href="livre-or.php">Le livre</a>
            <a href="commentaire.php">Faire un commentaire</a>
            <a href="deconnexion.php">Se déconnecter</a>
        <?php else: ?>
            <a href="index.php">L'index</a>
            <a href="inscription.php">S'inscrire</a>
            <a href="connexion.php">Se connecter</a>
            <a href="livre-or.php">Le livre</a>
        <?php endif; ?>
    </nav>
 
    <div class="conteneur_centrage_page">

    <article class="formulaire">

<h2>Se connecter</h2>

   <?php if (!empty($message)): ?>
            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="formulaire-groupe">
                <label for="login">Login :</label>
                <input type="text" id="login" name="login" value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>" required>
            </div>

            <div class="formulaire-groupe">
                <label for="password">Mot de passe :</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="bouton_ins">Se connecter</button>
        </form>        

 </div>
  </main>
</body>
</html>





