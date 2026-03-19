<?php
session_start();
require 'config.php';

// Security Check - Admin ද කියලා බලනවා
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// ID එක ලැබිලා තියෙනවාද බලනවා
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // 1. කලින් image එකේ නම හොයාගන්න (Server එකෙන් delete කරන්න ඕන නිසා)
    $stmt = $pdo->prepare("SELECT service_image FROM services WHERE id = ?");
    $stmt->execute([$id]);
    $service = $stmt->fetch();

    if ($service) {
        $image_path = "uploads/services/" . $service['service_image'];

        // 2. Image එක folder එකේ තියෙනවා නම් ඒක අයින් කරනවා
        if (!empty($service['service_image']) && file_exists($image_path)) {
            unlink($image_path);
        }

        // 3. Database එකෙන් record එක delete කරනවා
        $delete_stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
        if ($delete_stmt->execute([$id])) {
            // සාර්ථකව delete වුණාම ආපහු services list එකට යනවා
            header("Location: services.php?msg=deleted");
            exit();
        } else {
            echo "Error deleting record.";
        }
    } else {
        echo "Service not found.";
    }
} else {
    header("Location: services.php");
    exit();
}