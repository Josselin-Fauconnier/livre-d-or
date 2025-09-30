<?php
require_once "session.php";
require_once 'CSRFprotection.php';

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

$message = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user'])) {
   $token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        http_response_code(403);
        die('Erreur CSRF. <a href="connexion.php">Retour</a>');
    } 
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['comment_id'])) {
        $comment_id = (int)$_POST['comment_id'];
        
        $conn = new mysqli("localhost", "root", "", "livreor");
        if ($conn->connect_error) {
            $message = "Erreur de connexion à la base de données : " . $conn->connect_error;
        } else {
            $conn->set_charset('utf8mb4');
            
            $stmt = $conn->prepare("DELETE FROM commentaires WHERE id = ? AND id_utilisateur = ?");
            $stmt->bind_param("ii", $comment_id, $_SESSION['user']['id']);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $message = "Commentaire supprimé avec succès !";
                } else {
                    $message = "Erreur : commentaire non trouvé ou non autorisé.";
                }
            } else {
                $message = "Erreur lors de la suppression : " . $stmt->error;
            }
            
            $stmt->close();
            $conn->close();
        }
    }
    
    elseif (isset($_POST['action']) && $_POST['action'] === 'update' && isset($_POST['comment_id'])) {
        $comment_id = (int)$_POST['comment_id'];
        $new_comment = trim($_POST['new_comment'] ?? '');
        
        if (!mb_check_encoding($new_comment, 'UTF-8')) {
            $new_comment = mb_convert_encoding($new_comment, 'UTF-8', 'auto');
        }
        
        $new_comment = htmlspecialchars($new_comment, ENT_QUOTES, 'UTF-8');
        
        if (empty($new_comment)) {
            $message = "Le commentaire ne peut pas être vide.";
        } else {
            $conn = new mysqli("localhost", "root", "", "livreor");
            if ($conn->connect_error) {
                $message = "Erreur de connexion à la base de données : " . $conn->connect_error;
            } else {
                $conn->set_charset('utf8mb4');
                
                $stmt = $conn->prepare("UPDATE commentaires SET commentaire = ? WHERE id = ? AND id_utilisateur = ?");
                $stmt->bind_param("sii", $new_comment, $comment_id, $_SESSION['user']['id']);
                
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $message = "Commentaire modifié avec succès !";
                    } else {
                        $message = "Erreur : commentaire non trouvé ou non autorisé.";
                    }
                } else {
                    $message = "Erreur lors de la modification : " . $stmt->error;
                }
                
                $stmt->close();
                $conn->close();
            }
        }
    }
}


$comments_per_page = 10; 
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page); 
$offset = ($current_page - 1) * $comments_per_page;

$edit_comment_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

$conn = new mysqli("localhost", "root", "", "livreor");

if ($conn->connect_error) {
    $message = "Erreur de connexion à la base de données : " . $conn->connect_error;
} else {
    $conn->set_charset("utf8mb4");
    
    $count_query = "SELECT COUNT(*) as total FROM commentaires";
    $count_result = $conn->query($count_query);
    $total_comments = $count_result->fetch_assoc()['total'];
    
    $total_pages = ceil($total_comments / $comments_per_page);
    
    $query = "SELECT c.id, c.commentaire, c.date, c.id_utilisateur, u.login 
              FROM commentaires c 
              JOIN utilisateurs u ON c.id_utilisateur = u.id 
              ORDER BY c.date DESC 
              LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $comments_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
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
            
            <?php if (empty($comments)): ?>
                <p class="aucun_commentaire">Aucun commentaire pour le moment.</p>
            <?php else: ?>
                <div class="commentaires">
                    <?php foreach ($comments as $comment): ?>
                        <div class="commentaire">
                            <div class="info_commentaire">
                                Posté le <?php 
                                $comment_date = new DateTime($comment['date']);
                                echo $comment_date->format('d/m/Y'); 
                                ?> par <?php echo htmlspecialchars($comment['login']); ?>
                            </div>
                            
                            <?php if ($edit_comment_id == $comment['id'] && isset($_SESSION['user']) && $_SESSION['user']['id'] == $comment['id_utilisateur']): ?>
                    
                                <form method="POST" action="">
                                     <?php echo CSRFTokenField(); ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                    <div class="edit_comment_form">
                                        <textarea name="new_comment" rows="4" required><?php echo htmlspecialchars($comment['commentaire']); ?></textarea>
                                        <div class="edit_actions">
                                            <button type="submit" class="btn_save">Sauvegarder</button>
                                            <a href="livre-or.php?page=<?php echo $current_page; ?>" class="btn_cancel">Annuler</a>
                                        </div>
                                    </div>
                                </form>
         <?php else: ?>
                                <div class="texte_commentaire">
                                    <?php echo nl2br($comment['commentaire']); ?>
                                </div>
                                
                                <?php if (isset($_SESSION['user']) && $_SESSION['user']['id'] == $comment['id_utilisateur']): ?>
                                    <div class="comment_user_actions">
                                        <a href="livre-or.php?edit=<?php echo $comment['id']; ?>&page=<?php echo $current_page; ?>" class="btn_edit">Éditer</a>
                                        
                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ?');">
                         <?php echo CSRFTokenField(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                  <button type="submit" class="btn_delete">Supprimer</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($current_page > 1): ?>
                            <a href="?page=<?php echo $current_page - 1; ?>" class="page_lien">« Précédent</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $current_page): ?>
                                <span class="page_actuelle"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>" class="page_lien"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <a href="?page=<?php echo $current_page + 1; ?>" class="page_lien">Suivant »</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </article>
    </div>
</main>
</body>
</html>