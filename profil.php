<?php
require_once "session.php";
require_once 'CSRFprotection.php';

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
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        http_response_code(403);
        die('Erreur CSRF. <a href="connexion.php">Retour</a>');
    }

    $new_login = htmlspecialchars(trim($_POST['new_login'] ?? ''), ENT_QUOTES, 'UTF-8');
    $actual_password = $_POST['actual_password'] ?? '';
    $new_password= trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

 
    if (!empty($new_password) && empty($actual_password)) {
        $message = "Veuillez saisir votre mot de passe actuel pour changer de mot de passe.";
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $message = "Les nouveaux mots de passe ne correspondent pas.";
    } elseif (!empty($new_password)) {
        $errors_password = validatePassword($new_password);
        if (!empty($errors_password)){
            $message = implode(", ", $errors_password);
        }
    }

   
    if (empty($message) && (!empty($new_login) || !empty($new_password))) {
        $conn = new mysqli("localhost", "root", "", "livreor");
        if ($conn->connect_error) {
            $message = "Erreur de connexion à la base de données : " . $conn->connect_error;
        } else {
            $conn->set_charset('utf8mb4');

            $updateFields = [];
            $params = [];
            $types = "";

            
            if (!empty($new_password)) {
                $stmt = $conn->prepare("SELECT password FROM utilisateurs WHERE id = ?");
                $stmt->bind_param("i", $_SESSION['user']['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    if (!password_verify($actual_password, $user['password'])) {
                        $message = "Mot de passe actuel incorrect.";
                    }
                }
                $stmt->close();

                if (empty($message)) {
                    $hash_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $updateFields[] = "password = ?";
                    $params[] = $hash_new_password;
                    $types .= "s";
                }
            }

           
            if (!empty($new_login) && empty($message)) {
                
                if ($new_login === $_SESSION['user']['login']) {
                    $message = "Le nouveau login est identique à l'actuel.";
                } else {
                    
                    $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE login = ? AND id != ?");
                    $stmt->bind_param("si", $new_login, $_SESSION['user']['id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        $message = "Ce login est déjà utilisé par un autre utilisateur.";
                    }
                    $stmt->close();

                    if (empty($message)) {
                        $updateFields[] = "login = ?";
                        $params[] = $new_login;
                        $types .= "s";
                    }
                }
            }

            
            if (empty($message) && !empty($updateFields)) {
                $query = "UPDATE utilisateurs SET " . implode(", ", $updateFields) . " WHERE id = ?";
                $params[] = $_SESSION['user']['id'];
                $types .= "i";

                $stmt = $conn->prepare($query);
                $stmt->bind_param($types, ...$params);

                if ($stmt->execute()) {
                    if (!empty($new_login)) {
                        $_SESSION['user']['login'] = $new_login;
                    }
                    $message = "Profil mis à jour avec succès !";
                } else {
                    $message = "Erreur lors de la mise à jour : " . $stmt->error;
                }
                $stmt->close();
            }
            $conn->close();
        }
    }
}

$comments_per_page = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page);
$offset = ($current_page - 1) * $comments_per_page;

$conn = new mysqli("localhost", "root", "", "livreor");
$user_comments = [];
$total_pages = 0;

if (!$conn->connect_error) {
    $conn->set_charset('utf8mb4');

    $count_query = "SELECT COUNT(*) as total FROM commentaires WHERE id_utilisateur = ?";
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param("i", $_SESSION['user']['id']);
    $stmt->execute();
    $count_result = $stmt->get_result();
    $total_comments = $count_result->fetch_assoc()['total'];
    $stmt->close();

    $total_pages = ceil($total_comments / $comments_per_page);

    $query = "SELECT id, commentaire, date FROM commentaires WHERE id_utilisateur = ? ORDER BY date DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $_SESSION['user']['id'], $comments_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $user_comments[] = $row;
    }

    $stmt->close();
    $conn->close();
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
            <div class="profile_section">
                <h2>Modifier mon profil</h2>

                <?php if (!empty($message)): ?>
                    <div class="message">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php echo CSRFTokenField(); ?>

                    <div class="formulaire-groupe">
                        <label for="new_login">Nouveau login :</label>
                        <input type="text" id="new_login" name="new_login" value="">
                        <small>Laissez vide si vous ne voulez pas changer votre login</small>
                    </div>

                    <div class="formulaire-groupe">
                        <label for="actual_password">Mot de passe actuel :</label>
                        <input type="password" id="actual_password" name="actual_password">
                        <small>Obligatoire seulement si vous changez de mot de passe</small>
                    </div>

                    <div class="formulaire-groupe">
                        <label for="new_password">Nouveau mot de passe :</label>
                        <input type="password" id="new_password" name="new_password">
                        <small>Minimum 12 caractères, avec au moins une majuscule, un chiffre et un caractère spécial</small>
                    </div>

                    <div class="formulaire-groupe">
                        <label for="confirm_password">Confirmer le nouveau mot de passe :</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>

                    <button type="submit" class="bouton_ins">Mettre à jour le profil</button>
                </form>
            </div>

            <div class="user_comments_section">
                <h2>Mes derniers commentaires</h2>
                <p><small>Pour modifier ou supprimer vos commentaires, rendez-vous sur <a href="livre-or.php">le livre d'or</a>.</small></p>

                <?php if (empty($user_comments)): ?>
                    <p class="aucun_commentaire">Vous n'avez encore publié aucun commentaire.</p>
                <?php else: ?>
                    <?php foreach ($user_comments as $comment): ?>
                        <div class="user_comment_item">
                            <div class="comment_date">
                                Publié le 
                                <?php 
                                $comment_date = new DateTime($comment['date']);
                                echo $comment_date->format('d/m/Y à H:i'); 
                                ?>
                            </div>
                            <div class="comment_text">
                                <?php echo nl2br(htmlspecialchars($comment['commentaire'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

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
            </div>

            <div class="retour_livre">
                <a href="livre-or.php">Retour au livre d'or</a>
            </div>
        </article>
    </div>
</main>
</body>
</html>