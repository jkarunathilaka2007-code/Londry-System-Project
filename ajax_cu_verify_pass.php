<?php
session_start();
require 'config.php';

$input_pass = $_POST['password'] ?? '';
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT password FROM customers WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user && password_verify($input_pass, $user['password'])) {
    echo 'success';
} else {
    echo 'fail';
}