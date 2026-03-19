<?php
session_start();
require 'config.php';

$new_pass = $_POST['new_pass'] ?? '';
$user_id = $_SESSION['user_id'];

if (!empty($new_pass)) {
    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE customers SET password = ? WHERE id = ?");
    if ($stmt->execute([$hashed, $user_id])) {
        echo 'success';
    } else {
        echo 'fail';
    }
}