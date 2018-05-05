<?php

$servername = "localhost";
$username = "legabrie_Gabe";
$password = "Asian00";
$dbname = "legabrie_movie";


$db = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
// set the PDO error mode to exception
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

?>