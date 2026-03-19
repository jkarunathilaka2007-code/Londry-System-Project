<?php
session_start();
require 'config.php';

// Admin කෙනෙක්ද කියලා check කිරීම
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_paid') {
    
    $emp_id = $_POST['employee_id'];
    $amount = $_POST['amount'];
    $month  = $_POST['month']; // format: YYYY-MM
    $admin_id = $_SESSION['user_id'];

    try {
        // 01. දැනටමත් මේ මාසෙට පඩි ගෙවලාද කියලා check කිරීම
        $check = $pdo->prepare("SELECT id FROM salary_payments WHERE employee_id = ? AND month_year = ?");
        $check->execute([$emp_id, $month]);

        if ($check->rowCount() > 0) {
            echo json_encode(['status' => 'exists', 'message' => 'Salary already paid for this month.']);
            exit();
        }

        // 02. පඩිය ගෙවූ බවට Record එකක් දැමීම
        $stmt = $pdo->prepare("INSERT INTO salary_payments (employee_id, amount, month_year, paid_at, admin_id) VALUES (?, ?, ?, NOW(), ?)");
        $result = $stmt->execute([$emp_id, $amount, $month, $admin_id]);

        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Payment recorded successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        }

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}