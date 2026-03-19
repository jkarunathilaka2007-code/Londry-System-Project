<?php
session_start();
require 'config.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// දත්ත ලබාගැනීම (All Employees)
$query = "SELECT * FROM employees ORDER BY id DESC";
$stmt = $pdo->query($query);
$staff_members = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Staff Registry | Laundry Care</title>
    
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

    /* --- TOP NAV --- */
    .top-nav {
        background: rgba(10, 10, 10, 0.8);
        backdrop-filter: blur(30px);
        border-bottom: 1px solid var(--glass-border);
        padding: 15px 0;
        position: sticky; top: 0; z-index: 1000;
    }

    .top-nav h5 {
        color: #ffffff !important;
        font-weight: 700 !important;
        text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        background: none !important;
        -webkit-text-fill-color: initial !important;
    }

    .back-btn {
        width: 42px; height: 42px; border-radius: 12px;
        background: var(--glass); border: 1px solid var(--glass-border);
        display: flex; align-items: center; justify-content: center;
        color: #fff !important; text-decoration: none; transition: 0.3s;
    }

    /* --- TABLE STYLING (FIXED FOR WHITE BACKGROUND ISSUE) --- */
    .table-container { margin-top: 20px; }

    /* Bootstrap Default Colors සම්පූර්ණයෙන්ම Reset කිරීම */
    .table { 
        border-collapse: separate !important; 
        border-spacing: 0 12px !important; 
        background-color: transparent !important; 
        --bs-table-bg: transparent !important; 
        --bs-table-accent-bg: transparent !important;
        --bs-table-striped-bg: transparent !important;
        --bs-table-hover-bg: transparent !important;
    }

    .table thead th {
        border: none !important; 
        color: rgba(255,255,255,0.5) !important; 
        font-size: 11px; text-transform: uppercase; 
        letter-spacing: 1.5px; padding: 15px 20px;
        background: transparent !important;
    }

    /* Row එකට විතරක් Glass Effect එක දීම */
    .table tbody tr {
        background-color: rgba(255, 255, 255, 0.05) !important; 
        backdrop-filter: blur(15px); 
        transition: 0.3s;
    }

    .table tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.08) !important; 
        transform: translateY(-2px);
    }

    .table td {
        border: none !important; 
        border-top: 1px solid var(--glass-border) !important;
        border-bottom: 1px solid var(--glass-border) !important;
        padding: 18px 20px !important; 
        vertical-align: middle; 
        color: #fff !important;
        background: transparent !important; /* TD එකේ background එක force transparent කළා */
    }

    .table td:first-child { border-left: 1px solid var(--glass-border) !important; border-radius: 20px 0 0 20px; }
    .table td:last-child { border-right: 1px solid var(--glass-border) !important; border-radius: 0 20px 20px 0; }

    .staff-avatar { 
        width: 45px; height: 45px; border-radius: 12px; 
        object-fit: cover; border: 1px solid var(--glass-border); 
    }

    /* --- NAVIGATION LOGIC --- */
    .mobile-nav { display: none !important; }

    @media (max-width: 991px) {
        .desktop-only { display: none !important; }
        .main-container { padding-bottom: 120px !important; }

        .mobile-card { 
            background: var(--glass); backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border); padding: 20px; 
            border-radius: 20px; margin-bottom: 15px; 
        }

        .mobile-nav {
            display: flex !important; 
            position: fixed; bottom: 0px; left: 0px; right: 0px;
            height: 65px; background: rgba(15, 15, 15, 0.95);
            backdrop-filter: blur(25px); border-radius: 25px 25px 0 0;
            border: 1px solid var(--glass-border); justify-content: space-around; 
            align-items: center; z-index: 1050;
        }
    }

    @media (min-width: 992px) {
        .main-container { padding-bottom: 30px !important; }
    }

    .nav-item-m { text-align: center; color: rgba(255,255,255,0.3); text-decoration: none; font-size: 10px; font-weight: 700; }
    .nav-item-m.active { color: #fff; }
    .nav-item-m i { font-size: 22px; display: block; }

    .badge.bg-success {
        background: rgba(40, 167, 69, 0.1) !important;
        color: #28a745 !important;
        border: 1px solid rgba(40, 167, 69, 0.2);
    }
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
            <a href="employee_register.php" class="nav-link-btn text-white" style="font-size: 1.5rem;">
                <i class="bi bi-plus-circle-fill"></i>
            </a>
        </div>

    </div>
</nav>

    <div class="container main-container mt-4">
        <?php if (empty($staff_members)): ?>
            <div class="text-center py-5 mt-5 text-white-50">
                <i class="bi bi-people display-1 opacity-25"></i>
                <h5 class="mt-4">No staff records found.</h5>
            </div>
        <?php else: ?>
            
            <div class="table-container desktop-only">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Role</th>
                            <th>Phone</th> <th>NIC Number</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff_members as $member): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="uploads/profiles/<?= $member['profile_image'] ?>" class="staff-avatar me-3" onerror="this.src='https://cdn-icons-png.flaticon.com/512/149/149071.png'">
                                    <div class="fw-bold"><?= htmlspecialchars($member['full_name']) ?></div>
                                </div>
                            </td>
                            <td class="small text-info"><?= htmlspecialchars($member['designation']) ?></td>
                            <td class="small"><?= htmlspecialchars($member['phone']) ?></td> <td class="font-monospace small opacity-75"><?= htmlspecialchars($member['nic_number']) ?></td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3">
                                    <?= htmlspecialchars($member['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-lg-none">
                <?php foreach ($staff_members as $member): ?>
                    <div class="mobile-card">
                        <div class="d-flex align-items-center">
                            <img src="uploads/profiles/<?= $member['profile_image'] ?>" class="staff-avatar me-3" onerror="this.src='https://cdn-icons-png.flaticon.com/512/149/149071.png'">
                            <div>
                                <h6 class="fw-bold m-0"><?= htmlspecialchars($member['full_name']) ?></h6>
                                <small class="text-info"><?= htmlspecialchars($member['designation']) ?></small>
                            </div>
                        </div>
                        <div class="mt-3 pt-2 border-top border-white border-opacity-10 d-flex justify-content-between align-items-center">
                            <small class="text-white-50"><i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($member['phone']) ?></small> <a href="tel:<?= $member['phone'] ?>" class="btn btn-sm btn-info rounded-circle"><i class="bi bi-phone"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
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