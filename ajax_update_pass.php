<?php
session_start();
require 'config.php';

if (!isset($_POST['new_pass']) || !isset($_SESSION['user_id'])) {
    echo 'fail';
    exit;
}

$new_pass = $_POST['new_pass'];
$hashed_password = password_hash($new_pass, PASSWORD_DEFAULT); // අලුත් password එක hash කරනවා
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("UPDATE employees SET password = ? WHERE id = ?");
    if($stmt->execute([$hashed_password, $user_id])) {
        echo 'success';
    } else {
        echo 'fail';
    }
} catch (PDOException $e) {
    echo 'fail';
}