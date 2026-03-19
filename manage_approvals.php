<?php
session_start();
require 'config.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- LOGIC FIX START ---

// 1. Logic - Approve (Status එක 'Active' ලෙස update කිරීම)
if (isset($_GET['approve'])) {
    $id = $_GET['approve'];
    // මෙතන 'Active' කියන වචනය හරියටම database එකේ column එකට යනවා
    $stmt = $pdo->prepare("UPDATE customers SET status = 'Active' WHERE id = ?");
    if($stmt->execute([$id])) {
        header("Location: manage_approvals.php?msg=approved");
    } else {
        header("Location: manage_approvals.php?msg=error");
    }
    exit();
}

// 2. Logic - Reject (Delete කරනවා වෙනුවට 'Rejected' status එක දාමු)
if (isset($_GET['reject'])) {
    $id = $_GET['reject'];
    $stmt = $pdo->prepare("UPDATE customers SET status = 'Rejected' WHERE id = ?");
    if($stmt->execute([$id])) {
        header("Location: manage_approvals.php?msg=rejected");
    } else {
        header("Location: manage_approvals.php?msg=error");
    }
    exit();
}

// --- LOGIC FIX END ---

// Data Fetch (Pending අය පමණක් පෙන්වීමට)
$query = "SELECT customers.*, companies.business_name, companies.business_type, 
          companies.reg_number, companies.business_address, companies.business_logo 
          FROM customers 
          JOIN companies ON customers.company_id = companies.id 
          WHERE customers.status = 'Pending' 
          ORDER BY customers.id DESC";

$stmt = $pdo->query($query);
$pending_list = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Verification Center | Laundry Care</title>
    
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
    padding: 0;
    overflow-x: hidden;
}

/* --- TOP NAV --- */
.top-nav {
    background: rgba(10, 10, 10, 0.8);
    backdrop-filter: blur(30px);
    border-bottom: 1px solid var(--glass-border);
    padding: 15px 0;
    position: sticky; 
    top: 0; 
    z-index: 1000;
}

/* --- TITLE & TEXT FIX --- */
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

/* --- TABLE STYLING --- */
.table-container { margin-top: 20px; }
.table { border-collapse: separate; border-spacing: 0 12px; background: transparent !important; }

.table thead th {
    border: none; color: rgba(255,255,255,0.5) !important; 
    font-size: 11px; text-transform: uppercase; 
    letter-spacing: 1.5px; padding: 15px 20px;
}

.table tbody tr {
    background: var(--glass) !important;
    backdrop-filter: blur(15px); transition: 0.3s;
}

.table td {
    border: none !important; 
    border-top: 1px solid var(--glass-border) !important;
    border-bottom: 1px solid var(--glass-border) !important;
    padding: 20px !important; vertical-align: middle; color: #fff !important;
}

.table td:first-child { border-left: 1px solid var(--glass-border) !important; border-radius: 20px 0 0 20px; }
.table td:last-child { border-right: 1px solid var(--glass-border) !important; border-radius: 0 20px 20px 0; }

.btn-details {
    background: rgba(255, 255, 255, 0.05); border: 1px solid var(--glass-border);
    color: #fff; border-radius: 10px; padding: 6px 15px;
    font-size: 11px; font-weight: 700; letter-spacing: 1px; transition: 0.3s;
}
.btn-details:hover { background: #fff; color: #000; }

/* --- NAVIGATION LOGIC (MOBILE vs DESKTOP) --- */
.mobile-nav {
    display: none !important; /* Default විදිහට හැම තැනම hide කරනවා */
}

@media (max-width: 991px) {
    .desktop-only { display: none !important; }
    
    .main-container {
        padding-bottom: 110px !important; /* Mobile යටින් ඉඩ තියනවා */
    }

    .mobile-card { 
        background: var(--glass); backdrop-filter: blur(15px);
        border: 1px solid var(--glass-border); padding: 25px; 
        border-radius: 25px; margin-bottom: 20px; 
    }

    .mobile-nav {
        display: flex !important; /* Mobile එකේදී විතරක් පෙන්නනවා */
        position: fixed; bottom: 0px; left: 0px; right: 0px;
        height: 65px; background: rgba(15, 15, 15, 0.95);
        backdrop-filter: blur(25px); border-radius: 25px 25px 0 0;
        border: 1px solid var(--glass-border); justify-content: space-around; 
        align-items: center; z-index: 1050;
    }
}

@media (min-width: 992px) {
    .main-container {
        padding-bottom: 30px !important; /* Desktop එකේ අනවශ්‍ය ඉඩ අයින් කරනවා */
    }
}

.nav-item-m { text-align: center; color: rgba(255,255,255,0.3); text-decoration: none; font-size: 10px; }
.nav-item-m.active { color: #fff; }
.nav-item-m i { font-size: 22px; display: block; }

/* --- INFO MODAL --- */
.info-overlay {
    display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.8); backdrop-filter: blur(15px);
    z-index: 2000; align-items: center; justify-content: center;
}
.info-modal {
    background: rgba(20, 20, 20, 0.95); border: 1px solid var(--glass-border);
    border-radius: 30px; padding: 30px; width: 92%; max-width: 450px;
}
.avatar-item img { 
    width: 80px; height: 80px; border-radius: 20px; object-fit: cover; 
    border: 1px solid var(--glass-border); 
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

    </div>
</nav>

    <div class="container main-container mt-4">
        <?php if (empty($pending_list)): ?>
            <div class="text-center py-5 mt-5">
                <i class="bi bi-shield-check text-info display-1 opacity-25"></i>
                <h5 class="mt-4 text-white-50">No pending approvals.</h5>
            </div>
        <?php else: ?>
            
            <div class="table-container desktop-only">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Business</th>
                            <th>Reg No</th>
                            <th>Owner</th>
                            <th>Details</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_list as $row): ?>
                        <tr>
                            <td><div class="fw-bold text-white"><?= htmlspecialchars($row['business_name']) ?></div><div class="text-info small"><?= htmlspecialchars($row['business_type']) ?></div></td>
                            <td class="small text-white-50"><?= htmlspecialchars($row['reg_number']) ?></td>
                            <td class="small"><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><button class="btn-details" onclick="openLayer(<?= htmlspecialchars(json_encode($row)) ?>)">PROFILE</button></td>
                            <td class="text-center">
                                <a href="?approve=<?= $row['id'] ?>" class="text-info fs-4 mx-2"><i class="bi bi-check-circle-fill"></i></a>
                                <a href="?reject=<?= $row['id'] ?>" class="text-danger fs-4 mx-2"><i class="bi bi-x-circle-fill"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-lg-none mt-4">
                <?php foreach ($pending_list as $row): ?>
                    <div class="mobile-card">
                        <div class="d-flex justify-content-between align-items-center mb-3 text-white">
                            <h6 class="fw-bold m-0"><?= htmlspecialchars($row['business_name']) ?></h6>
                            <button class="btn-details" onclick="openLayer(<?= htmlspecialchars(json_encode($row)) ?>)">Info</button>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="?approve=<?= $row['id'] ?>" class="btn btn-info btn-sm w-100 fw-bold">Approve</a>
                            <a href="?reject=<?= $row['id'] ?>" class="btn btn-outline-danger btn-sm w-100">Reject</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div id="infoLayer" class="info-overlay" onclick="closeLayer()">
        <div class="info-modal" onclick="event.stopPropagation()">
            <div class="avatar-section">
                <div class="avatar-item"><img id="bizLogo" src="" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'"><span>Logo</span></div>
                <div class="avatar-item"><img id="userImg" src="" onerror="this.src='https://cdn-icons-png.flaticon.com/512/149/149071.png'"><span>Owner</span></div>
            </div>
            <div id="layerBody" class="small text-white"></div>
            <button class="btn btn-info w-100 mt-4 rounded-pill fw-bold" onclick="closeLayer()">DISMISS</button>
        </div>
    </div>

    <div class="mobile-nav">
        <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
        <a href="admin_dashboard.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
        <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
    </div>

    <script>
        function openLayer(data) {
            document.getElementById('bizLogo').src = data.business_logo ? 'uploads/logos/' + data.business_logo : '';
            document.getElementById('userImg').src = data.profile_image ? 'uploads/profiles/' + data.profile_image : '';
            document.getElementById('layerBody').innerHTML = `
                <div class="mb-3"><label class="text-white-50 x-small d-block">OWNER</label><div class="fw-bold">${data.full_name}</div></div>
                <div class="mb-3"><label class="text-white-50 x-small d-block">CONTACT</label><div class="fw-bold text-info">${data.phone_number}</div></div>
                <div class="mb-1"><label class="text-white-50 x-small d-block">ADDRESS</label><div class="text-white-50">${data.business_address}</div></div>
            `;
            document.getElementById('infoLayer').style.display = 'flex';
        }
        function closeLayer() { document.getElementById('infoLayer').style.display = 'none'; }
    </script>
</body>
</html>