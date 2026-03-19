<?php
session_start();
require 'config.php';

// Admin Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Stock අඩු ඒවා මුලට එන විදිහට Sort කළා
$stmt = $pdo->query("SELECT * FROM inventory ORDER BY current_stock ASC");
$inventory = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Inventory | Fabricare</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;500;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-dark: #080808;
            --glass: rgba(255, 255, 255, 0.04);
            --glass-border: rgba(255, 255, 255, 0.1);
            --accent-red: #ff3b30;
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
            overflow-x: hidden;
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

        /* --- INVENTORY GRID --- */
        .main-container { padding: 15px 8px 110px 8px; }

        .inv-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 18px;
            padding: 12px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            transition: 0.3s ease;
        }

        /* LOW STOCK HIGHLIGHT */
        .low-stock-card {
            background: rgba(255, 59, 48, 0.08) !important;
            border: 1px solid rgba(255, 59, 48, 0.3) !important;
        }

        .low-stock-badge {
            background: var(--accent-red);
            color: #fff;
            font-size: 0.5rem;
            padding: 2px 6px;
            border-radius: 6px;
            font-weight: 800;
            text-transform: uppercase;
            position: absolute;
            top: 8px; right: 8px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }

        .item-title {
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 2px;
            color: #fff;
            display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;
        }

        .stock-label {
            font-size: 0.6rem;
            text-transform: uppercase;
            color: rgba(255,255,255,0.4);
            margin-bottom: 12px;
            display: block;
        }

        .critical-stock { color: var(--accent-red) !important; font-weight: 800; }

        .price-container {
            border-top: 1px solid rgba(255,255,255,0.06);
            padding-top: 10px;
            display: flex; justify-content: space-between; align-items: center;
        }

        .price-tag { font-size: 0.95rem; font-weight: 800; color: #fff; }
        .price-tag small { font-size: 0.65rem; opacity: 0.5; margin-right: 1px; }

        .edit-link { color: rgba(255,255,255,0.4) !important; font-size: 1.1rem; }

        /* --- MOBILE GRID (2 COLS) --- */
        @media (max-width: 991px) {
            .row { margin-left: -5px; margin-right: -5px; }
            .row > * { padding-left: 5px; padding-right: 5px; flex: 0 0 50%; max-width: 50%; }
        }

        /* --- MOBILE NAV --- */
        .mobile-nav {
            position: fixed; bottom: 0; left: 0; right: 0;
            height: 65px; background: rgba(15, 15, 15, 0.98);
            backdrop-filter: blur(25px); border-radius: 20px 20px 0 0;
            border-top: 1px solid var(--glass-border);
            display: flex; justify-content: space-around; align-items: center; z-index: 1050;
        }

        .nav-item-m { text-align: center; color: rgba(255,255,255,0.3); text-decoration: none; font-size: 9px; font-weight: 600; flex: 1; }
        .nav-item-m.active { color: #fff; }
        .nav-item-m i { font-size: 20px; display: block; margin-bottom: 2px; }

        @media (min-width: 992px) { .mobile-nav { display: none !important; } }
    </style>
</head>
<body>

<nav class="top-nav">
    <div class="container d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <a href="admin_dashboard.php" class="back-btn"><i class="bi bi-chevron-left"></i></a>
            <div class="ms-3">
                <h5>Staff Onboarding</h5>
                <small class="text-white-50" style="font-size: 0.6rem;">Inventory Manager</small>
            </div>
        </div>
        <a href="add_inventory.php" class="text-white fs-4"><i class="bi bi-plus-circle-fill"></i></a>
    </div>
</nav>

<div class="container main-container">
    <div class="row g-2">
        <?php foreach ($inventory as $item): 
            $isLow = ($item['current_stock'] <= 2); // Stock 5 ට අඩු නම් Alert
        ?>
        <div class="col-6">
            <div class="inv-card <?= $isLow ? 'low-stock-card' : '' ?>">
                
                <?php if($isLow): ?>
                    <span class="low-stock-badge">Low</span>
                <?php endif; ?>

                <div class="item-info">
                    <div class="item-title"><?= htmlspecialchars($item['item_name']) ?></div>
                    <span class="stock-label">
                        Stock: <span class="<?= $isLow ? 'critical-stock' : 'text-white' ?>">
                            <?= (int)$item['current_stock'] ?>
                        </span>
                    </span>
                </div>

                <div class="price-container">
                    <div class="price-tag"><small>Rs.</small><?= number_format($item['unit_price'], 0) ?></div>
                    <a href="edit_inventory.php?id=<?= $item['id'] ?>" class="edit-link">
                        <i class="bi bi-pencil-square"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="mobile-nav">
    <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
    <a href="admin_dashboard.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
    <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
</div>

</body>
</html>