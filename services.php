<?php
session_start();
require 'config.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// දත්ත ලබාගැනීම
$query = "SELECT * FROM services ORDER BY id DESC";
$stmt = $pdo->query($query);
$services = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Service Registry | Fabricare</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    
    <style>
    :root {
        --bg-dark: #080808;
        --glass: rgba(255, 255, 255, 0.05);
        --glass-border: rgba(255, 255, 255, 0.12);
    }

    body {
        background-color: var(--bg-dark);
        background-image: linear-gradient(rgba(8, 8, 8, 0.85), rgba(8, 8, 8, 0.85)), 
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

    /* --- TOP NAV (FIXED TITLE) --- */
    .top-nav {
        background: rgba(10, 10, 10, 0.8);
        backdrop-filter: blur(30px);
        border-bottom: 1px solid var(--glass-border);
        padding: 12px 0;
        position: sticky; top: 0; z-index: 1000;
    }

    .top-nav h5 {
        color: #ffffff !important;
        font-weight: 700 !important;
        margin: 0;
        display: block !important;
        background: none !important;
        -webkit-text-fill-color: initial !important;
    }

    .back-btn {
        width: 40px; height: 40px; border-radius: 12px;
        background: var(--glass); border: 1px solid var(--glass-border);
        display: flex; align-items: center; justify-content: center;
        color: #fff !important; text-decoration: none;
    }

    /* --- PRODUCT GRID --- */
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
        margin-top: 10px;
    }

    .product-card {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(20px);
        border: 1px solid var(--glass-border);
        border-radius: 28px;
        overflow: hidden;
        position: relative;
        transition: 0.3s;
    }

    /* --- BUTTONS FIX (EDIT/DELETE) --- */
    .card-actions {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(10px);
        padding: 8px 12px;
        border-radius: 15px;
        border: 1px solid var(--glass-border);
        z-index: 10;
        display: flex;
        gap: 10px;
        opacity: 1 !important; /* හැමවෙලේම පේන්න opacity 1 කළා */
    }

    .card-action-btn {
        font-size: 1.1rem;
        transition: 0.2s;
        text-decoration: none;
    }

    .edit-btn { color: #ffffff !important; opacity: 0.8; }
    .delete-btn { color: #ff4d4d !important; opacity: 0.8; }
    .card-action-btn:hover { transform: scale(1.2); opacity: 1; }

    /* --- CARD CONTENT --- */
    .card-img-container {
        width: 100%; height: 220px;
        background: rgba(0,0,0,0.2);
        display: flex; align-items: center; justify-content: center;
        padding: 15px;
    }
    .product-card-img { width: 100%; height: 100%; object-fit: contain; }
    .card-body-custom { padding: 20px; }
    .item-name { font-weight: 700; color: #fff; margin-bottom: 5px; }
    .item-price { font-size: 1.2rem; font-weight: 800; color: #fff; }

    /* --- MOBILE NAV (TOP ROUNDED ONLY) --- */
    .mobile-nav { display: none !important; }

    @media (max-width: 991px) {
        .main-container { padding-bottom: 110px; }
        .mobile-nav {
            display: flex !important; 
            position: fixed; bottom: 0px; left: 0px; right: 0px;
            height: 70px; background: rgba(15, 15, 15, 0.96);
            backdrop-filter: blur(25px); 
            border-radius: 25px 25px 0 0; 
            border-top: 1px solid var(--glass-border); 
            justify-content: space-around; 
            align-items: center; z-index: 1050;
        }
        .product-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
        .card-img-container { height: 160px; }
    }

    .nav-item-m { text-align: center; color: rgba(255,255,255,0.3); text-decoration: none; font-size: 10px; font-weight: 700; flex: 1; }
    .nav-item-m.active { color: #fff; }
    .nav-item-m i { font-size: 22px; display: block; }
</style>
</head>
<body>

<nav class="top-nav">
    <div class="container d-flex align-items-center justify-content-between">
        
        <div class="d-flex align-items-center">
            <a href="admin_dashboard.php" class="back-btn"><i class="bi bi-chevron-left"></i></a>
            <div class="ms-3">
                <h5 class="m-0 fw-bold" style="background: linear-gradient(to right, #fff, var(--primary-neon)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Staff Onboarding</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Admin Control Panel</small>
            </div>
        </div>

        <div class="right-actions">
            <a href="add_services.php" class="nav-link-btn text-white" style="font-size: 1.5rem;">
                <i class="bi bi-plus-circle-fill"></i>
            </a>
        </div>

    </div>
</nav>

    <div class="container main-container mt-4">
        
        <?php if (empty($services)): ?>
            <div class="text-center py-5 mt-5 text-white-50">
                <i class="bi bi-tag display-1 opacity-25"></i>
                <h5 class="mt-4">No services in the registry.</h5>
                <a href="add_services.php" class="btn btn-outline-info mt-3 rounded-pill px-4">Initialize First Node</a>
            </div>
        <?php else: ?>
            
            <div class="product-grid">
                <?php foreach ($services as $item): ?>
                    
                    <div class="product-card">
                        
                        <div class="card-actions">
                            <a href="edit_service.php?id=<?= $item['id'] ?>" class="card-action-btn edit-btn"><i class="bi bi-pencil-square"></i></a>
                            <a href="delete_service.php?id=<?= $item['id'] ?>" class="card-action-btn delete-btn" onclick="return confirm('Remove Node Configuration?')">
                                <i class="bi bi-trash3-fill"></i>
                            </a>
                        </div>
                        
                        <div class="card-img-container">
                            <?php if(!empty($item['service_image'])): ?>
                                <img src="uploads/services/<?= htmlspecialchars($item['service_image']) ?>" class="product-card-img" alt="<?= htmlspecialchars($item['service_name']) ?>">
                            <?php else: ?>
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-dark">
                                    <i class="bi bi-image text-white-50 fs-1"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body-custom">
                            <div>
                                <h6 class="item-name"><?= htmlspecialchars($item['service_name']) ?></h6>
                                <p class="item-lead-time"><i class="bi bi-clock-history me-1"></i> Lead Time: <?= htmlspecialchars($item['lead_time']) ?></p>
                            </div>
                            <div class="item-price"><small>LKR</small> <?= number_format($item['price'], 2) ?></div>
                        </div>

                    </div> <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>

    <div class="mobile-nav">
        <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
        <a href="admin_dashboard.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
        <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
    </div>

</body>
</html>