<?php
session_start();
require 'config.php';

// සෙෂන් පරීක්ෂාව
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ෆිල්ටර් දත්ත ලබා ගැනීම (Default අගයන් සමඟ)
$selected_year = $_GET['year'] ?? date('Y');
$month1 = $_GET['month1'] ?? date('Y-m'); // වර්තමාන මාසය default එක ලෙස
$month2 = $_GET['month2'] ?? '';

/**
 * අදාළ මාසයට අදාළ දත්ත (Qty & Amount) එකතු කර ලබා ගන්නා Function එක
 */
function fetchMonthlyStats($pdo, $uid, $target_month) {
    if (empty($target_month)) return [];
    
    $sql = "SELECT 
                DAY(s.settled_at) as day, 
                SUM(oi.quantity) as qty, 
                SUM(s.total_amount) as amt
            FROM settlements s
            JOIN order_items oi ON FIND_IN_SET(oi.id, s.order_items_ids)
            WHERE s.admin_id = :uid 
            AND DATE_FORMAT(s.settled_at, '%Y-%m') = :m
            GROUP BY DAY(s.settled_at)
            ORDER BY day ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['uid' => $uid, 'm' => $target_month]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// දත්ත ලබා ගැනීම
$data1 = fetchMonthlyStats($pdo, $user_id, $month1);
$data2 = fetchMonthlyStats($pdo, $user_id, $month2);

// මුළු එකතුවන් ගණනය කිරීම (Summary Cards සඳහා)
$total_qty = 0; 
$total_amt = 0;

foreach ($data1 as $r) { $total_qty += $r['qty']; $total_amt += $r['amt']; }
foreach ($data2 as $r) { $total_qty += $r['qty']; $total_amt += $r['amt']; }

// ප්‍රස්තාරය සඳහා දත්ත සකස් කිරීම (දවස් 1 සිට 31 දක්වා)
$labels = range(1, 31);
$m1_values = array_fill(0, 31, 0);
$m2_values = array_fill(0, 31, 0);

foreach ($data1 as $r) { $m1_values[$r['day'] - 1] = $r['qty']; }
foreach ($data2 as $r) { $m2_values[$r['day'] - 1] = $r['qty']; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Employee Analytics | Fabricare</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
   <style>
    :root { 
        --primary-neon: #00f2fe; 
        --dark-deep: #080808; 
        --glass: rgba(255, 255, 255, 0.05);
        --glass-border: rgba(255, 255, 255, 0.12);
        --accent-white: #ffffff;
    }

    body { 
        background-color: var(--dark-deep);
        background-image: linear-gradient(rgba(8, 8, 8, 0.98), rgba(8, 8, 8, 0.98)), 
                          url('uploads/resources/bg2.jpg');
        background-size: cover; background-position: center; background-attachment: fixed;
        color: #fff; font-family: 'Plus Jakarta Sans', sans-serif; 
        min-height: 100vh; padding: 20px;
    }

    /* --- GLASS CARDS --- */
    .glass-card { 
        background: var(--glass); backdrop-filter: blur(15px);
        border: 1px solid var(--glass-border); border-radius: 20px; 
        padding: 22px; transition: 0.3s; height: 100%;
    }

    /* --- TITLES & LABELS --- */
    h2.tracking-tighter { color: var(--accent-white); }
    .text-info { color: var(--primary-neon) !important; }
    .stat-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1.5px; color: rgba(255,255,255,0.5); font-weight: 600; }
    .stat-value { font-size: 2rem; font-weight: 800; color: var(--accent-white); }

    /* --- FORMS & INPUTS --- */
    .form-control, .form-select { 
        background: rgba(255,255,255,0.07) !important; 
        border: 1px solid var(--glass-border) !important; 
        color: #fff !important; border-radius: 12px; padding: 10px 15px;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--primary-neon) !important; box-shadow: 0 0 10px rgba(0, 242, 254, 0.2);
    }

    /* --- FILTER BUTTON --- */
    .btn-filter {
        background: var(--accent-white); border: none; color: #000;
        font-weight: 700; border-radius: 12px; transition: 0.3s;
    }
    .btn-filter:hover { background: var(--primary-neon); transform: translateY(-2px); }

    /* --- SUMMARY CARDS --- */
    .border-info { border-left: 4px solid var(--primary-neon) !important; }
    .border-warning { border-left: 4px solid var(--accent-white) !important; }
    .text-warning { color: var(--accent-white) !important; }

    /* --- CHART CONTAINER --- */
    .chart-container { height: 380px; margin-top: 20px; position: relative; }

    /* --- BACK BUTTON --- */
    .btn-outline-light {
        border: 1px solid var(--glass-border); background: var(--glass);
        color: #fff; font-weight: 600; font-size: 0.8rem;
    }
    .btn-outline-light:hover { background: #fff; color: #000; }

    @media (max-width: 768px) {
        body { padding: 15px; }
        .stat-value { font-size: 1.6rem; }
        .chart-container { height: 300px; }
    }
</style>
</head>
<body>

<div class="container-fluid max-width-lg">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold m-0 tracking-tighter">Work <span class="text-info">Analytics</span></h2>
            <p class="text-white-50 small m-0">Compare performance and track financial growth</p>
        </div>
        <a href="employee_dashboard.php" class="btn btn-outline-light rounded-pill px-4 btn-sm">Back to Home</a>
    </div>

    <div class="glass-card mb-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-6 col-md-2">
                <label class="stat-label mb-2">Year</label>
                <select name="year" class="form-select">
                    <?php 
                    $curr = date('Y');
                    for($y=$curr-2; $y<=$curr; $y++) {
                        echo "<option value='$y' ".($selected_year == $y ? 'selected':'').">$y</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="stat-label mb-2">Primary Month</label>
                <input type="month" name="month1" class="form-control" value="<?= $month1 ?>" required>
            </div>
            <div class="col-12 col-md-3">
                <label class="stat-label mb-2">Comparison Month (Optional)</label>
                <input type="month" name="month2" class="form-control" value="<?= $month2 ?>">
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-filter w-100 py-2">Apply Filters</button>
                <a href="emp_analytics.php" class="btn btn-dark rounded-3 px-3 py-2"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="glass-card border-start border-info border-4">
                <div class="stat-label">Total Quantity</div>
                <div class="stat-value text-info"><?= number_format($total_qty) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card border-start border-warning border-4">
                <div class="stat-label">Total Amount (Settled)</div>
                <div class="stat-value text-warning"><small class="fs-5">LKR</small> <?= number_format($total_amt, 2) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card">
                <div class="stat-label">Comparison Mode</div>
                <div class="mt-2">
                    <?php if($month2): ?>
                        <span class="badge bg-info text-dark">Active</span> <small class="opacity-50 ms-2">Comparing <?= $month1 ?> vs <?= $month2 ?></small>
                    <?php else: ?>
                        <span class="badge bg-secondary">Single Month</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="glass-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="stat-label m-0">Daily Performance Trend (Quantity)</h6>
            <div class="d-flex gap-3 small">
                <span class="d-flex align-items-center"><i class="bi bi-circle-fill me-1" style="color: var(--cyan);"></i> Month 1</span>
                <?php if($month2): ?>
                <span class="d-flex align-items-center"><i class="bi bi-circle-fill me-1" style="color: var(--magenta);"></i> Month 2</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="multiChart"></canvas>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('multiChart').getContext('2d');

// Gradient Colors
let grad1 = ctx.createLinearGradient(0, 0, 0, 400);
grad1.addColorStop(0, 'rgba(0, 242, 254, 0.2)');
grad1.addColorStop(1, 'rgba(0, 242, 254, 0)');

let grad2 = ctx.createLinearGradient(0, 0, 0, 400);
grad2.addColorStop(0, 'rgba(244, 0, 255, 0.2)');
grad2.addColorStop(1, 'rgba(244, 0, 255, 0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [
            {
                label: '<?= $month1 ?>',
                data: <?= json_encode($m1_values) ?>,
                borderColor: '#00f2fe',
                backgroundColor: grad1,
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 4,
                pointHoverRadius: 7
            },
            <?php if(!empty($month2)): ?>
            {
                label: '<?= $month2 ?>',
                data: <?= json_encode($m2_values) ?>,
                borderColor: '#f400ff',
                backgroundColor: grad2,
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 4,
                pointHoverRadius: 7
            }
            <?php endif; ?>
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                mode: 'index',
                intersect: false,
                backgroundColor: 'rgba(0,0,0,0.8)',
                titleColor: '#00f2fe'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(255,255,255,0.05)' },
                ticks: { color: '#888' }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#888' },
                title: { display: true, text: 'Day of Month', color: '#444' }
            }
        }
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>