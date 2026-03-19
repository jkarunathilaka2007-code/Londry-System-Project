<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'config.php';

// Admin Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- Status Update Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status_update'])) {
    $item_id = intval($_POST['item_id']);
    $new_status = strtolower(trim($_POST['status']));
    try {
        $update_stmt = $pdo->prepare("UPDATE order_items SET status = ? WHERE id = ?");
        $update_stmt->execute([$new_status, $item_id]);
        header("Location: new_orders.php"); 
        exit();
    } catch (PDOException $e) { die("Error: " . $e->getMessage()); }
}

// --- Data Fetching ---
try {
    $services_list = $pdo->query("SELECT id, service_name FROM services")->fetchAll();
    $query = "SELECT oi.id AS item_id, o.id AS order_id, comp.business_name, s.service_name, oi.quantity, 
                     oi.subtotal, oi.status AS item_status, o.order_date
              FROM order_items oi
              JOIN orders o ON oi.order_id = o.id
              JOIN customers cust ON o.customer_id = cust.id
              JOIN companies comp ON cust.company_id = comp.id
              JOIN services s ON oi.service_id = s.id
              WHERE oi.status IN ('pending', 'processing', 'completed', 'delivered', 'cancelled')
              ORDER BY o.id DESC";
    $all_items = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Master Queue | Fabricare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
    :root { 
        --bg-dark: #080808; 
        --glass: rgba(255, 255, 255, 0.05); 
        --glass-border: rgba(255, 255, 255, 0.1);
        --font-main: 'Plus Jakarta Sans', sans-serif;
    }
    
    body { 
        background-color: var(--bg-dark);
        background-image: linear-gradient(rgba(8, 8, 8, 0.9), rgba(8, 8, 8, 0.9)), 
                          url('uploads/resources/bg2.jpg');
        background-size: cover;
        background-attachment: fixed;
        color: #ffffff; 
        font-family: var(--font-main); 
        margin: 0; 
        padding-top: 75px; 
        padding-bottom: 30px; /* Desktop padding */
    }

    /* --- TOP NAV (Luxe Style) --- */
    .top-nav {
        background: rgba(10, 10, 10, 0.85);
        backdrop-filter: blur(30px);
        border-bottom: 1px solid var(--glass-border);
        padding: 12px 0;
        position: fixed; top: 0; width: 100%; z-index: 1000;
    }

    /* Master Queue Text Visibility Fix */
    .top-nav h5 {
        color: #ffffff !important;
        font-weight: 700 !important;
        margin: 0;
        font-size: 1.1rem;
        background: none !important;
        -webkit-text-fill-color: #ffffff !important;
        opacity: 1 !important;
    }

    .back-btn {
        width: 38px; height: 38px; border-radius: 10px;
        background: var(--glass); border: 1px solid var(--glass-border);
        display: flex; align-items: center; justify-content: center;
        color: #fff !important; text-decoration: none; transition: 0.3s;
    }

    #orderCount {
        background: var(--glass) !important;
        border: 1px solid var(--glass-border) !important;
        color: #fff !important;
        font-weight: 700;
        border-radius: 8px;
    }

    /* --- FILTER SYSTEM --- */
    .filter-container {
        overflow-x: auto; white-space: nowrap; display: flex; gap: 8px;
        padding: 15px; scrollbar-width: none;
    }
    .filter-container::-webkit-scrollbar { display: none; }

    .btn-filter {
        background: var(--glass); border: 1px solid var(--glass-border);
        color: rgba(255,255,255,0.4); border-radius: 12px; padding: 8px 18px; 
        font-size: 0.75rem; text-transform: uppercase; font-weight: 700; transition: 0.3s;
    }
    .btn-filter.active { background: #ffffff; color: #000000; border-color: #ffffff; }

    #serviceFilter {
        background: var(--glass); color: #fff; border: 1px solid var(--glass-border);
        border-radius: 15px; padding: 12px; width: calc(100% - 30px); margin: 0 15px 15px 15px;
        font-size: 0.85rem; outline: none;
    }

    /* --- QUEUE TABLE (DESKTOP) --- */
    .table-responsive-custom { padding: 0 15px; }
    .master-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
    .order-row { background: var(--glass); backdrop-filter: blur(15px); border-radius: 18px; transition: 0.3s; }
    
    .master-table th { 
        padding: 10px 20px; font-size: 0.65rem; color: rgba(255,255,255,0.4); 
        text-transform: uppercase; letter-spacing: 1.5px; font-weight: 800;
    }

    .master-table td { 
        padding: 18px 20px; vertical-align: middle; 
        border-top: 1px solid var(--glass-border); border-bottom: 1px solid var(--glass-border); 
    }

    /* --- TRANSPARENT STATUS DROPDOWN --- */
    .status-select {
        background: transparent !important;
        color: #ffffff !important;
        border: 1px solid var(--glass-border);
        border-radius: 10px; padding: 8px 12px; font-size: 0.75rem; 
        font-weight: 700; width: 100%; cursor: pointer; text-transform: uppercase;
        outline: none; transition: 0.3s;
    }

    .status-select option { background: #111111; color: #ffffff; }

    /* Border highlight based on status */
    .order-row[data-status="pending"] .status-select { border-color: #ff9f0a; }
    .order-row[data-status="processing"] .status-select { border-color: #007aff; }
    .order-row[data-status="completed"] .status-select { border-color: #34c759; }
    .order-row[data-status="delivered"] .status-select { border-color: #af52de; }
    .order-row[data-status="cancelled"] .status-select { border-color: #ff3b30; }

    /* --- MOBILE NAV (HIDDEN ON DESKTOP) --- */
    .bottom-nav {
        display: none; 
    }

    /* --- MOBILE RESPONSIVE OPTIMIZATION --- */
    @media (max-width: 768px) {
        body { padding-bottom: 90px; } /* Space for mobile nav */
        
        .bottom-nav {
            display: flex; /* Only visible on mobile */
            position: fixed; bottom: 0; left: 0; right: 0; height: 65px;
            background: rgba(10, 10, 10, 0.98); backdrop-filter: blur(25px);
            border-top: 1px solid var(--glass-border);
            justify-content: space-around; align-items: center; z-index: 1050;
            border-radius: 20px 20px 0 0;
        }

        .nav-item-m { text-align: center; color: rgba(255,255,255,0.3); text-decoration: none; font-size: 10px; font-weight: 600; flex: 1; }
        .nav-item-m.active { color: #ffffff; }
        .nav-item-m i { font-size: 20px; display: block; margin-bottom: 2px; }

        /* Card-style layout for mobile */
        .master-table thead { display: none; }
        .master-table, .master-table tbody, .master-table tr, .master-table td { display: block; width: 100%; }
        
        .order-row { 
            margin-bottom: 12px; padding: 15px; 
            border: 1px solid var(--glass-border) !important;
            border-radius: 20px;
        }
        
        .master-table td { 
            border: none !important; padding: 6px 0; 
            display: flex; justify-content: space-between; align-items: center;
            font-size: 0.8rem;
        }

        .master-table td::before { 
            content: attr(data-label); font-weight: 800; 
            color: rgba(255,255,255,0.25); font-size: 0.6rem; 
            text-transform: uppercase;
        }

        .status-select { width: 130px; padding: 5px; font-size: 0.7rem; }
    }

    #syncLoader {
        display:none; position: fixed; top:0; left:0; width:100%; height:100%;
        background: rgba(0,0,0,0.9); z-index: 2000; justify-content:center; align-items:center;
    }
</style>
</head>
<body>

<div id="syncLoader"><div class="spinner-border text-info"></div></div>

<nav class="top-nav">
    <div class="container d-flex align-items-center justify-content-between px-3">
        <div class="d-flex align-items-center">
            <a href="admin_dashboard.php" class="back-btn"><i class="bi bi-chevron-left"></i></a>
            <div class="ms-3">
                <h5 class="m-0 fw-bold" style="background: linear-gradient(to right, #fff, var(--primary-neon)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Master Queue</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Admin Control Panel</small>
            </div>
        </div>
        <span id="orderCount" class="badge bg-dark border border-info text-info">0</span>
    </div>
</nav>

<div class="container-fluid px-2 px-md-4 mt-2">
    
    <div class="filter-container">
        <button class="btn-filter active" onclick="setFilter('pending', this)">Pending</button>
        <button class="btn-filter" onclick="setFilter('processing', this)">Processing</button>
        <button class="btn-filter" onclick="setFilter('completed', this)">Completed</button>
        <button class="btn-filter" onclick="setFilter('delivered', this)">Delivered</button>
        <button class="btn-filter" onclick="setFilter('all', this)">All Orders</button>
    </div>

    <div class="px-3 mb-3">
        <select id="serviceFilter" class="status-select" style="height: 40px; font-size: 13px;">
            <option value="">Filter by Service Type</option>
            <?php foreach($services_list as $s): ?>
                <option value="<?php echo htmlspecialchars($s['service_name']); ?>"><?php echo htmlspecialchars($s['service_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="table-responsive-custom">
        <table class="master-table">
            <thead>
                <tr>
                    <th>Customer/Business</th>
                    <th>Service</th>
                    <th>Qty</th>
                    <th>Amount</th>
                    <th>Status Action</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach ($all_items as $item): ?>
                <tr class="order-row" data-status="<?php echo $item['item_status']; ?>" data-service="<?php echo htmlspecialchars($item['service_name']); ?>">
                    
                    <td data-label="Business">
                        <div class="fw-bold"><?php echo htmlspecialchars($item['business_name']); ?></div>
                    </td>
                    <td data-label="Service">
                        <span style="color: var(--primary-neon); font-size: 0.85rem;"><?php echo htmlspecialchars($item['service_name']); ?></span>
                    </td>
                    <td data-label="Quantity"><?php echo $item['quantity']; ?></td>
                    <td data-label="Total" class="fw-bold text-info">LKR <?php echo number_format($item['subtotal'], 0); ?></td>
                    <td data-label="Status">
                        <form method="POST" id="form-<?php echo $item['item_id']; ?>" class="m-0">
                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                            <input type="hidden" name="status_update" value="1">
                            <select name="status" class="status-select" onchange="autoSubmit(<?php echo $item['item_id']; ?>)">
                                <option value="pending" <?php echo ($item['item_status'] == 'pending') ? 'selected' : ''; ?>>PENDING</option>
                                <option value="processing" <?php echo ($item['item_status'] == 'processing') ? 'selected' : ''; ?>>PROCESSING</option>
                                <option value="completed" <?php echo ($item['item_status'] == 'completed') ? 'selected' : ''; ?>>COMPLETED</option>
                                <option value="delivered" <?php echo ($item['item_status'] == 'delivered') ? 'selected' : ''; ?>>DELIVERED</option>
                                <option value="cancelled" <?php echo ($item['item_status'] == 'cancelled') ? 'selected' : ''; ?>>CANCELLED</option>
                            </select>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="bottom-nav">
    <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i>Home</a>
    <a href="admin_dashboard.php" class="nav-item-m active"><i class="bi bi-person"></i>Profile</a>
    <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i>Logout</a>
</div>

<script>
let activeStatus = 'pending';

function autoSubmit(itemId) {
    document.getElementById('syncLoader').style.display = 'flex';
    document.getElementById('form-' + itemId).submit();
}

function setFilter(status, btn) {
    document.querySelectorAll('.btn-filter').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    activeStatus = status;
    filterTable();
}

function filterTable() {
    const svVal = document.getElementById('serviceFilter').value.toLowerCase();
    const rows = document.querySelectorAll('.order-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const rowS = row.getAttribute('data-status').toLowerCase();
        const rowSv = row.getAttribute('data-service').toLowerCase();
        
        const statusMatch = (activeStatus === 'all' || rowS === activeStatus);
        const serviceMatch = (svVal === "" || rowSv === svVal);

        if (statusMatch && serviceMatch) {
            row.style.display = "";
            visibleCount++;
        } else {
            row.style.display = "none";
        }
    });
    document.getElementById('orderCount').innerText = visibleCount;
}

window.onload = filterTable;
document.getElementById('serviceFilter').addEventListener('change', filterTable);
</script>

</body>
</html>