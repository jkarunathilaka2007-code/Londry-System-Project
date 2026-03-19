<?php
session_start();
require 'config.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// 2. Logic - Remove/Deactivate Node
if (isset($_GET['remove'])) {
    $id = (int)$_GET['remove'];
    // Database එකෙන් මකන්නේ නැතුව Status එක Inactive කරන එක ආරක්ෂිතයි
    $stmt = $pdo->prepare("UPDATE customers SET status = 'Inactive' WHERE id = :id");
    if($stmt->execute(['id' => $id])) {
        header("Location: view_customers.php?msg=node_removed");
    }
    exit();
}

// 3. Data Fetch - Active Customers Only
$query = "SELECT customers.*, companies.business_name, companies.business_type, 
          companies.reg_number, companies.business_address, companies.business_logo 
          FROM customers 
          JOIN companies ON customers.company_id = companies.id 
          WHERE customers.status = 'Active' 
          ORDER BY customers.id DESC";

$stmt = $pdo->query($query);
$customers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Network Hub | Laundry Care</title>
    
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

    /* --- TOP NAV (FIXED) --- */
    .top-nav {
        background: rgba(10, 10, 10, 0.8);
        backdrop-filter: blur(30px);
        border-bottom: 1px solid var(--glass-border);
        padding: 12px 0;
        position: sticky; 
        top: 0; 
        z-index: 1000;
        width: 100%;
    }

    /* Title එක පේන්නේ නැති ප්‍රශ්නයට විසඳුම */
    .top-nav h5 {
        color: #ffffff !important;
        font-weight: 700 !important;
        margin: 0;
        background: none !important; /* Gradient effects අයින් කළා පේන්නේ නැති නිසා */
        -webkit-text-fill-color: initial !important;
        text-shadow: 0 2px 10px rgba(0,0,0,0.5);
    }

    .back-btn {
        width: 42px; height: 42px; border-radius: 12px;
        background: var(--glass); border: 1px solid var(--glass-border);
        display: flex; align-items: center; justify-content: center;
        color: #fff !important; text-decoration: none;
    }

    /* --- TABLE STYLING --- */
    .table-container { margin-top: 20px; }
    
    .table { 
        border-collapse: separate !important; 
        border-spacing: 0 12px !important; 
        background: transparent !important;
        --bs-table-bg: transparent !important;
    }

    .table thead th {
        border: none !important; 
        color: rgba(255,255,255,0.5) !important; 
        font-size: 11px; text-transform: uppercase; 
        letter-spacing: 1.5px; padding: 15px 20px;
    }

    .table tbody tr {
        background-color: rgba(255, 255, 255, 0.04) !important; 
        backdrop-filter: blur(15px); 
        transition: 0.3s;
    }

    .table td {
        border: none !important; 
        border-top: 1px solid var(--glass-border) !important;
        border-bottom: 1px solid var(--glass-border) !important;
        padding: 18px 20px !important; 
        vertical-align: middle; color: #fff !important;
        background: transparent !important;
    }

    .table td:first-child { border-left: 1px solid var(--glass-border) !important; border-radius: 20px 0 0 20px; }
    .table td:last-child { border-right: 1px solid var(--glass-border) !important; border-radius: 0 20px 20px 0; }

    /* --- MODAL / DOSSIER --- */
    .info-overlay {
        display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.85); backdrop-filter: blur(20px);
        z-index: 2000; align-items: center; justify-content: center;
    }
    .info-modal {
        background: #111; border: 1px solid var(--glass-border);
        border-radius: 30px; padding: 30px; width: 95%; max-width: 500px;
    }

    /* --- MOBILE STYLES --- */
    .mobile-nav { display: none !important; }

    @media (max-width: 991px) {
        .desktop-only { display: none !important; }
        .main-container { padding-bottom: 100px; }
        
        .mobile-card { 
            background: var(--glass); border: 1px solid var(--glass-border); 
            padding: 20px; border-radius: 20px; margin-bottom: 15px;
        }

        .mobile-nav {
            display: flex !important; 
            position: fixed; bottom: 0px; left: 0px; right: 0px;
            height: 70px; background: rgba(15, 15, 15, 0.95);
            backdrop-filter: blur(25px); 
            border-radius: 25px 25px 0 0; 
            border-top: 1px solid var(--glass-border); 
            justify-content: space-around; 
            align-items: center; 
            z-index: 1050;
        }
    }

    .nav-item-m { text-align: center; color: rgba(255,255,255,0.3); text-decoration: none; font-size: 10px; font-weight: 700; }
    .nav-item-m.active { color: #fff; }
    .nav-item-m i { font-size: 22px; display: block; }
    
    .action-btn { font-size: 1.2rem; transition: 0.3s; cursor: pointer; }
    .info-btn { color: #fff; opacity: 0.6; }
    .remove-btn { color: #ff4d4d; opacity: 0.6; }
    .action-btn:hover { opacity: 1; transform: scale(1.1); }
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
        <?php if (empty($customers)): ?>
            <div class="text-center py-5 mt-5 text-white-50">
                <i class="bi bi-diagram-3 display-1 opacity-25"></i>
                <h5 class="mt-4">Zero active nodes in the system.</h5>
            </div>
        <?php else: ?>
            
            <div class="table-container desktop-only">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Business / Company</th>
                            <th>Identity</th>
                            <th>Principal Name</th>
                            <th>Reg ID</th>
                            <th class="text-center">Control</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $row): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-white"><?= htmlspecialchars($row['business_name']) ?></div>
                                <div class="text-info x-small fw-bold" style="font-size: 0.7rem;"><?= htmlspecialchars($row['business_type']) ?></div>
                            </td>
                            <td><span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">ACTIVE</span></td>
                            <td class="text-white"><?= htmlspecialchars($row['full_name']) ?></td>
                            <td class="font-monospace small text-white-50"><?= htmlspecialchars($row['reg_number']) ?></td>
                            <td class="text-center">
                                <i class="bi bi-info-square-fill action-btn info-btn" onclick="openDossier(<?= htmlspecialchars(json_encode($row)) ?>)"></i>
                                <a href="?remove=<?= $row['id'] ?>" onclick="return confirm('Deactivate this node?')">
                                    <i class="bi bi-dash-circle-fill action-btn remove-btn"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-lg-none mt-4">
                <?php foreach ($customers as $row): ?>
                    <div class="mobile-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-bold text-white m-0"><?= htmlspecialchars($row['business_name']) ?></h6>
                                <small class="text-info"><?= htmlspecialchars($row['business_type']) ?></small>
                            </div>
                            <i class="bi bi-info-circle-fill text-info fs-4" onclick="openDossier(<?= htmlspecialchars(json_encode($row)) ?>)"></i>
                        </div>
                        <div class="mt-3">
                            <a href="?remove=<?= $row['id'] ?>" class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('Remove Node?')">Deactivate Node</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>

    <div id="infoLayer" class="info-overlay" onclick="closeDossier()">
        <div class="info-modal shadow-lg" onclick="event.stopPropagation()">
            <div class="text-center mb-4">
                <img id="dLogo" src="" class="rounded-4 mb-3" style="width: 90px; height: 90px; object-fit: cover; border: 2px solid var(--primary-neon); background: #1a1a20;">
                <h4 id="dBizName" class="fw-bold text-white mb-1"></h4>
                <div id="dBizType" class="text-info small fw-bold text-uppercase"></div>
            </div>
            
            <div id="dData" class="row g-3 text-white">
                </div>
            
            <button class="btn btn-info w-100 mt-4 rounded-pill fw-bold" onclick="closeDossier()">DISMISS</button>
        </div>
    </div>

    <div class="mobile-nav">
        <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
        <a href="admin_dashboard.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
        <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
    </div>

    <script>
        function openDossier(data) {
            document.getElementById('dLogo').src = data.business_logo ? 'uploads/logos/' + data.business_logo : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
            document.getElementById('dBizName').innerText = data.business_name;
            document.getElementById('dBizType').innerText = data.business_type;
            
            document.getElementById('dData').innerHTML = `
                <div class="col-6"><label class="text-white-50 x-small d-block opacity-50">PRINCIPAL</label><b>${data.full_name}</b></div>
                <div class="col-6"><label class="text-white-50 x-small d-block opacity-50">NIC NO</label><b>${data.nic_number}</b></div>
                <div class="col-12 border-top border-white border-opacity-10 pt-2"><label class="text-white-50 x-small d-block opacity-50">COMMUNICATION</label><b class="text-info">${data.phone_number}</b><br><span class="small opacity-75">${data.email_address}</span></div>
                <div class="col-12 border-top border-white border-opacity-10 pt-2"><label class="text-white-50 x-small d-block opacity-50">OFFICE ADDRESS</label><span class="small opacity-75">${data.business_address}</span></div>
                <div class="col-12 border-top border-white border-opacity-10 pt-2"><label class="text-white-50 x-small d-block opacity-50">REGISTRATION</label><span class="font-monospace small text-info">${data.reg_number}</span></div>
            `;
            document.getElementById('infoLayer').style.display = 'flex';
        }
        function closeDossier() { document.getElementById('infoLayer').style.display = 'none'; }
    </script>
</body>
</html>