<?php
session_start();
require 'config.php';

// Customer කෙනෙක්ද කියලා check කිරීම
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. මාසික වියදම් ලබාගැනීම (Chart එක සඳහා)
try {
    $stmt = $pdo->prepare("SELECT MONTHNAME(created_at) as month, SUM(total_price) as total 
                           FROM orders 
                           WHERE customer_id = ? 
                           GROUP BY MONTH(created_at)
                           ORDER BY MONTH(created_at) ASC");
    $stmt->execute([$user_id]);
    $analyticsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $analyticsData = [];
}

// Chart එකට data සකස් කිරීම
$months = [];
$totals = [];
if (count($analyticsData) > 0) {
    foreach($analyticsData as $row) {
        $months[] = $row['month'];
        $totals[] = (float)$row['total'];
    }
} else {
    $months = [date('F')];
    $totals = [0];
}

// 2. දැනට පවතින (Active) Orders ගණන ගැනීම
$stmt_active = $pdo->prepare("SELECT COUNT(*) FROM order_items 
                             WHERE status != 'completed' 
                             AND order_id IN (SELECT id FROM orders WHERE customer_id = ?)");
$stmt_active->execute([$user_id]);
$active_orders_count = $stmt_active->fetchColumn();

// 3. මුළු වියදම (Lifetime Spent)
$total_spent = array_sum($totals);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Analytics | Fabricare</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --bg-dark: #080808;
            --glass: rgba(255, 255, 255, 0.04);
            --glass-border: rgba(255, 255, 255, 0.12);
            --accent-neon: #00f2fe;
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
            padding-bottom: 100px;
        }

        .top-nav {
            background: rgba(10, 10, 10, 0.9);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            padding: 12px 0;
            position: sticky; top: 0; z-index: 1000;
        }

        .glass-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .stat-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.5; margin-bottom: 5px; }
        .stat-value { font-size: 1.4rem; font-weight: 800; color: var(--accent-neon); }

        .back-btn {
            width: 35px; height: 35px; border-radius: 10px;
            background: var(--glass); border: 1px solid var(--glass-border);
            display: flex; align-items: center; justify-content: center;
            color: #fff; text-decoration: none;
        }
        /* --- MOBILE NAV --- */
    .mobile-nav {
        position: fixed; bottom: 0; left: 0; right: 0;
        height: 65px; background: rgba(10, 10, 10, 0.98);
        backdrop-filter: blur(25px); border-radius: 20px 20px 0 0;
        border-top: 1px solid var(--glass-border);
        display: flex; justify-content: space-around; align-items: center; z-index: 1050;
    }

    @media (min-width: 992px) { .mobile-nav { display: none !important; } }

    .nav-item-m { text-align: center; color: rgba(255,255,255,0.3); text-decoration: none; font-size: 9px; flex: 1; }
    .nav-item-m.active { color: #fff; }
    .nav-item-m i { font-size: 20px; display: block; }
    </style>
</head>
<body>

<nav class="top-nav mb-4">
    <div class="container d-flex align-items-center">
        <a href="customer_dashboard.php" class="back-btn"><i class="bi bi-chevron-left"></i></a>
        <div class="ms-3">
            <h5 class="m-0 fw-bold">Spending Insights</h5>
            <small class="opacity-50">Analytics Dashboard</small>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row g-3 mb-2">
        <div class="col-6">
            <div class="glass-card">
                <div class="stat-label">Total Spent</div>
                <div class="stat-value">LKR <?= number_format($total_spent, 2) ?></div>
            </div>
        </div>
        <div class="col-6">
            <div class="glass-card">
                <div class="stat-label">Active Items</div>
                <div class="stat-value"><?= str_pad($active_orders_count, 2, "0", STR_PAD_LEFT) ?></div>
            </div>
        </div>
    </div>

    <div class="glass-card">
        <h6 class="fw-bold mb-4" style="font-size: 0.8rem; opacity: 0.7;">MONTHLY EXPENDITURE (LKR)</h6>
        <div style="width: 100%; height: 250px;">
            <canvas id="spendingChart"></canvas>
        </div>
    </div>
</div>
<div class="mobile-nav">
    <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
    <a href="customer_dashboard.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
    <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
</div>

<script>
    const ctx = document.getElementById('spendingChart').getContext('2d');
    
    // Gradient නිර්මාණය
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(0, 242, 254, 0.4)');
    gradient.addColorStop(1, 'rgba(0, 242, 254, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($months) ?>,
            datasets: [{
                label: 'Monthly Spending',
                data: <?= json_encode($totals) ?>,
                borderColor: '#00f2fe',
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointBackgroundColor: '#00f2fe',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: { color: 'rgba(255,255,255,0.4)', font: { size: 10 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: 'rgba(255,255,255,0.4)', font: { size: 10 } }
                }
            }
        }
    });
</script>

</body>
</html>