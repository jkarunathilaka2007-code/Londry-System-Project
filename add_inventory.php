<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$status = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['item_name'];
    $stock = $_POST['current_stock'];
    $price = $_POST['unit_price'];

    // Category සහ Unit නැතුව සරලව Insert කිරීම
    $stmt = $pdo->prepare("INSERT INTO inventory (item_name, current_stock, unit_price) VALUES (?, ?, ?)");
    if ($stmt->execute([$name, $stock, $price])) {
        $status = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Stock | Fabricare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    
    <style>
    :root {
        --bg-dark: #080808;
        --glass: rgba(255, 255, 255, 0.04);
        --glass-border: rgba(255, 255, 255, 0.1);
        --accent: #ffffff;
    }

    body {
        background-color: var(--bg-dark);
        background-image: linear-gradient(rgba(8, 8, 8, 0.9), rgba(8, 8, 8, 0.9)), 
                          url('uploads/resources/bg2.jpg');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        min-height: 100vh;
        font-family: 'Plus Jakarta Sans', sans-serif;
        color: #ffffff !important;
        margin: 0;
    }

    /* --- TOP NAV --- */
    .top-nav {
        background: rgba(10, 10, 10, 0.85);
        backdrop-filter: blur(30px);
        border-bottom: 1px solid var(--glass-border);
        padding: 10px 0;
        position: sticky; top: 0; z-index: 1000;
    }

    .top-nav h5 {
        color: #ffffff !important;
        font-weight: 700 !important;
        margin: 0;
        font-size: 1rem;
        -webkit-text-fill-color: #ffffff !important;
        opacity: 1 !important;
    }

    .back-btn {
        width: 36px; height: 36px; border-radius: 10px;
        background: var(--glass); border: 1px solid var(--glass-border);
        display: flex; align-items: center; justify-content: center;
        color: #fff !important; text-decoration: none;
    }

    /* --- GLASS FORM CARD --- */
    .glass-card {
        background: var(--glass);
        backdrop-filter: blur(25px);
        border: 1px solid var(--glass-border);
        border-radius: 28px;
        padding: 30px;
        max-width: 450px;
        margin: 30px auto;
        box-shadow: 0 20px 40px rgba(0,0,0,0.4);
    }

    .form-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255,255,255,0.5);
        font-weight: 700;
        margin-bottom: 8px;
    }

    .form-control {
        background: rgba(255,255,255,0.05);
        border: 1px solid var(--glass-border);
        color: #fff !important;
        border-radius: 15px;
        padding: 14px 18px;
        font-weight: 500;
        transition: 0.3s;
    }

    .form-control:focus {
        background: rgba(255,255,255,0.08);
        border-color: rgba(255, 255, 255, 0.3);
        box-shadow: 0 0 15px rgba(255,255,255,0.05);
        outline: none;
    }

    .input-group-text {
        background: rgba(255,255,255,0.03);
        border: 1px solid var(--glass-border);
        color: rgba(255,255,255,0.6);
        border-radius: 15px 0 0 15px !important;
    }

    .input-group .form-control {
        border-radius: 0 15px 15px 0 !important;
    }

    /* --- SUBMIT BUTTON --- */
    .btn-submit {
        background: #fff;
        border: none;
        color: #000 !important;
        font-weight: 800;
        border-radius: 18px;
        padding: 16px;
        width: 100%;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 1px;
        margin-top: 10px;
        transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .btn-submit:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(255, 255, 255, 0.2);
        background: #f8f8f8;
    }

    /* --- MOBILE STYLES --- */
    @media (max-width: 991px) {
        .glass-card { margin: 20px; padding: 25px; border-radius: 24px; }
        
        .mobile-nav {
            display: flex !important; 
            position: fixed; bottom: 0; left: 0; right: 0;
            height: 65px; background: rgba(15, 15, 15, 0.98);
            backdrop-filter: blur(25px); 
            border-radius: 20px 20px 0 0; 
            border-top: 1px solid var(--glass-border); 
            justify-content: space-around; align-items: center; 
            z-index: 1050;
        }
    }

    .nav-item-m { text-align: center; color: rgba(255,255,255,0.3); text-decoration: none; font-size: 9px; font-weight: 600; flex: 1; }
    .nav-item-m.active { color: #fff; }
    .nav-item-m i { font-size: 20px; display: block; margin-bottom: 2px; }

    @media (min-width: 992px) { .mobile-nav { display: none !important; } }
</style>
</head>
<body>

    <nav class="top-nav">
        <div class="container d-flex align-items-center">
            <a href="inventory.php" class="back-btn"><i class="bi bi-chevron-left"></i></a>
            <div class="ms-3">
                <h5 class="m-0 fw-bold" style="background: linear-gradient(to right, #fff, var(--primary-neon)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Staff Onboarding</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Admin Control Panel</small>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if($status == "success"): ?>
            <div class="alert alert-success bg-success bg-opacity-10 text-success border-0 rounded-4 text-center mt-4 mx-auto" style="max-width: 450px;">
                <i class="bi bi-check-circle-fill me-2"></i> Stock item registered!
            </div>
        <?php endif; ?>

        <div class="glass-card">
            <form method="POST">
                <div class="mb-4">
                    <label class="small text-white-50 mb-2">ITEM NAME</label>
                    <input type="text" name="item_name" class="form-control" placeholder="e.g. Gas Tank, Soap Box" required>
                </div>

                <div class="mb-4">
                    <label class="small text-white-50 mb-2">QUANTITY IN STOCK</label>
                    <input type="number" name="current_stock" class="form-control" placeholder="e.g. 10" required>
                </div>

                <div class="mb-5">
                    <label class="small text-white-50 mb-2">PRICE PER 1 QUANTITY (LKR)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0" style="border: 1px solid var(--glass-border); color: var(--primary-neon);">Rs.</span>
                        <input type="number" step="0.01" name="unit_price" class="form-control border-start-0" placeholder="0.00" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-submit">Save to Inventory</button>
            </form>
        </div>
    </div>
<div class="mobile-nav">
        <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
        <a href="admin_dashboard.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
        <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
</div>
</body>
</html>