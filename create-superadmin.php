<?php 
require_once "includes/db.php";
 
$fullname = "Abdulrasaq Sanni";
$email = "rasaq001@gmail.com";
$password = "Incorrect12$";
$role = "super_admin";

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $db->prepare("INSERT INTO admins (full_name, email, password, role) VALUES(?, ?, ?, ?)");
$stmt->execute([$fullname, $email, $hashedPassword, $role]);

echo "Super admin created successfully";