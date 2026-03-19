<?php
session_start();
require 'config.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: services.php"); exit(); }

// පරණ දත්ත ලබාගැනීම
$stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
$stmt->execute([$id]);
$service = $stmt->fetch();

if (!$service) { die("Service not found."); }

// Update Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['service_name'];
    $price = $_POST['price'];
    $lead_time = $_POST['lead_time'];
    $image_name = $service['service_image']; // පරණ image එක default තියාගන්න

    // අලුත් image එකක් upload කළොත්
    if (!empty($_FILES['service_image']['name'])) {
        $target_dir = "uploads/services/";
        $image_name = time() . "_" . basename($_FILES["service_image"]["name"]);
        move_uploaded_file($_FILES["service_image"]["tmp_name"], $target_dir . $image_name);
    }

    $update_stmt = $pdo->prepare("UPDATE services SET service_name = ?, price = ?, lead_time = ?, service_image = ? WHERE id = ?");
    if ($update_stmt->execute([$name, $price, $lead_time, $image_name, $id])) {
        header("Location: services.php?msg=updated");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Service | Luxe Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-dark: #080808;
            --glass: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.12);
        }

        body {
            background-color: var(--bg-dark);
            background-image: linear-gradient(rgba(8, 8, 8, 0.9), rgba(8, 8, 8, 0.9)), url('uploads/resources/bg2.jpg');
            background-size: cover;
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            padding: 20px;
        }

        .edit-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid var(--glass-border) !important;
            color: #fff !important;
            border-radius: 12px;
            padding: 12px;
        }

        .form-control:focus { box-shadow: 0 0 0 3px rgba(255,255,255,0.1); }

        .btn-update {
            background: #fff;
            color: #000;
            border: none;
            border-radius: 15px;
            padding: 12px;
            font-weight: 700;
            width: 100%;
            transition: 0.3s;
        }

        .btn-update:hover { transform: scale(1.02); background: #f0f0f0; }
        
        .preview-img {
            width: 80px; height: 80px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="edit-card">
    <div class="d-flex align-items-center mb-4">
        <a href="services.php" class="text-white me-3 fs-4"><i class="bi bi-arrow-left-circle-fill"></i></a>
        <h4 class="m-0 fw-bold">Edit Service</h4>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3 text-center">
            <label class="d-block text-white-50 small mb-2">Current Image</label>
            <img src="uploads/services/<?= $service['service_image'] ?>" class="preview-img">
        </div>

        <div class="mb-3">
            <label class="form-label small text-white-50">Service Name</label>
            <input type="text" name="service_name" class="form-control" value="<?= htmlspecialchars($service['service_name']) ?>" required>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label small text-white-50">Price (LKR)</label>
                <input type="number" step="0.01" name="price" class="form-control" value="<?= $service['price'] ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label small text-white-50">Lead Time</label>
                <input type="text" name="lead_time" class="form-control" value="<?= htmlspecialchars($service['lead_time']) ?>" required>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label small text-white-50">Change Image (Optional)</label>
            <input type="file" name="service_image" class="form-control">
        </div>

        <button type="submit" class="btn-update">Update Configuration</button>
    </form>
</div>

</body>
</html>