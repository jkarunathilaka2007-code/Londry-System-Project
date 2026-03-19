<?php
session_start();
require 'config.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Stats Fetching
$pending = $pdo->query("SELECT COUNT(*) FROM customers WHERE status = 'Pending'")->fetchColumn();
$staff = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$companies = $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn();

// --- 01. Current Month Total Settlements ---
$currentMonth = date('Y-m');
$monthly_revenue = $pdo->prepare("SELECT SUM(total_amount) FROM settlements WHERE DATE_FORMAT(settled_at, '%Y-%m') = ?");
$monthly_revenue->execute([$currentMonth]);
$total_revenue = $monthly_revenue->fetchColumn() ?: 0;

// --- 02. Estimated Staff Payments (Current Month) ---
// මෙතනදී අපි සරලව Basic Salary එකයි Commissions වල එකතුවයි ගන්නවා
$total_staff_costs = $pdo->query("SELECT SUM(basic_salary + service_commissions + bonus) FROM salary_structures")->fetchColumn() ?: 0;

// --- 03. Calculated Profit ---
$total_profit = $total_revenue - $total_staff_costs;
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Dashboard | Laundry Care</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
    --bg-dark: #080808;
    --glass: rgba(255, 255, 255, 0.05);
    --glass-border: rgba(255, 255, 255, 0.12);
    --sidebar-width: 280px;
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
    color: #ffffff;
    margin: 0;
    overflow-x: hidden;
}

/* --- SIDEBAR CUSTOMIZATION --- */
/* --- SIDEBAR UPDATED (SCROLL FIX) --- */
.sidebar {
    width: var(--sidebar-width);
    height: 100vh;
    background: rgba(10, 10, 10, 0.85); /* ටිකක් තද කළා */
    backdrop-filter: blur(30px);
    border-right: 1px solid var(--glass-border);
    position: fixed;
    left: 0; top: 0; z-index: 1100;
    display: flex;
    flex-direction: column;
    transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar-nav {
    flex: 1;
    overflow-y: overlay; /* Scroll එක content එක උඩින් යන විදිහට හැදුවා */
    padding: 15px 0;
    scrollbar-width: thin; /* Firefox සඳහා සියුම් scrollbar එකක් */
    scrollbar-color: rgba(255,255,255,0.1) transparent;
}

/* Chrome/Safari scrollbar එක ලස්සනට පේන්න */
.sidebar-nav::-webkit-scrollbar {
    width: 4px;
}
.sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}

/* --- NAV LINKS WITH STROKE --- */
.nav-link-custom {
    display: flex; 
    align-items: center; 
    padding: 12px 20px;
    color: rgba(255,255,255,0.6); 
    text-decoration: none;
    border-radius: 12px; 
    margin: 6px 15px; 
    transition: 0.3s;
    font-size: 14px; 
    font-weight: 500;
    
    /* මෙන්න මෙතනින් තමයි stroke එක වැටෙන්නේ */
    border: 1px solid rgba(255, 255, 255, 0.05); 
    background: rgba(255, 255, 255, 0.02);
}

.nav-link-custom i { 
    font-size: 18px; 
    margin-right: 15px; 
}

/* Hover සහ Active අවස්ථාවේදී Stroke එක තව ටිකක් පේන්න හැදුවා */
.nav-link-custom:hover, .nav-link-custom.active {
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    border-color: rgba(255, 255, 255, 0.2); /* Stroke එක පැහැදිලි වෙනවා */
    transform: translateX(3px); /* පොඩි animation එකක් */
}

.nav-link-custom.active {
    border-color: rgba(255, 255, 255, 0.4);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

/* --- MAIN CONTENT AREA --- */
.main-content {
    margin-left: var(--sidebar-width);
    padding: 40px;
    transition: 0.3s;
}

h2.fw-bold {
    font-family: 'Playfair Display', serif !important;
    font-size: 2.2rem;
    letter-spacing: 1px;
}

/* --- STAT CARDS (GLASS EFFECT) --- */
.stat-card {
    background: var(--glass);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 25px; 
    padding: 30px;
    transition: 0.4s; 
    height: 100%;
}

.stat-card:hover { 
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-5px); 
}

.label-text { 
    font-size: 11px; 
    font-weight: 800; 
    color: #fff; 
    text-transform: uppercase; 
    letter-spacing: 1.5px; 
    opacity: 0.6;
}

.stat-card h2 {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    margin-top: 10px;
    font-size: 2.5rem;
}

/* --- MOBILE UI --- */
.mobile-header {
    display: none;
    background: rgba(10, 10, 10, 0.9);
    backdrop-filter: blur(20px);
    padding: 15px 20px;
    border-bottom: 1px solid var(--glass-border);
    position: sticky; top: 0; z-index: 1000;
}

.mobile-nav {
    display: none;
    position: fixed; bottom: 0px; left: 0px; right: 0px;
    height: 65px; background: rgba(15, 15, 15, 0.95);
    backdrop-filter: blur(25px); border-radius: 25px 25px 0 0;
    border: 1px solid var(--glass-border);
    justify-content: space-around; align-items: center;
    z-index: 1050;
    box-shadow: 0 20px 50px rgba(0,0,0,0.5);
}

.nav-item-m {
    text-align: center; color: rgba(255,255,255,0.3); text-decoration: none;
    font-size: 10px; font-weight: 700; text-transform: uppercase;
}

.nav-item-m.active { color: #fff; }
.nav-item-m i { font-size: 22px; display: block; margin-bottom: 2px; }

/* --- RESPONSIVE LOGIC --- */
@media (max-width: 991px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.active { transform: translateX(0); }
    .main-content { margin-left: 0; padding: 25px; padding-bottom: 120px; }
    .mobile-header { display: flex; align-items: center; justify-content: space-between; }
    .mobile-nav { display: flex; }
    .desktop-only { display: none; }
}

#overlay {
    display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.8); z-index: 1090; backdrop-filter: blur(8px);
}
#overlay.active { display: block; }
/* --- MOBILE OPTIMIZED SIZES --- */
@media (max-width: 768px) {
    /* Main Title size එක අඩු කිරීම */
    h2.fw-bold {
        font-size: 1.6rem !important;
    }

    /* Stat Cards වල padding සහ font size එක ගැලපීම */
    .stat-card {
        padding: 20px 15px !important;
        border-radius: 20px !important;
    }

    .stat-card h2 {
        font-size: 1.6rem !important; /* මුදල පෙන්වන අකුරු වල ප්‍රමාණය */
    }

    .label-text {
        font-size: 9px !important;
        letter-spacing: 1px !important;
    }

    /* Table එක Mobile එකේදී කියවන්න ලේසි කරන්න */
    .table thead {
        font-size: 9px !important;
    }

    .table tbody {
        font-size: 12px !important;
    }

    .table td, .table th {
        padding: 12px 8px !important; /* පේළි අතර ඉඩ අඩු කිරීම */
    }

    /* Badge එකේ size එකත් පොඩ්ඩක් අඩු කරමු */
    .badge {
        padding: 4px 8px !important;
        font-size: 10px !important;
    }

    /* Action button එකේ size එක */
    .btn-sm {
        padding: 5px 10px !important;
        font-size: 12px !important;
    }
}

/* ඉතා කුඩා Screen (e.g. iPhone SE) සඳහා තවදුරටත් optimize කිරීම */
@media (max-width: 400px) {
    .stat-card h2 {
        font-size: 1.3rem !important;
    }
    
    .nav-brand-text {
        font-size: 1rem !important;
    }
}
    </style>
</head>
<body>

    <div id="overlay"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h5 class="fw-bold m-0" style="background: linear-gradient(to right, #fff, var(--primary-neon)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">LAUNDRY CARE</h5>
            <small class="text-white-50" style="font-size: 0.65rem; letter-spacing: 2px;">ADMIN CONSOLE</small>
        </div>
        
        <div class="sidebar-nav">
            <a href="index.php" class="nav-link-custom active"><i class="bi bi-grid-1x2"></i> Home</a>
            <a href="manage_approvals.php" class="nav-link-custom"><i class="bi bi-shield-check"></i> Approvals <span class="badge bg-danger ms-auto"><?= $pending ?></span></a>
            <a href="staff.php" class="nav-link-custom"><i class="bi bi-person-plus"></i> Staff Registry</a>
            <a href="view_customers.php" class="nav-link-custom"><i class="bi bi-buildings"></i> Network Nodes</a>
            <a href="services.php" class="nav-link-custom"><i class="bi bi-box-seam"></i> Services</a>
            <a href="inventory.php" class="nav-link-custom"><i class="bi bi-archive"></i> Inventory</a>
            <a href="new_orders.php" class="nav-link-custom"><i class="bi bi-cart"></i> Orders</a>
            <a href="salaries.php" class="nav-link-custom"><i class="bi bi-wallet2"></i> Salary Management</a>
            <a href="Analytics.php" class="nav-link-custom"><i class="bi bi-graph-up-arrow"></i> Analytics</a>
            <a href="admin_setup.php" class="nav-link-custom"><i class="bi bi-gear"></i> System Config</a>
            
            <div style="margin-top: 30px; padding-bottom: 20px;">
                <a href="logout.php" class="nav-link-custom text-danger"><i class="bi bi-power"></i> Logout Session</a>
            </div>
        </div>
    </div>

    <div class="mobile-header">
        <button class="btn text-info fs-3 p-0" id="btnToggle"><i class="bi bi-list"></i></button>
        <h6 class="m-0 fw-bold">Admin Panel</h6>
        <div style="width: 35px;"></div>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <div class="mb-5 desktop-only">
                <h2 class="fw-bold">System <span style="color: var(--primary-neon);">Status</span></h2>
                <p class="text-white-50">Operational dashboard for managing infrastructure.</p>
            </div>

            <div class="row g-4">
                <div class="col-6 col-lg-4">
                    <div class="stat-card">
                        <span class="label-text">Requests</span>
                        <h2 class="fw-bold m-0 mt-1"><?= $pending ?></h2>
                        <p class="text-white-50 small m-0">Pending</p>
                    </div>
                </div>
                <div class="col-6 col-lg-4">
                    <div class="stat-card">
                        <span class="label-text">Staff</span>
                        <h2 class="fw-bold m-0 mt-1"><?= $staff ?></h2>
                        <p class="text-white-50 small m-0">Members</p>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="stat-card">
                        <span class="label-text">Network</span>
                        <h2 class="fw-bold m-0 mt-1"><?= $companies ?></h2>
                        <p class="text-white-50 small m-0">Active Nodes</p>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-2">
    <div class="col-12 col-md-4">
        <div class="stat-card" style="border-left: 4px solid #fff;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="label-text">Monthly Settlements</span>
                    <h2 class="fw-bold m-0 mt-2">Rs. <?= number_format($total_revenue, 2) ?></h2>
                    <p class="text-white-50 small m-0 mt-1"><i class="bi bi-calendar-check"></i> Current Month Status</p>
                </div>
                <div class="icon-box" style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 12px;">
                    <i class="bi bi-cash-stack fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="stat-card" style="border-left: 4px solid rgba(255,255,255,0.3);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="label-text">Staff Payments Due</span>
                    <h2 class="fw-bold m-0 mt-2">Rs. <?= number_format($total_staff_costs, 2) ?></h2>
                    <p class="text-white-50 small m-0 mt-1"><i class="bi bi-people"></i> Total Payroll Estimate</p>
                </div>
                <div class="icon-box" style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 12px;">
                    <i class="bi bi-wallet2 fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="stat-card" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="label-text" style="color: #fff; opacity: 1;">Projected Profit</span>
                    <h2 class="fw-bold m-0 mt-2" style="color: <?= $total_profit >= 0 ? '#00ff88' : '#ff4d4d' ?>;">
                        Rs. <?= number_format($total_profit, 2) ?>
                    </h2>
                    <p class="text-white-50 small m-0 mt-1">Net Earnings after Payroll</p>
                </div>
                <div class="icon-box" style="background: rgba(255,255,255,0.1); padding: 10px; border-radius: 12px;">
                    <i class="bi bi-graph-up-arrow fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>
        </div>
    </div>

    <div class="mobile-nav">
        <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
        <a href="admin_dashboard.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
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