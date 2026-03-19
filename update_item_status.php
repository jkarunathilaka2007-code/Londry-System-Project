<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $new_status = isset($_POST['status']) ? $_POST['status'] : '';

    if ($item_id > 0 && !empty($new_status)) {
        try {
            // ඔයාගේ table එක order_items, primary key එක id
            $stmt = $pdo->prepare("UPDATE order_items SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $item_id]);

            if ($stmt->rowCount() >= 0) { // rowCount 0 වෙන්නත් පුළුවන් එකම status එක ආයෙ දුන්නොත්
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No changes made']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid ID or Status']);
    }
}
exit;