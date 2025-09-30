<?php
require_once "session.php";


if (session_status()===PHP_SESSION_ACTIVE){
    $_SESSION=array();
    if (isset($_COOKIE[session_name()])){
        setcookie(session_name(),'',time()-5400,'/');
}
    session_destroy();
}

 header("Location: index.php");
  exit();
?>