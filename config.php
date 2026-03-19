<?php
$host = 'localhost';
$db   = 'laundry_care_db';
$user = 'root';
$pass = ''; // ඔයාගේ password එක මෙතනට දාන්න

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
date_default_timezone_set('Asia/Colombo');
?>
