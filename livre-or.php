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


$commentaires_par_page = 10; 
$page_actuelle = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page_actuelle = max(1, $page_actuelle); 

$offset = ($page_actuelle - 1) * $commentaires_par_page;


$conn = new mysqli("localhost", "root", "", "livreor");
$message = "";

if ($conn->connect_error) {
    $message = "Erreur de connexion à la base de données : " . $conn->connect_error;
} else {
    $conn->set_charset("utf8");
    
    $count_query = "SELECT COUNT(*) as total FROM commentaires";
    $count_result = $conn->query($count_query);
    $total_commentaires = $count_result->fetch_assoc()['total'];
    
    
    $total_pages = ceil($total_commentaires / $commentaires_par_page);
    
    $query = "SELECT c.commentaire, c.date, u.login 
              FROM commentaires c 
              JOIN utilisateurs u ON c.id_utilisateur = u.id 
              ORDER BY c.date DESC 
              LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $commentaires_par_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $commentaires = [];
    while ($row = $result->fetch_assoc()) {
        $commentaires[] = $row;
    }
    
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Livre d'or</title>
    <meta name="description" content="Livre d'or des projets github de Josselin Fauconnier" />
    <link rel="stylesheet" href="style_or.css">
</head>
<body>
<header>
    <div>
        <p><?php
        if(isset($_SESSION['user'])){
            echo "Bienvenue, " . htmlspecialchars($_SESSION['user']['login']);
        } else {
            echo "Bienvenue invité";
        }
        ?></p>
        <p><?php echo "Nous sommes le " . $date_FR->format($format_date); ?></p>
    </div>
</header>
<main>
    <nav id="nav-bar">
        <?php if (isset($_SESSION['user'])): ?>
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
        <article class="livre_or">
            <h1>Livre d'or</h1>
            
            <?php if (isset($_SESSION['user'])): ?>
                <div class="lien_commentaire">
                    <a href="commentaire.php" class="bouton_commentaire">Ajouter un commentaire</a>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($message)): ?>
                <div class="message">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($commentaires)): ?>
                <p class="aucun_commentaire">Aucun commentaire pour le moment.</p>
            <?php else: ?>
                <div class="commentaires">
                    <?php foreach ($commentaires as $commentaire): ?>
                        <div class="commentaire">
                            <div class="info_commentaire">
                                Posté le <?php 
                                $date_commentaire = new DateTime($commentaire['date']);
                                echo $date_commentaire->format('d/m/Y'); 
                                ?> par <?php echo htmlspecialchars($commentaire['login']); ?>
                            </div>
                            <div class="texte_commentaire">
                                <?php echo nl2br(htmlspecialchars($commentaire['commentaire'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page_actuelle > 1): ?>
                            <a href="?page=<?php echo $page_actuelle - 1; ?>" class="page_lien">« Précédent</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page_actuelle): ?>
                                <span class="page_actuelle"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>" class="page_lien"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page_actuelle < $total_pages): ?>
                            <a href="?page=<?php echo $page_actuelle + 1; ?>" class="page_lien">Suivant »</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </article>
    </div>
</main>
</body>
</html>