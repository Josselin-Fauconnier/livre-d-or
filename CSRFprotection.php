 <?php

// Génération token  CSRF

function generateCSRFToken(){
    if (isset($_SESSION)){
        $_SESSION['token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['token'];
}

// Vérification token CSRF

function verifyCSRFToken($token){
    if (isset($_SESSION['token']) && hash_equals($_SESSION['token'], $token)) {
        return true;
    }
    return false;
}


function CSRFTokenField(){
    $token=generateCSRFToken();
    return'<input type="hidden" name="csrf_token" value="'.htmlspecialchars($token).'">';
}
?>

