<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch Customer & Company Data
$stmt = $pdo->prepare("
    SELECT c.*, comp.business_name, comp.business_logo 
    FROM customers c 
    LEFT JOIN companies comp ON c.company_id = comp.id 
    WHERE c.id = ?
");
$stmt->execute([$user_id]);
$customer = $stmt->fetch();

$user_name = $customer['full_name'] ?? 'Customer';
$biz_name = $customer['business_name'] ?? 'LAUNDRY CARE';

// Image Paths
$profile_img = !empty($customer['profile_image']) ? 'uploads/profiles/'.$customer['profile_image'] : 'https://ui-avatars.com/api/?name='.urlencode($user_name).'&background=00f2fe&color=000';
$biz_logo = !empty($customer['business_logo']) ? 'uploads/logos/'.$customer['business_logo'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Profile | <?= htmlspecialchars($biz_name) ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    
    <style>
    /* 1. Fonts & Essentials */
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap');

    :root {
        --primary-neon: #ffffff;
        --secondary-neon: #ffffff;
        --bg-dark: #080808;
        --glass: rgba(255, 255, 255, 0.05);
        --glass-border: rgba(255, 255, 255, 0.1);
        --sidebar-width: 280px;
        --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
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
        color: #ffffff;
        margin: 0;
        overflow-x: hidden;
    }

    /* 2. Animations */
    @keyframes pulse-neon {
        0% { box-shadow: 0 0 5px rgba(0, 0, 0, 0.2); }
        50% { box-shadow: 0 0 20px rgba(255, 255, 255, 0.5); }
        100% { box-shadow: 0 0 5px rgba(0, 0, 0, 0.2); }
    }

    /* 3. Sidebar (Premium Style) */
    .sidebar {
        width: var(--sidebar-width);
        height: 100vh;
        background: rgba(10, 10, 10, 0.8);
        backdrop-filter: blur(30px);
        border-right: 1px solid var(--glass-border);
        position: fixed;
        left: 0; top: 0; z-index: 1100;
        display: flex;
        flex-direction: column;
        transition: var(--transition);
    }

    .sidebar-header { 
        padding: 40px 25px; 
        border-bottom: 1px solid var(--glass-border); 
    }

    .nav-link-custom {
        display: flex; 
        align-items: center; 
        padding: 14px 22px;
        color: rgba(255,255,255,0.5); 
        text-decoration: none;
        border-radius: 16px; 
        margin: 8px 18px; 
        transition: var(--transition);
        font-size: 14px; 
        font-weight: 600;
        border: 1px solid rgba(255, 255, 255, 0.03); 
        background: rgba(255, 255, 255, 0.02);
    }

    .nav-link-custom i { font-size: 18px; margin-right: 15px; }

    .nav-link-custom:hover, .nav-link-custom.active {
        background: rgba(255, 255, 255, 0.1);
        color: var(--primary-neon);
        border-color: rgba(255, 255, 255, 0.3);
        transform: translateX(8px);
    }

    /* 4. Main Content Area */
    .main-content {
        margin-left: var(--sidebar-width);
        padding: 50px;
        transition: var(--transition);
    }

    /* 5. Profile Card (The Masterpiece) */
    .profile-card {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.01) 100%);
        backdrop-filter: blur(40px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 45px;
        padding: 50px;
        box-shadow: 0 40px 100px rgba(0,0,0,0.5);
        position: relative;
        overflow: hidden;
    }

    .profile-card::before {
        content: '';
        position: absolute;
        top: -100px; right: -100px;
        width: 250px; height: 250px;
        background: var(--primary-neon);
        filter: blur(120px);
        opacity: 0.1;
    }

    .profile-img-lg {
        width: 130px;
        height: 130px;
        border-radius: 40px;
        object-fit: cover;
        border: 2px solid var(--primary-neon);
        padding: 6px;
        background: #000;
        animation: pulse-neon 4s infinite ease-in-out;
    }

    /* 6. Data Boxes */
    .data-box {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 24px;
        padding: 22px;
        margin-bottom: 15px;
        transition: var(--transition);
    }

    .data-box:hover {
        background: rgba(255, 255, 255, 0.06);
        border-color: var(--primary-neon);
        transform: translateY(-5px);
    }

    .label-text {
        font-size: 10px;
        font-weight: 800;
        color: var(--primary-neon);
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 8px;
        display: block;
    }

    .value-text {
        font-size: 16px;
        font-weight: 600;
        color: #fff;
    }

    /* 7. Action Button */
    .btn-action {
        background: #ffffff;
        color: #000;
        font-weight: 800;
        border-radius: 22px;
        padding: 18px;
        width: 100%;
        border: 1px solid #fff;
        transition: var(--transition);
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 13px;
    }

    .btn-action:hover {
        background: transparent;
        color: #fff;
        border-color: var(--primary-neon);
        box-shadow: 0 15px 30px rgba(0, 242, 254, 0.2);
    }

    /* 8. Mobile UI Optimization */
    .mobile-header {
        display: none;
        background: rgba(10, 10, 10, 0.9);
        backdrop-filter: blur(20px);
        padding: 18px 25px;
        border-bottom: 1px solid var(--glass-border);
        position: sticky; top: 0; z-index: 1000;
    }

    .mobile-nav {
        display: none;
        position: fixed; bottom: 0; left: 0; right: 0;
        height: 75px;
        background: rgba(10, 10, 10, 0.98);
        backdrop-filter: blur(30px);
        border-top: 1px solid var(--glass-border);
        border-radius: 30px 30px 0 0;
        justify-content: space-around;
        align-items: center;
        z-index: 2000;
    }

    .nav-item-m {
        text-align: center; color: rgba(255,255,255,0.4);
        text-decoration: none; font-size: 10px; font-weight: 700;
    }

    .nav-item-m.active { color: var(--primary-neon); }
    .nav-item-m i { font-size: 24px; display: block; margin-bottom: 3px; }

    /* 9. Scrollbar & Overlay */
    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-thumb { background: var(--glass-border); border-radius: 10px; }
    #overlay {
        display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.85); z-index: 1090; backdrop-filter: blur(10px);
    }
    #overlay.active { display: block; }

    /* 10. Responsive Breakpoints */
    @media (max-width: 991px) {
        .sidebar { transform: translateX(-100%); }
        .sidebar.active { transform: translateX(0); }
        .main-content { margin-left: 0; padding: 25px; padding-bottom: 110px; }
        .mobile-header { display: flex; align-items: center; justify-content: space-between; }
        .mobile-nav { display: flex; }
        .profile-card { padding: 30px; border-radius: 35px; }
    }
</style>
</head>
<body>

    <div id="overlay"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h5 class="fw-bold m-0" style="color: var(--primary-neon); letter-spacing: 1px;"><?= strtoupper($biz_name) ?></h5>
            <small class="text-white-50" style="font-size: 10px; letter-spacing: 2px;">CUSTOMER HUB</small>
        </div>
        <div class="mt-4">
            <a href="index.php" class="nav-link-custom active"><i class="bi bi-person-badge"></i> My Profile</a>
            <a href="orders.php" class="nav-link-custom"><i class="bi bi-receipt"></i> Orders</a>
            <a href="cu_analytics.php" class="nav-link-custom"><i class="bi bi-pie-chart"></i> Analytics</a>
            <a href="cu_settings.php" class="nav-link-custom"><i class="bi bi-shield-lock"></i> Security</a>
            <a href="logout.php" class="nav-link-custom text-danger mt-5"><i class="bi bi- power"></i> Sign Out</a>
        </div>
    </div>

    <div class="mobile-header">
        <button class="btn text-info fs-2 p-0" id="btnToggle"><i class="bi bi-list"></i></button>
        <span class="fw-bold"><?= strtoupper($biz_name) ?></span>
        <div style="width: 30px;"></div>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-12 col-xl-7">
                    
                    <div class="profile-card">
                        <div class="text-center mb-4">
                            <div class="position-relative d-inline-block">
                                <img src="<?= $profile_img ?>" class="profile-img-lg mb-3">
                                <span class="position-absolute bottom-0 end-0 bg-success border border-dark rounded-circle" style="width: 15px; height: 15px;"></span>
                            </div>
                            <h4 class="fw-bold mb-1"><?= htmlspecialchars($user_name) ?></h4>
                            <p class="text-info opacity-75 small">Member ID: #CUS-00<?= $user_id ?></p>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="data-box">
                                    <div class="label-text">Contact Number</div>
                                    <div class="value-text"><?= htmlspecialchars($customer['phone_number']) ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="data-box">
                                    <div class="label-text">NIC / Identity</div>
                                    <div class="value-text"><?= htmlspecialchars($customer['nic_number'] ?? 'N/A') ?></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="data-box">
                                    <div class="label-text">Email Address</div>
                                    <div class="value-text small opacity-75"><?= htmlspecialchars($customer['email_address']) ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="data-box">
                                    <div class="label-text">Account Status</div>
                                    <div class="value-text text-success">Verified</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="data-box">
                                    <div class="label-text">Member Since</div>
                                    <div class="value-text"><?= date('Y-m-d', strtotime($customer['created_at'])) ?></div>
                                </div>
                            </div>
                        </div>

                        <button onclick="location.href='cu_settings.php'" class="btn-action mt-4 shadow">
                            UPDATE PROFILE DETAILS
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="mobile-nav">
        <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
        <a href="customer_dashboard.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
        <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
    </div>

    <script>
        const btnToggle = document.getElementById('btnToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        btnToggle.addEventListener('click', () => {
            sidebar.classList.add('active');
            overlay.classList.add('active');
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    </script>
</body>
</html>