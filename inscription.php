<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    header("Location:index.php");
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirmPassword'] ?? '');

    if (empty($login) || empty($password) || empty($confirmPassword)) {
        $message = "Remplir tous les champs est obligatoire.";
    } elseif ($password !== $confirmPassword) {
        $message = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 12) {
        $message = "Le mot de passe doit contenir au moins 12 caractères.";
    } else {
        $conn = new mysqli("localhost", "root", "root", "livreor");

        if ($conn->connect_error) {
            $message = "Erreur de connexion à la base de données : " . $conn->connect_error;
        } else {
            $conn->set_charset("utf8");
            
            $stm = $conn->prepare("SELECT id FROM utilisateurs WHERE login = ?");
            $stm->bind_param("s", $login);
            $stm->execute();
            $result = $stm->get_result();

            if ($result->num_rows > 0) {
                $message = "Ce login est déjà utilisé.";
            } else {
                $hashPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO utilisateurs (login, password) VALUES (?, ?)");
                $stmt->bind_param("ss", $login, $hashPassword);

                if ($stmt->execute()) {
                    header("Location:connexion.php");
                    exit();
                } else {
                    $message = "Erreur lors de l'inscription : " . $stmt->error;
                }

                $stmt->close();
            }

            $stm->close();
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
</body>
</html>
