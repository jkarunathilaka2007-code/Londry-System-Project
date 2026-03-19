<?php
session_start();
require 'config.php';

if (!isset($_POST['password']) || !isset($_SESSION['user_id'])) {
    echo 'fail';
    exit;
}

$input_password = $_POST['password'];
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT password FROM employees WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user && password_verify($input_password, $user['password'])) {
        // password_verify එකෙන් තමයි hashed password එකයි input එකයි check කරන්නේ
        echo 'success';
    } else {
        echo 'fail';
    }
} catch (PDOException $e) {
    echo 'fail';
}