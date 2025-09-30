<?php
require_once "session.php";
require_once 'CSRFprotection.php';

date_default_timezone_set("Europe/Paris");


function validatePassword($password){
    $errors=[];

    if(strlen($password)<12){
        $errors[]='Le mot de passe doit être composé d\'au moins 12 caractères';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Le mot de passe doit avoir au moins une majuscule";
    }
    
    if (!preg_match('/[!@#$%^&_+\-=\[\].<>?]/', $password)) {
        $errors[] = "Le mot de passe doit avoir au moins un caractère spécial";
    }

    if(!preg_match('/[0-9]/',$password)){
        $errors[]="Le mot de passe doit avoir au moins un chiffre";
    }
    
    return $errors;
}

date_default_timezone_set("Europe/Paris");

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
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        http_response_code(403);
        die('Erreur CSRF. <a href="connexion.php">Retour</a>');
    }

    $login = htmlspecialchars(trim($_POST['login'] ?? ''), ENT_QUOTES, "UTF-8");
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirmPassword'] ?? '');

    if (empty($login) || empty($password) || empty($confirmPassword)) {
        $message = "Remplir tous les champs est obligatoire.";
    } elseif ($password !== $confirmPassword) {
        $message = "Les mots de passe ne correspondent pas.";
    } else {
        $errors_password = validatePassword($password);
        if(!empty($errors_password)){
            $message = implode(", ", $errors_password);
        } else {
            $conn = new mysqli("localhost", "root", "", "livreor");

            if ($conn->connect_error) {
                $message = "Erreur de connexion à la base de données : " . $conn->connect_error;
            } else {
                $conn->set_charset("utf8mb4");
                
                $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE login = ?");
                $stmt->bind_param("s", $login);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $message = "Ce login est déjà utilisé.";
                } else {
                    $hashPassword = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $conn->prepare("INSERT INTO utilisateurs (login, password) VALUES (?, ?)");
                    $stmt->bind_param("ss", $login, $hashPassword);

                    if ($stmt->execute()) {
                        header("Location: connexion.php");
                        exit();
                    } else {
                        $message = "Erreur lors de l'inscription : " . $stmt->error;
                    }
                }

                $stmt->close();
                $conn->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription livre d'or</title>
    <meta name="description" content="Page d'inscription du livre d'or github de Josselin Fauconnier" />
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
    
 <article class="formulaire_inscription">

<h2>Créer un compte</h2>

            <?php if (!empty($message)): ?>
                <div class="message">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <?php echo CSRFTokenField(); ?>
                <div class="formulaire-groupe">
                    <label for="login">Login :</label>
                    <input type="text" id="login" name="login" value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>" required>
                </div>

                <div class="formulaire-groupe">
                    <label for="password">Mot de passe :</label>
                    <input type="password" id="password" name="password" required>
                    <small>Minimum 12 caractères. Il faut une majuscule, un caractère spécial et un chiffre</small>
                </div>

                <div class="formulaire-groupe">
                    <label for="confirmPassword">Confirmation du mot de passe :</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                </div>

                <button type="submit" class="bouton_ins">S'inscrire</button>
            </form>
    </article>
 </div>

  </main>
</body>
</html>