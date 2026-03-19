<?php
session_start();
require 'config.php';

// සෙෂන් එක පරීක්ෂා කිරීම
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];
$users = [];

// 1. ROLE-BASED PERMISSION LOGIC
// එක් එක් user ට පෙනිය යුතු අනිත් අය තීරණය කිරීම
try {
    if ($current_user_role === 'customer') {
        // Customer ට පේන්නේ: Admin සහ Employees පමණි
        $query = "SELECT id, gmail as name, 'admin' as role FROM system_admin
                  UNION
                  SELECT id, full_name as name, 'employee' as role FROM employees
                  ORDER BY name ASC";
        $users = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($current_user_role === 'employee') {
        // Employee ට පේන්නේ: Admin සහ Customers පමණි
        $query = "SELECT id, gmail as name, 'admin' as role FROM system_admin
                  UNION
                  SELECT id, full_name as name, 'customer' as role FROM customers
                  ORDER BY name ASC";
        $users = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($current_user_role === 'admin') {
        // Admin ට පේන්නේ: Employees සහ Customers පමණි
        $query = "SELECT id, full_name as name, 'employee' as role FROM employees
                  UNION
                  SELECT id, full_name as name, 'customer' as role FROM customers
                  ORDER BY name ASC";
        $users = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// 2. FORM SUBMISSION LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['receivers'])) {
    $receivers_raw = $_POST['receivers']; 
    $message = $_POST['message'];
    $media_url = "";
    $media_type = "text";

    // Media Upload (Image/Video)
    if (!empty($_FILES['media']['name'])) {
        $target_dir = "uploads/notifications/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_name = time() . "_" . basename($_FILES["media"]["name"]);
        $target_file = $target_dir . $file_name;
        $file_ext = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (move_uploaded_file($_FILES["media"]["tmp_name"], $target_file)) {
            $media_url = $file_name;
            $media_type = in_array($file_ext, ['mp4', 'mov', 'avi', 'mkv']) ? 'video' : 'image';
        }
    }

    // සෑම Receiver කෙනෙකුටම වෙන් වෙන් වශයෙන් පණිවිඩය ඇතුළත් කිරීම
    $stmt = $pdo->prepare("INSERT INTO notifications (sender_id, sender_role, receiver_id, receiver_role, message_text, media_url, media_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($receivers_raw as $raw_data) {
        list($r_id, $r_role) = explode('|', $raw_data);
        $stmt->execute([$current_user_id, $current_user_role, $r_id, $r_role, $message, $media_url, $media_type]);
    }
    
    header("Location: notifications.php?status=sent");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compose | Fabricare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --bg: #080808;
            --accent: #ffffff;
            --glass: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body {
            background: var(--bg);
            background-image: linear-gradient(rgba(8, 8, 8, 0.85), rgba(8, 8, 8, 0.75)), url('uploads/resources/bg2.jpg');
            background-size: cover;
            color: #fff;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .glass-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 30px;
        }

        .receiver-container {
            max-height: 250px;
            overflow-y: auto;
            background: rgba(0,0,0,0.4);
            border-radius: 15px;
            border: 1px solid var(--glass-border);
        }

        .user-option {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            cursor: pointer;
            transition: 0.3s;
        }

        .user-option:hover { background: rgba(255,255,255,0.05); }

        .user-option input[type="checkbox"] {
            width: 20px; height: 20px;
            margin-right: 15px;
            accent-color: var(--accent);
        }

        .role-badge {
            margin-left: auto;
            font-size: 0.6rem;
            letter-spacing: 1px;
            padding: 4px 10px;
            border-radius: 50px;
            border: 1px solid rgba(255,255,255,0.2);
            text-transform: uppercase;
        }

        .form-control {
            background: rgba(0,0,0,0.4);
            border: 1px solid var(--glass-border);
            color: #fff;
            border-radius: 12px;
            padding: 12px;
        }

        .form-control:focus {
            background: rgba(0,0,0,0.6);
            border-color: var(--accent);
            color: #fff;
            box-shadow: none;
        }

        .btn-send {
            background: var(--accent);
            color: #000;
            font-weight: 800;
            border-radius: 50px;
            padding: 15px;
            transition: 0.4s;
        }

        .btn-send:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 255, 255, 0.3);
            background: #ffffff00;
        }

        /* Scrollbar styling */
        .receiver-container::-webkit-scrollbar { width: 5px; }
        .receiver-container::-webkit-scrollbar-thumb { background: var(--accent); border-radius: 10px; }
            /* --- TOP NAV (FIXED) --- */
.top-nav {
    background: rgba(10, 10, 10, 0.9) !important;
    backdrop-filter: blur(30px);
    border-bottom: 1px solid var(--glass-border);
    padding: 10px 0;
    position: sticky; top: 0; z-index: 1000;
}

/* Titles and Text inside Top Nav */
.top-nav h5, 
.top-nav .ms-3 h5 {
    color: #ffffff !important;
    font-weight: 700 !important;
    opacity: 1 !important;
    margin: 0;
    -webkit-text-fill-color: #ffffff !important; /* Force gradient override */
}

.top-nav small, 
.top-nav .text-white-50 {
    color: rgba(255, 255, 255, 0.7) !important; /* Making "Workshop Terminal" brighter */
    font-weight: 500;
}
/* --- MOBILE NAV --- */
    .mobile-nav {
        position: fixed; bottom: 0; left: 0; right: 0;
        height: 65px; background: rgba(10, 10, 10, 0.98);
        backdrop-filter: blur(25px); border-radius: 20px 20px 0 0;
        border-top: 1px solid var(--glass-border);
        display: flex; justify-content: space-around; align-items: center; z-index: 1050;
    }

    @media (min-width: 992px) { .mobile-nav { display: none !important; } }

    .nav-item-m { text-align: center; color: rgba(255,255,255,0.3); text-decoration: none; font-size: 9px; flex: 1; }
    .nav-item-m.active { color: #fff; }
    .nav-item-m i { font-size: 20px; display: block; }
    .back-btn {
            width: 36px; height: 36px; border-radius: 10px;
            background: var(--glass); border: 1px solid var(--glass-border);
            display: flex; align-items: center; justify-content: center;
            color: #fff !important; text-decoration: none;
        }

    </style>
</head>

<nav class="top-nav">
    <div class="container d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <a href="notifications.php" class="back-btn"><i class="bi bi-chevron-left"></i></a>
            <div class="ms-3">
                <h5 class="m-0 fw-bold" style="background: linear-gradient(to right, #fff, var(--primary-neon)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Attendance</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Workshop Terminal</small>
            </div>
        </div>
    </div>
</nav>

<body class="py-5">

<div class="container" style="max-width: 750px;">
    

    <div class="glass-card shadow-lg">
        <form action="" method="POST" enctype="multipart/form-data">
            
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-end mb-2">
                    <label class="form-label small fw-bold opacity-50 m-0">RECIPIENTS</label>
                    <button type="button" class="btn btn-link btn-sm p-0 text-info text-decoration-none" onclick="selectAll()">Select All</button>
                </div>
                <div class="receiver-container">
                    <?php if(empty($users)): ?>
                        <div class="p-4 text-center opacity-50">No available contacts.</div>
                    <?php else: ?>
                        <?php foreach($users as $user): ?>
                            <label class="user-option">
                                <input type="checkbox" name="receivers[]" value="<?= $user['id'] ?>|<?= $user['role'] ?>">
                                <div>
                                    <div class="fw-bold small"><?= strtoupper($user['name']) ?></div>
                                    <div class="opacity-40" style="font-size: 0.7rem;">Verified Identity</div>
                                </div>
                                <span class="role-badge"><?= $user['role'] ?></span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold opacity-50">MESSAGE</label>
                <textarea name="message" class="form-control" rows="4" placeholder="Enter your message details..." required></textarea>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold opacity-50">ATTACHMENT (IMAGE / VIDEO)</label>
                <input type="file" name="media" class="form-control" accept="image/*,video/*">
                <small class="opacity-40 mt-1 d-block">Supported formats: JPG, PNG, MP4, MKV</small>
            </div>

            <button type="submit" class="btn btn-send w-100 mt-2">
                SEND MESSAGE <i class="bi bi-send-fill ms-2"></i>
            </button>
        </form>
    </div>
</div>

<div class="mobile-nav">
    <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
    <a href="index.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
    <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
</div>

<script>
    function selectAll() {
        const checkboxes = document.getElementsByName('receivers[]');
        for(let i=0; i<checkboxes.length; i++) {
            checkboxes[i].checked = true;
        }
    }
</script>

</body>
</html>