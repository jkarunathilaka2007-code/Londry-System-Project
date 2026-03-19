<?php
session_start();
require 'config.php';

// 1. ආරක්ෂාව සහ Role එක පරීක්ෂා කිරීම
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- GET FILTER VALUES ---
$filter_status = $_GET['status'] ?? 'all';
$filter_due = $_GET['due'] ?? 'all';

try {
    // 2. PAYMENT SUMMARY CALCULATION
    // Delivered නම් Paid ලෙසත්, අනෙක්වා Not Paid ලෙසත් ගණන් බලයි
    $summary_query = "
        SELECT 
            SUM(CASE WHEN oi.status = 'delivered' THEN (o.total_price / (SELECT COUNT(*) FROM order_items WHERE order_id = o.id)) * 1 ELSE 0 END) as total_paid,
            SUM(CASE WHEN oi.status != 'delivered' THEN (o.total_price / (SELECT COUNT(*) FROM order_items WHERE order_id = o.id)) * 1 ELSE 0 END) as total_unpaid
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.customer_id = ?
    ";
    // සටහන: මෙහි total_price එක අයිටම් ගණනින් බෙදා ඇත්තේ සම්පූර්ණ ඕඩර් එකේ මිල අයිටම් වලට සාධාරණව බෙදා වෙන් කර පෙන්වීමටයි.
    $stmt_sum = $pdo->prepare($summary_query);
    $stmt_sum->execute([$user_id]);
    $summary = $stmt_sum->fetch();

    // 3. SQL Query WITH FILTERS
    $sql_conditions = ["o.customer_id = ?"];
    $params = [$user_id];

    if ($filter_status !== 'all') {
        $sql_conditions[] = "oi.status = ?";
        $params[] = $filter_status;
    }

    if ($filter_due !== 'all') {
        if ($filter_due == 'today') {
            $sql_conditions[] = "DATE(oi.item_due_date) = CURDATE()";
        } elseif ($filter_due == 'week') {
            $sql_conditions[] = "WEEK(oi.item_due_date) = WEEK(CURDATE()) AND YEAR(oi.item_due_date) = YEAR(CURDATE())";
        }
    }

    $where_clause = implode(" AND ", $sql_conditions);

    $query = "
        SELECT 
            o.id as order_id, 
            o.order_date, 
            o.total_price, 
            o.pickup_required,
            oi.status as item_status, 
            oi.quantity,
            oi.item_due_date,
            s.service_name,
            s.service_image
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN services s ON oi.service_id = s.id
        WHERE $where_clause
        ORDER BY o.id DESC, oi.id ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Query Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Track Orders | Fabricare</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    
    <style>
    :root {
        --bg-dark: #080808;
        --glass: rgba(255, 255, 255, 0.05);
        --glass-border: rgba(255, 255, 255, 0.1);
    }

    body {
        background-color: var(--bg-dark);
        background-image: linear-gradient(rgba(8, 8, 8, 0.85), rgba(8, 8, 8, 0.95)), 
                          url('uploads/resources/bg2.jpg');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        min-height: 100vh;
        font-family: 'Plus Jakarta Sans', sans-serif;
        color: #ffffff;
        margin: 0;
    }

    /* --- TOP NAV --- */
    .top-nav {
    background: rgba(10, 10, 10, 0.9) !important;
    backdrop-filter: blur(30px);
    border-bottom: 1px solid var(--glass-border);
    padding: 10px 0;
    position: sticky; top: 0; z-index: 1000;
}

/* Titles and Text inside Top Nav */
.top-nav h5, 
.top-nav .ms-3 h5 {
    color: #ffffff !important;
    font-weight: 700 !important;
    opacity: 1 !important;
    margin: 0;
    -webkit-text-fill-color: #ffffff !important; /* Force gradient override */
}

.top-nav small, 
.top-nav .text-white-50 {
    color: rgba(255, 255, 255, 0.7) !important; /* Making "Workshop Terminal" brighter */
    font-weight: 500;
}
    /* --- STATS & FILTERS --- */
    .stats-container {
        background: var(--glass);
        border-radius: 15px;
        padding: 12px;
        border: 1px solid var(--glass-border);
        margin: 15px 0;
        display: flex;
    }
    .stat-box { flex: 1; text-align: center; border-right: 1px solid var(--glass-border); }
    .stat-box:last-child { border-right: none; }
    .stat-label { font-size: 8px; text-transform: uppercase; opacity: 0.5; }
    .stat-value { font-weight: 700; font-size: 13px; }

    /* --- GRID SYSTEM --- */
    .items-grid { 
        display: grid; 
        gap: 8px; 
    }

    .item-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid var(--glass-border);
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 8px;
        text-align: center;
    }

    /* IMAGE SIZE CONTROL */
    .img-box {
        background: rgba(255,255,255,0.05);
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden;
    }

    .img-box img { 
        max-width: 80%; 
        max-height: 80%; 
        object-fit: contain; 
    }

    .item-name { 
        font-weight: 600; 
        line-height: 1.2;
        margin: 5px 0;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }

    .status-pill {
        font-size: 7px;
        padding: 2px 6px;
        border-radius: 4px;
        background: rgba(255,255,255,0.1);
        text-transform: uppercase;
    }

    /* --- RESPONSIVE LOGIC --- */

    /* Desktop: 2 per row, larger images */
    @media (min-width: 992px) {
        .container { max-width: 850px !important; }
        .items-grid { grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .item-card { flex-direction: row; text-align: left; padding: 15px; }
        .img-box { width: 70px; height: 70px; min-width: 70px; margin-right: 15px; }
        .item-name { font-size: 14px; margin: 0 0 5px 0; }
        .mobile-nav { display: none !important; }
    }

    /* Mobile: 3 per row, micro images */
    @media (max-width: 991px) {
        .items-grid { grid-template-columns: repeat(3, 1fr); }
        .container { padding: 0 10px; }
        
        /* IMAGE පොඩි කිරීම */
        .img-box { 
            width: 45px; /* ඉතාම පොඩි ගාණකට සෙට් කළා */
            height: 45px; 
            margin-bottom: 5px;
        }
        
        .item-name { font-size: 9px; height: 22px; }
        .status-pill { font-size: 7px; zoom: 0.9; }

        body { padding-bottom: 80px; }
        .mobile-nav {
            display: flex; position: fixed; bottom: 0; left: 0; right: 0;
            height: 60px; background: rgba(10,10,10,0.95);
            backdrop-filter: blur(20px); border-top: 1px solid var(--glass-border);
            justify-content: space-around; align-items: center; z-index: 1050;
        }
    }

    .nav-item-m { text-align: center; color: rgba(255,255,255,0.4); text-decoration: none; font-size: 9px; }
    .nav-item-m.active { color: #fff; }
    .nav-item-m i { font-size: 18px; display: block; }
    /* --- PLUS ICON (NEW ORDER BUTTON) --- */
.history-link {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1); /* Transparent White */
    border: 1px solid rgba(255, 255, 255, 0.3); /* සිහින් border එකක් */
    border-radius: 12px;
    color: #ffffff !important;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 0 15px rgba(255, 255, 255, 0.05);
}

.history-link i {
    font-size: 24px; /* Icon එකේ size එක වැඩි කළා */
    line-height: 0;
}

.history-link:hover {
    background: #ffffff; /* Hover කළාම සුදු පාට වෙනවා */
    color: #000000 !important; /* Icon එක කළු වෙනවා */
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 255, 255, 0.3);
}
.back-btn {
            width: 36px; height: 36px; border-radius: 10px;
            background: var(--glass); border: 1px solid var(--glass-border);
            display: flex; align-items: center; justify-content: center;
            color: #fff !important; text-decoration: none;
        }
</style>
</head>
<body>

    <nav class="top-nav">
    <div class="container d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <a href="customer_dashboard.php" class="back-btn"><i class="bi bi-chevron-left"></i></a>
            <div class="ms-3">
                <h5 class="m-0 fw-bold" style="background: linear-gradient(to right, #fff, var(--primary-neon)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Attendance</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Workshop Terminal</small>
            </div>
        </div>

        <div class="right-actions">
            <a href="add_order.php" class="history-link" title="New">
                <i class="bi bi-plus"></i>
            </a>
        </div>
    </div>
</nav>

    <div class="container px-3 mt-2">
        
        <div class="stats-container row g-0">
            <div class="col-6 stat-box">
                <div class="stat-label">Paid Amount</div>
                <div class="stat-value text-success">LKR <?= number_format($summary['total_paid'], 2) ?></div>
            </div>
            <div class="col-6 stat-box">
                <div class="stat-label">Pending Payment</div>
                <div class="stat-value text-danger">LKR <?= number_format($summary['total_unpaid'], 2) ?></div>
            </div>
        </div>

        <form method="GET" class="filter-bar">
            <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?= $filter_status == 'all' ? 'selected' : '' ?>>All Status</option>
                <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="processing" <?= $filter_status == 'processing' ? 'selected' : '' ?>>Processing</option>
                <option value="completed" <?= $filter_status == 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="delivered" <?= $filter_status == 'delivered' ? 'selected' : '' ?>>Delivered</option>
            </select>
            
            <select name="due" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?= $filter_due == 'all' ? 'selected' : '' ?>>All Dates</option>
                <option value="today" <?= $filter_due == 'today' ? 'selected' : '' ?>>Due Today</option>
                <option value="week" <?= $filter_due == 'week' ? 'selected' : '' ?>>This Week</option>
            </select>
        </form>

        <?php if (empty($items)): ?>
            <div class="text-center mt-5 pt-4 opacity-25">
                <i class="bi bi-search display-1"></i>
                <p class="mt-3">No orders found matching filters.</p>
            </div>
        <?php else: ?>
            <?php 
            $last_order_id = null;
            foreach ($items as $item): 
                if ($last_order_id !== $item['order_id']): 
                    if ($last_order_id !== null) echo '</div>';
                    $last_order_id = $item['order_id'];
            ?>
                <div class="order-group">
                    <div class="order-header">
                        <div>
                            <div class="small opacity-50"><?= date('M d, Y', strtotime($item['order_date'])) ?></div>
                        </div>
                        <div class="text-end">
                            <div class="small opacity-50" style="font-size: 9px;">TOTAL</div>
                            <div class="fw-bold text-info">LKR <?= number_format($item['total_price'], 2) ?></div>
                        </div>
                    </div>
            <?php endif; ?>

            <div class="item-card status-<?= strtolower($item['item_status']) ?>">
                <div class="row align-items-center g-0">
                    <div class="col-auto">
                        <div class="img-box">
                            <img src="uploads/services/<?= $item['service_image'] ?>" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3003/3003984.png'">
                        </div>
                    </div>
                    <div class="col px-3">
                        <h6 class="mb-0 fw-bold small text-uppercase"><?= htmlspecialchars($item['service_name']) ?></h6>
                        <div class="status-pill pill-<?= strtolower($item['item_status']) ?>">
                            <?= htmlspecialchars($item['item_status']) ?>
                        </div>
                        <div class="due-date">
                            <i class="bi bi-clock me-1"></i> Due: <?= date('M d', strtotime($item['item_due_date'])) ?>
                        </div>
                    </div>
                    <div class="col-auto text-end">
                        <div class="fw-bold">x<?= $item['quantity'] ?></div>
                    </div>
                </div>
            </div>

            <?php endforeach; ?>
            </div> 
        <?php endif; ?>
    </div>
    <div class="mobile-nav">
    <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
    <a href="customer_dashboard.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
    <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>