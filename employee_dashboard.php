<?php
session_start();
require 'config.php';

// සේවකයා ලොග් වෙලා නැත්නම් ලොගින් පේජ් එකට යවනවා
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// ලොග් වී සිටින සේවකයාගේ විස්තර පමණක් ඩේටාබේස් එකෙන් ගන්නවා
$emp_stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$emp_stmt->execute([$current_user_id]);
$me = $emp_stmt->fetch();

if (!$me) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Employee Profile | Fabricare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
    :root {
        --bg-dark: #080808;
        --glass: rgba(255, 255, 255, 0.05);
        --glass-border: rgba(255, 255, 255, 0.1);
        --accent-red: #ff3b30;
        --primary-white: #ffffff;
        --sidebar-width: 260px;
    }

    body {
        background-color: var(--bg-dark);
        background-image: linear-gradient(rgba(0, 0, 0, 0.88), rgba(0, 0, 0, 0.88)), 
                          url('uploads/resources/bg2.jpg');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        min-height: 100vh;
        font-family: 'Plus Jakarta Sans', sans-serif;
        color: var(--primary-white) !important;
        margin: 0;
        overflow-x: hidden;
    }

    /* --- SIDEBAR STYLE & HIDE SCROLLBAR --- */
    .sidebar {
        width: var(--sidebar-width); height: 100vh; position: fixed;
        left: 0; top: 0; background: rgba(10, 10, 10, 0.98);
        border-right: 1px solid var(--glass-border); backdrop-filter: blur(30px);
        padding: 30px 15px; z-index: 1050; transition: all 0.4s ease;
        display: flex; flex-direction: column;
    }

    .sidebar nav {
        flex: 1;
        overflow-y: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    .sidebar nav::-webkit-scrollbar { display: none; }

    /* --- SIDEBAR BUTTONS WITH CONSTANT STROKE --- */
    .nav-link-custom {
        display: flex; align-items: center; padding: 12px 18px;
        color: rgba(255,255,255,0.5); text-decoration: none;
        border-radius: 16px; margin-bottom: 10px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-weight: 600; font-size: 14px;
        
        /* සැමවිටම පෙනෙන Stroke එක (Low Opacity) */
        border: 1px solid rgba(255, 255, 255, 0.12); 
        background: rgba(255, 255, 255, 0.02);
    }
    
    .nav-link-custom i { font-size: 1.25rem; margin-right: 15px; transition: 0.3s; }

    /* Hover අවස්ථාවේදී Stroke එක තදින් පෙන්වීම */
    .nav-link-custom:hover {
        background: rgba(255, 255, 255, 0.08);
        color: var(--primary-white);
        border: 1px solid rgba(255, 255, 255, 0.4); 
        transform: translateY(-2px);
    }

    /* Active (දැනට ඉන්න Page එකේ) Button එකේ ස්ටයිල් එක */
    .nav-link-custom.active {
        background: rgba(255, 255, 255, 0.1);
        color: var(--primary-white);
        border: 1px solid rgba(255, 255, 255, 0.7); /* වඩාත් පැහැදිලි Stroke එකක් */
        box-shadow: 0 4px 15px rgba(255, 255, 255, 0.05);
    }

    .nav-link-custom.text-danger {
        border-color: rgba(255, 59, 48, 0.15);
    }
    .nav-link-custom.text-danger:hover {
        border-color: rgba(255, 59, 48, 0.5);
        background: rgba(255, 59, 48, 0.05);
    }

    /* --- MAIN CONTENT & PROFILE CARD --- */
    .main-content { margin-left: var(--sidebar-width); padding: 40px; transition: 0.4s; }

    .profile-card {
        background: var(--glass); backdrop-filter: blur(25px);
        border: 1px solid var(--glass-border);
        border-radius: 30px; padding: 40px; text-align: center;
        box-shadow: 0 20px 40px rgba(0,0,0,0.4);
    }

    .main-profile-img {
        width: 135px; height: 135px; border-radius: 24px; 
        object-fit: cover; border: 1px solid var(--glass-border);
        padding: 6px; margin-bottom: 20px; background: rgba(255,255,255,0.03);
    }

    .info-box {
        background: var(--glass); border: 1px solid var(--glass-border);
        border-radius: 20px; padding: 20px; text-align: left; transition: 0.3s;
        height: 100%;
    }
    .info-box:hover { border-color: rgba(255,255,255,0.3); background: rgba(255,255,255,0.08); }

    .info-label {
        font-size: 0.65rem; text-transform: uppercase;
        color: rgba(255,255,255,0.4); font-weight: 800;
        letter-spacing: 1.2px; margin-bottom: 6px; display: block;
    }
    .info-value { font-weight: 700; font-size: 1rem; color: #fff; }

    .emergency-card { border-left: 4px solid var(--accent-red) !important; }
    .emergency-card .info-label { color: var(--accent-red); }

    /* --- MOBILE RESPONSIVE --- */
    #menu-toggle {
        display: none; position: fixed; top: 15px; left: 15px;
        background: var(--glass); border: 1px solid var(--glass-border); 
        backdrop-filter: blur(10px); width: 45px; height: 45px;
        border-radius: 12px; color: #fff; z-index: 1100; align-items: center; justify-content: center;
    }

    @media (max-width: 991px) {
        .sidebar { transform: translateX(-100%); width: 280px; }
        .sidebar.active { transform: translateX(0); }
        .main-content { margin-left: 0; padding: 85px 15px 100px 15px; }
        #menu-toggle { display: flex; }
        .grid-col-mobile { flex: 0 0 50%; max-width: 50%; }
    }

    .bottom-nav { 
        position: fixed; bottom: 0; left: 0; right: 0; height: 65px; 
        background: rgba(10, 10, 10, 0.98); border-top: 1px solid var(--glass-border); 
        display: none; justify-content: space-around; align-items: center; z-index: 1000;
        backdrop-filter: blur(25px); border-radius: 25px 25px 0 0;
    }
    .nav-item-m { text-align: center; color: rgba(255,255,255,0.3); text-decoration: none; font-size: 9px; font-weight: 600; flex: 1; }
    .nav-item-m.active { color: var(--primary-white); border-top: 2px solid var(--primary-white); padding-top: 5px; }
    .nav-item-m i { font-size: 24px; display: block; margin-bottom: 2px; }
    
    @media (max-width: 991px) { .bottom-nav { display: flex; } }
</style>
</head>
<body>

    <button id="menu-toggle"><i class="bi bi-list"></i></button>

    <div class="sidebar" id="sidebar">
        <div class="mb-5 px-3">
            <h4 class="fw-bold m-0" style="letter-spacing: 1px;">FABRI<span style="color: var(--primary-neon);">CARE</span></h4>
            <small class="text-white opacity-50 text-uppercase fw-bold" style="font-size: 9px; letter-spacing: 1px;">Workshop Terminal</small>
        </div>

        <nav style="flex: 1; overflow-y: auto;">
            <a href="index.php" class="nav-link-custom "><i class="bi bi-person-circle"></i> Dashboard</a>
            <a href="inventory_usage.php" class="nav-link-custom"><i class="bi bi-archive"></i> Inventory</a>
            <a href="attendance.php" class="nav-link-custom"><i class="bi bi-calendar-event"></i> Attendance</a>
            <a href="all_orders.php" class="nav-link-custom"><i class="bi bi-briefcase"></i> My Works</a>
            <a href="settle_order.php" class="nav-link-custom"><i class="bi bi-receipt"></i> Settle Order</a>
            <a href="emp_analytics.php" class="nav-link-custom"><i class="bi bi-graph-up"></i> Analytics</a>
            <a href="emp_salary.php" class="nav-link-custom"><i class="bi bi-cash"></i> Salary</a>
            <a href="edit_emp_data.php" class="nav-link-custom"><i class="bi bi-gear"></i> Settings</a>
            
            <div class="mt-4 pt-4 border-top border-white border-opacity-10">
                <a href="logout.php" class="nav-link-custom text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a>
            </div>
        </nav>
    </div>

    <div class="main-content">
        <div class="container" style="max-width: 800px;">
            
            <div class="profile-card">
                <?php 
                    $profile_path = 'uploads/profiles/' . $me['profile_image'];
                    $img_src = (!empty($me['profile_image']) && file_exists($profile_path)) 
                               ? $profile_path 
                               : 'https://ui-avatars.com/api/?name='.urlencode($me['full_name']).'&background=00f2fe&color=000&size=200';
                ?>
                <img src="<?= $img_src ?>" class="main-profile-img">
                
                <h3 class="fw-bold mb-1"><?= htmlspecialchars($me['full_name']) ?></h3>
                <div class="text-info text-uppercase small fw-bold mb-4" style="letter-spacing: 2px; font-size: 11px;">
                    <?= htmlspecialchars($me['designation']) ?>
                </div>

                <div class="row g-2">
                    <div class="col-md-6 grid-col-mobile">
                        <div class="info-box">
                            <span class="info-label">NIC Number</span>
                            <span class="info-value"><?= htmlspecialchars($me['nic_number']) ?></span>
                        </div>
                    </div>
                    <div class="col-md-6 grid-col-mobile">
                        <div class="info-box">
                            <span class="info-label">Contact Phone</span>
                            <span class="info-value"><?= htmlspecialchars($me['phone']) ?></span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="info-box">
                            <span class="info-label">Official Email</span>
                            <span class="info-value text-break"><?= htmlspecialchars($me['email']) ?></span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="info-box" style="border-left: 3px solid var(--accent-red);">
                            <span class="info-label" style="color: var(--accent-red);">Emergency Contact</span>
                            <span class="info-value" style="color: var(--accent-red);"><?= htmlspecialchars($me['emergency_contact']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-top border-white border-opacity-10 d-flex justify-content-between align-items-center">
                    <div class="small opacity-50 fw-bold" style="font-size: 10px;">SINCE <?= strtoupper(date('M Y', strtotime($me['created_at']))) ?></div>
                    <div class="badge rounded-pill px-3 py-2" style="background: rgba(0, 242, 254, 0.1); color: var(--primary-neon); border: 1px solid rgba(0, 242, 254, 0.2); font-size: 10px;">
                        <i class="bi bi-patch-check-fill me-1"></i> <?= strtoupper($me['status']) ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <nav class="bottom-nav">
      <a href="index.php" class="nav-item-m active"><i class="bi bi-house"></i><span>Home</span></a>
      <a href="employee_dashboard.php" class="nav-item-m"><i class="bi bi-person"></i><span>Profile</span></a>
      <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
    </nav>

    <script>
        const toggleBtn = document.getElementById('menu-toggle');
        const sidebar = document.getElementById('sidebar');

        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 991 && sidebar.classList.contains('active') && !sidebar.contains(e.target) && e.target !== toggleBtn) {
                sidebar.classList.remove('active');
            }
        });
    </script>

</body>
</html>