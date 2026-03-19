<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: login.php");
    exit();
}

$emp_id = $_SESSION['user_id'];
$target_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$display_month = date('F Y', strtotime($target_month));

try {
    // 1. Employee Data
    $stmt = $pdo->prepare("SELECT e.*, s.basic_salary, s.bonus, s.service_commissions 
                           FROM employees e 
                           LEFT JOIN salary_structures s ON e.designation = s.designation 
                           WHERE e.id = ?");
    $stmt->execute([$emp_id]);
    $employee = $stmt->fetch();

    $basic = (float)($employee['basic_salary'] ?? 0);
    $bonus = (float)($employee['bonus'] ?? 0);
    $comm_rates = json_decode($employee['service_commissions'] ?? '[]', true);

    // 2. Monthly Work Data
    $stmt = $pdo->prepare("
        SELECT oi.service_id, SUM(oi.quantity) as qty, ser.service_name 
        FROM settlements s
        JOIN order_items oi ON FIND_IN_SET(oi.id, s.order_items_ids)
        JOIN services ser ON oi.service_id = ser.id
        WHERE s.admin_id = :uid AND DATE_FORMAT(s.settled_at, '%Y-%m') = :month
        GROUP BY oi.service_id
    ");
    $stmt->execute(['uid' => $emp_id, 'month' => $target_month]);
    $works = $stmt->fetchAll();

    $total_commission = 0;
    foreach ($works as $w) { $total_commission += ($w['qty'] * (float)($comm_rates[$w['service_id']] ?? 0)); }
    $net_salary = $basic + $bonus + $total_commission;

    // 3. History Retrieval
    $historyStmt = $pdo->prepare("SELECT month_year, amount, paid_at FROM salary_payments WHERE employee_id = ? ORDER BY month_year DESC");
    $historyStmt->execute([$emp_id]);
    $history = $historyStmt->fetchAll();

    $checkPaid = $pdo->prepare("SELECT paid_at FROM salary_payments WHERE employee_id = ? AND month_year = ?");
    $checkPaid->execute([$emp_id, $target_month]);
    $payment_info = $checkPaid->fetch();

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Payroll Invoice | Fabricare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --dark-deep: #0f172a;
            --neon: #38bdf8; 
            --glass: rgba(255, 255, 255, 0.03); 
            --border: rgba(255, 255, 255, 0.1); 
        }

        body {
        background-color: var(--bg-dark);
        background-image: linear-gradient(rgba(8, 8, 8, 0.97), rgba(8, 8, 8, 0.97)), 
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

        /* History Overlay */
        #historyOverlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85); backdrop-filter: blur(20px);
            z-index: 2000; display: none; padding: 15px;
        }
        .history-card {
            max-width: 400px; margin: 30px auto;
            background: #1e293b; border: 1px solid var(--border);
            border-radius: 12px; padding: 25px; box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        }

        /* Invoice Container */
        .invoice-container {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(30px);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 40px;
            max-width: 850px;
            margin: auto;
            position: relative;
        }

        .invoice-title { font-size: clamp(24px, 5vw, 38px); font-weight: 700; color: #fff; margin-bottom: 30px; text-transform: uppercase; letter-spacing: -1px; }
        .info-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1.2px; color: var(--neon); font-weight: 700; margin-bottom: 4px; }
        .info-value { font-size: 13px; color: #cbd5e1; margin-bottom: 20px; line-height: 1.5; }

        /* Responsive Table Replacement */
        .items-header { 
            background: rgba(255, 255, 255, 0.03); 
            border-top: 1px solid var(--border); 
            border-bottom: 1px solid var(--border);
            padding: 10px 15px; font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--neon);
        }
        .item-row { border-bottom: 1px solid rgba(255,255,255,0.05); padding: 12px 15px; font-size: 13px; }

        .grand-total { font-size: clamp(20px, 4vw, 28px); font-weight: 700; color: var(--neon); margin-top: 5px; }

        .btn-tool { background: var(--glass); border: 1px solid var(--border); color: #fff; font-size: 11px; font-weight: 600; padding: 8px 12px; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center; }
        .btn-tool:hover { background: var(--neon); color: #000; }

        /* Mobile specific fixes */
        @media (max-width: 576px) {
            .invoice-container { padding: 20px; border-radius: 16px; }
            .items-header { display: none; } /* Hide header on mobile for cleaner look */
            .item-row { padding: 15px 0; }
            .item-row > div { margin-bottom: 5px; }
            .item-row .qty-label::before { content: 'Qty: '; color: var(--neon); font-size: 10px; }
            .item-row .rate-label::before { content: 'Rate: '; color: var(--neon); font-size: 10px; }
            .item-row .text-end { text-align: left !important; font-size: 14px; margin-top: 5px; }
        }

        @media print { .no-print { display: none !important; } }
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

@media (max-width: 576px) {
        .clock { font-size: 2.5rem; }
        .btn-punch { width: 130px; height: 130px; font-size: 0.8rem; }
        .attendance-card { padding: 25px 10px; margin-top: 15px; }
        .top-nav h5 { font-size: 1rem; }
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
    <nav class="top-nav">
    <div class="container d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <a href="employee_dashboard.php" class="back-btn"><i class="bi bi-chevron-left"></i></a>
            <div class="ms-3">
                <h5 class="m-0 fw-bold" style="background: linear-gradient(to right, #fff, var(--primary-neon)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Salary</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Workshop Terminal</small>
            </div>
        </div>
    </div>
</nav>

<div id="historyOverlay" onclick="closeHistory(event)">
    <div class="history-card shadow-lg">
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-white border-opacity-10 pb-2">
            <h6 class="m-0 text-white fw-bold"><i class="bi bi-clock-history me-2 text-info"></i>History</h6>
            <button onclick="toggleHistory()" class="btn-close btn-close-white" style="font-size: 10px;"></button>
        </div>
        <div class="overflow-auto" style="max-height: 350px;">
            <?php foreach($history as $h): ?>
                <a href="?month=<?= $h['month_year'] ?>" class="text-decoration-none d-block mb-2">
                    <div class="p-3 rounded border border-white border-opacity-5" style="background: rgba(255,255,255,0.02);">
                        <div class="d-flex justify-content-between small">
                            <span class="text-white"><?= date('M Y', strtotime($h['month_year'])) ?></span>
                            <span class="text-info fw-bold">LKR <?= number_format($h['amount'], 2) ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="container py-2">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3 no-print mx-auto" style="max-width: 850px;">
        <div class="d-flex gap-2">
            <button onclick="toggleHistory()" class="btn-tool"><i class="bi bi-clock-history me-1"></i> HISTORY</button>
        </div>
        <button id="downloadBtn" class="btn btn-info fw-bold btn-sm px-3 rounded-1 w-xs-100" style="background: var(--neon); color:#000; border:none;">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </button>
    </div>

    <div class="invoice-container shadow-2xl" id="invoice">
        <div class="invoice-title">Invoice</div>

        <div class="row">
            <div class="col-md-6 col-12 mb-3">
                <div class="info-label">From</div>
                <div class="info-value">
                    <strong>FABRICARE PVT LTD</strong><br>
                    Payroll Department, Colombo<br>
                    contact@fabricare.lk
                </div>

                <div class="info-label">Billed To</div>
                <div class="info-value">
                    <strong><?= htmlspecialchars($employee['full_name']) ?></strong><br>
                    <?= htmlspecialchars($employee['designation']) ?><br>
                    ID: EMP-<?= str_pad($employee['id'], 4, '0', STR_PAD_LEFT) ?>
                </div>
            </div>
            <div class="col-md-6 col-12 text-md-end text-start mb-4">
                <div class="info-label">Invoice Number</div>
                <div class="info-value">#INV-<?= str_replace('-', '', $target_month) ?>-<?= $employee['id'] ?></div>

                <div class="info-label">Date of Issue</div>
                <div class="info-value"><?= date('m/d/Y') ?></div>

                <div class="info-label">Reporting Period</div>
                <div class="info-value fw-bold text-white"><?= $display_month ?></div>
                
                <div class="mt-2">
                    <span class="badge border <?= $payment_info ? 'text-success border-success' : 'text-warning border-warning' ?> px-3 py-2" style="font-size: 9px; letter-spacing: 1px;">
                        <?= $payment_info ? 'PAID / SETTLED' : 'PAYMENT PENDING' ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="items-header row mx-0 mt-2">
            <div class="col-6">Description</div>
            <div class="col-2 text-center">Rate</div>
            <div class="col-2 text-center">Qty</div>
            <div class="col-2 text-end">Amount</div>
        </div>

        <div class="item-row row mx-0">
            <div class="col-md-6 col-12 fw-bold text-white">Monthly Basic Salary</div>
            <div class="col-md-2 col-6 text-md-center rate-label text-white-50 small"><?= number_format($basic, 2) ?></div>
            <div class="col-md-2 col-6 text-md-center qty-label text-white-50 small">1.00</div>
            <div class="col-md-2 col-12 text-md-end text-white fw-bold">LKR <?= number_format($basic, 2) ?></div>
        </div>

        <?php if($bonus > 0): ?>
        <div class="item-row row mx-0">
            <div class="col-md-6 col-12 fw-bold text-white">Performance Bonus</div>
            <div class="col-md-2 col-6 text-md-center rate-label text-white-50 small"><?= number_format($bonus, 2) ?></div>
            <div class="col-md-2 col-6 text-md-center qty-label text-white-50 small">1.00</div>
            <div class="col-md-2 col-12 text-md-end text-white fw-bold">LKR <?= number_format($bonus, 2) ?></div>
        </div>
        <?php endif; ?>

        <?php foreach($works as $w): 
            $rate = (float)($comm_rates[$w['service_id']] ?? 0);
            $amt = $w['qty'] * $rate;
        ?>
        <div class="item-row row mx-0">
            <div class="col-md-6 col-12 text-white-50"><?= htmlspecialchars($w['service_name']) ?> Commission</div>
            <div class="col-md-2 col-6 text-md-center rate-label text-white-50 small"><?= number_format($rate, 2) ?></div>
            <div class="col-md-2 col-6 text-md-center qty-label text-white-50 small"><?= $w['qty'] ?></div>
            <div class="col-md-2 col-12 text-md-end text-white fw-bold">LKR <?= number_format($amt, 2) ?></div>
        </div>
        <?php endforeach; ?>

        <div class="row mt-4">
            <div class="col-md-7 d-none d-md-block">
                <div class="info-label">Verification</div>
                <p class="text-white-50 small">This is a system-generated document and does not require a physical signature.</p>
            </div>
            <div class="col-md-5 col-12 text-end">
                <div class="d-flex justify-content-between mb-1 small">
                    <span class="text-white-50">SUBTOTAL</span>
                    <span>LKR <?= number_format($net_salary, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-1 small">
                    <span class="text-white-50">DEDUCTIONS</span>
                    <span>0.00</span>
                </div>
                <div class="border-top border-secondary pt-3 mt-3">
                    <div class="info-label" style="margin-bottom:0">NET REMITTANCE</div>
                    <div class="grand-total">LKR <?= number_format($net_salary, 2) ?></div>
                </div>
            </div>
        </div>

        <div class="mt-5 pt-3 border-top border-white border-opacity-5 text-center">
            <div class="info-label" style="font-size: 8px; opacity: 0.5;">Fabricare Automation • <?= date('Y') ?></div>
        </div>
    </div>
</div>
<div class="mobile-nav">
    <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
    <a href="employee_dashboard.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
    <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
</div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    function toggleHistory() {
        const overlay = document.getElementById('historyOverlay');
        overlay.style.display = (overlay.style.display === 'block') ? 'none' : 'block';
    }
    function closeHistory(e) {
        if(e.target.id === 'historyOverlay') toggleHistory();
    }

    document.getElementById('downloadBtn').addEventListener('click', function () {
        const element = document.getElementById('invoice');
        const opt = {
            margin: [0.3, 0.3],
            filename: 'Invoice_<?= $target_month ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, backgroundColor: '#0f172a', useCORS: true },
            jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    });
</script>
</body>
</html>