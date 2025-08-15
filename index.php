<?php
session_start();
date_default_timezone_set("Europe/Paris");

$actual_date=date('j F,d,m,Y-H:i:s');

$date_FR= new IntlDateFormatter (
    'fr_FR',
    IntlDateFormatter::FULL,
    IntlDateFormatter::FULL,
    "Europe/Paris",
    IntlDateFormatter::GREGORIAN
);

$date_message='Nous sommes le' . $date_FR;
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Présentation du livre d'or</title>
    <link rel="stylesheet" href="style_or.css">
</head>
<body>
<header class="en-tête">
    <p> <?php
        if(isset($_SESSION['user'])){
            echo"Bienvenue,". htmlspecialchars($_SESSION['user']['login']);
        }else{
            echo"Bienvenue invité";
        }
            ?>
    </p>
    <p>
      <?php  echo $date_message ?>
    </p>
</header>
<main>
        
</main>
</body>
</html>