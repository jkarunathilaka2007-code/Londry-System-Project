<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- 1. Filter Settings ---
$metric = $_GET['metric'] ?? 'count'; 
$status = $_GET['status'] ?? '';
$view_type = $_GET['view_type'] ?? 'day'; 
$service_id = $_GET['service_id'] ?? '';

$sql_where = "WHERE 1=1";
$prev_sql_where = "WHERE 1=1"; // කලින් කාලය සමඟ සැසඳීමට
$params = [];

if ($view_type == 'day') {
    $sql_group = "DATE_FORMAT(o.created_at, '%h %p')";
    $sql_where .= " AND DATE(o.created_at) = CURDATE()";
    $prev_sql_where .= " AND DATE(o.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    $time_title = "Today vs Yesterday";
} elseif ($view_type == 'month') {
    $sql_group = "DATE_FORMAT(o.created_at, '%d %b')";
    $sql_where .= " AND MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
    $prev_sql_where .= " AND MONTH(o.created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(o.created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
    $time_title = "This Month vs Last Month";
} else {
    $sql_group = "DATE_FORMAT(o.created_at, '%M')";
    $sql_where .= " AND YEAR(o.created_at) = YEAR(CURDATE())";
    $prev_sql_where .= " AND YEAR(o.created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 YEAR))";
    $time_title = "This Year vs Last Year";
}

if ($status) $sql_where .= " AND oi.status = '$status'";
if ($service_id) { $sql_where .= " AND oi.service_id = :sid"; $params[':sid'] = $service_id; }

// --- 2. Data Fetching ---
try {
    // Current KPIs
    $kpi = $pdo->prepare("SELECT COUNT(oi.id) as total_orders, SUM(oi.subtotal) as total_rev FROM order_items oi JOIN orders o ON oi.order_id = o.id $sql_where");
    $kpi->execute($params);
    $current = $kpi->fetch();

    // Previous KPIs (Comparison එක සඳහා)
    $prev_kpi = $pdo->prepare("SELECT COUNT(oi.id) as total_orders, SUM(oi.subtotal) as total_rev FROM order_items oi JOIN orders o ON oi.order_id = o.id $prev_sql_where");
    $prev_kpi->execute($params);
    $previous = $prev_kpi->fetch();

    // Line Chart Data
    $select_col = ($metric == 'price') ? "SUM(oi.subtotal)" : "COUNT(oi.id)";
    $chart_stmt = $pdo->prepare("SELECT $sql_group as label, $select_col as val FROM order_items oi JOIN orders o ON oi.order_id = o.id $sql_where GROUP BY label ORDER BY MIN(o.created_at) ASC");
    $chart_stmt->execute($params);
    $chart_res = $chart_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Donut Chart Data (Top Services)
    $service_stmt = $pdo->prepare("SELECT s.service_name as name, COUNT(oi.id) as count FROM order_items oi JOIN services s ON oi.service_id = s.id JOIN orders o ON oi.order_id = o.id $sql_where GROUP BY name ORDER BY count DESC LIMIT 5");
    $service_stmt->execute($params);
    $service_res = $service_stmt->fetchAll(PDO::FETCH_ASSOC);

    $services_list = $pdo->query("SELECT id, service_name FROM services")->fetchAll();
} catch (PDOException $e) { die($e->getMessage()); }

// Comparison Calculation
$rev_diff = (($previous['total_rev'] ?? 0) > 0) ? (($current['total_rev'] - $previous['total_rev']) / $previous['total_rev']) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Fabricare Analytics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --neon: #00f2fe; --bg: #050508; --card: rgba(255, 255, 255, 0.05); --border: rgba(255, 255, 255, 0.1); }
        body { background: var(--bg); color: #fff; font-family: 'Inter', sans-serif; padding: 70px 10px 90px 10px; overflow-x: hidden; }
        
        /* Navigation */
        .top-nav { background: rgba(0,0,0,0.8); backdrop-filter: blur(15px); border-bottom: 1px solid var(--border); position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; padding: 12px 15px; }
        .bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; height: 65px; background: rgba(0,0,0,0.9); border-top: 1px solid var(--border); display: none; justify-content: space-around; align-items: center; z-index: 1000; }
        @media (max-width: 768px) { .bottom-nav { display: flex; } }

        /* Cards & UI Elements */
        .glass-card { background: var(--card); border: 1px solid var(--border); border-radius: 20px; padding: 15px; margin-bottom: 15px; backdrop-filter: blur(10px); }
        .seg-control { display: flex; background: #000; border-radius: 12px; padding: 4px; border: 1px solid var(--border); margin-bottom: 15px; }
        .seg-btn { flex: 1; text-align: center; padding: 8px; font-size: 0.7rem; color: #666; text-decoration: none; border-radius: 8px; font-weight: bold; }
        .seg-btn.active { background: var(--neon); color: #000; }

        .kpi-val { font-size: 1.4rem; font-weight: 800; margin: 0; }
        .kpi-sub { font-size: 0.65rem; text-transform: uppercase; color: #888; letter-spacing: 0.5px; }
        .percent { font-size: 0.75rem; font-weight: bold; }

        .form-select-glass { background: #000 !important; border: 1px solid var(--border) !important; color: #fff !important; font-size: 0.8rem; border-radius: 10px; }
        .chart-box { height: 250px; width: 100%; }
    </style>
</head>
<body>

<nav class="top-nav d-flex justify-content-between align-items-center">
    <a href="admin_dashboard.php" class="text-white"><i class="bi bi-chevron-left fs-4"></i></a>
    <h6 class="m-0 fw-bold">MASTER <span class="text-info">INSIGHTS</span></h6>
    <div class="dropdown">
        <button class="btn btn-sm btn-dark border-secondary text-info dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <?= strtoupper($metric) ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-dark">
            <li><a class="dropdown-item" href="?metric=count&view_type=<?= $view_type ?>">Order Count</a></li>
            <li><a class="dropdown-item" href="?metric=price&view_type=<?= $view_type ?>">Revenue</a></li>
        </ul>
    </div>
</nav>

<div class="container">
    <div class="seg-control">
        <a href="?view_type=day&metric=<?= $metric ?>" class="seg-btn <?= $view_type=='day'?'active':'' ?>">DAY</a>
        <a href="?view_type=month&metric=<?= $metric ?>" class="seg-btn <?= $view_type=='month'?'active':'' ?>">MONTH</a>
        <a href="?view_type=year&metric=<?= $metric ?>" class="seg-btn <?= $view_type=='year'?'active':'' ?>">YEAR</a>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="glass-card h-100">
                <p class="kpi-sub mb-1">Total Revenue</p>
                <h3 class="kpi-val">Rs.<?= number_format($current['total_rev'] ?? 0, 0) ?></h3>
                <span class="percent <?= $rev_diff >= 0 ? 'text-success' : 'text-danger' ?>">
                    <i class="bi bi-caret-<?= $rev_diff >= 0 ? 'up' : 'down' ?>-fill"></i> <?= round(abs($rev_diff), 1) ?>%
                </span>
            </div>
        </div>
        <div class="col-6">
            <div class="glass-card h-100">
                <p class="kpi-sub mb-1">Total Orders</p>
                <h3 class="kpi-val"><?= $current['total_orders'] ?? 0 ?></h3>
                <span class="text-white-50 small" style="font-size: 0.7rem;">Items Processed</span>
            </div>
        </div>
    </div>

    <div class="glass-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="small fw-bold text-info text-uppercase"><?= $time_title ?></span>
            <i class="bi bi-activity text-info"></i>
        </div>
        <div class="chart-box">
            <canvas id="lineChart"></canvas>
        </div>
    </div>

    <div class="glass-card">
        <span class="small fw-bold text-uppercase opacity-50 d-block mb-3">Service Distribution</span>
        <div class="row align-items-center">
            <div class="col-6">
                <canvas id="donutChart" style="max-height: 140px;"></canvas>
            </div>
            <div class="col-6">
                <?php foreach($service_res as $index => $s): ?>
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-white-50" style="font-size: 0.7rem;"><?= $s['name'] ?></span>
                        <span class="fw-bold"><?= $s['count'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="glass-card">
        <form class="row g-2">
            <input type="hidden" name="metric" value="<?= $metric ?>">
            <input type="hidden" name="view_type" value="<?= $view_type ?>">
            <div class="col-6">
                <select name="status" class="form-select-glass form-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $status=='pending'?'selected':'' ?>>Pending</option>
                    <option value="completed" <?= $status=='completed'?'selected':'' ?>>Completed</option>
                    <option value="delivered" <?= $status=='delivered'?'selected':'' ?>>Delivered</option>
                </select>
            </div>
            <div class="col-6">
                <select name="service_id" class="form-select-glass form-select" onchange="this.form.submit()">
                    <option value="">All Services</option>
                    <?php foreach($services_list as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $service_id==$s['id']?'selected':'' ?>><?= $s['service_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="bottom-nav">
    <a href="index.php" class="text-white-50 text-decoration-none text-center"><i class="bi bi-house-door fs-4 d-block"></i><small style="font-size:9px">Home</small></a>
    <a href="admin_dashboard.php" class="text-white-50 text-decoration-none text-center"><i class="bi bi-person fs-4 d-block"></i><small style="font-size:9px">Profile</small></a>
    <a href="logout.php" class="text-white-50 text-decoration-none text-center"><i class="bi bi-power fs-4 d-block"></i><small style="font-size:9px">Logout</small></a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// --- Line Chart Configuration ---
const ctxL = document.getElementById('lineChart').getContext('2d');
const grad = ctxL.createLinearGradient(0, 0, 0, 250);
grad.addColorStop(0, 'rgba(0, 242, 254, 0.4)');
grad.addColorStop(1, 'rgba(0, 242, 254, 0)');

new Chart(ctxL, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($chart_res, 'label')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($chart_res, 'val')) ?>,
            borderColor: '#00f2fe',
            borderWidth: 3,
            fill: true,
            backgroundColor: grad,
            tension: 0.4,
            pointRadius: 2
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#555', font: { size: 9 } } },
            x: { grid: { display: false }, ticks: { color: '#555', font: { size: 9 } } }
        }
    }
});

// --- Donut Chart Configuration ---
const ctxD = document.getElementById('donutChart').getContext('2d');
new Chart(ctxD, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($service_res, 'name')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($service_res, 'count')) ?>,
            backgroundColor: ['#00f2fe', '#4facfe', '#7367f0', '#28c76f', '#ff9f43'],
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        cutout: '70%',
        plugins: { legend: { display: false } }
    }
});
</script>
</body>
</html>