<?php

$host = "localhost";
$dbname = "blog";
$username = "root";
$password = "";

try {

    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Connection Failed: ".$e->getMessage());
}


?>