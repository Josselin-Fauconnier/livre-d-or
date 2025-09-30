<?php

// Vérifie si la session est déjà demarré, si non la démarre
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>