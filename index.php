<?php
session_start();
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
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Présentation du livre d'or</title>
    <link rel="stylesheet" href="style_or.css">
</head>
<body>
<header >
   <div>
        <p> <?php
        if(isset($_SESSION['user'])){
            echo"Bienvenue,". htmlspecialchars($_SESSION['user']['login']);
        }else{
            echo"Bienvenue invité";
        }
            ?>
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
   <div class="conteneur">
        <article class="article_presentation">
            <h1>Présentation du site</h1>
            <p> Ce site sert à recuillier divers retour sur les projets pister sur ce githubs</p>
        </article>
   </div>
</main>
</body>
</html>