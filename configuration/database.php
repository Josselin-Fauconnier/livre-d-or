<?php
 $db_configuration = [
    'host'=>'localhost',
    'username'=>'root',
    'password'=>'',
    'database'=>'livreor',
    'charset' => 'utf8mb4',
 ];


 

 function getDbConfiguration(array $config) :array {
    return $config;
 }

 print_r(getDbConfiguration($db_configuration));


?>