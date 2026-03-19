<?php
session_start();
require 'config.php';

// Admin Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$target_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$display_month = date('F Y', strtotime($target_month));
$prev_month = date('Y-m', strtotime($target_month . " -1 month"));
$next_month = date('Y-m', strtotime($target_month . " +1 month"));

try {
    $employees = $pdo->query("SELECT * FROM employees WHERE status = 'active'")->fetchAll();
    
    $services = [];
    $srv_raw = $pdo->query("SELECT id, service_name FROM services")->fetchAll();
    foreach($srv_raw as $s) { $services[$s['id']] = $s['service_name']; }

    $structures_raw = $pdo->query("SELECT * FROM salary_structures")->fetchAll();
    $structures = [];
    foreach ($structures_raw as $s) {
        $structures[$s['designation']] = [
            'basic' => (float)$s['basic_salary'],
            'bonus' => (float)$s['bonus'],
            'rates' => json_decode($s['service_commissions'], true)
        ];
    }

    $paid_list = $pdo->prepare("SELECT employee_id FROM salary_payments WHERE month_year = ?");
    $paid_list->execute([$target_month]);
    $paid_ids = $paid_list->fetchAll(PDO::FETCH_COLUMN);

    $salary_report = [];
    foreach ($employees as $emp) {
        $emp_id = $emp['id'];
        $desig = $emp['designation'];
        if (!isset($structures[$desig])) continue;

        $struct = $structures[$desig];
        $total_commission = 0; $work_details = [];

        $stmt = $pdo->prepare("
            SELECT oi.service_id, SUM(oi.quantity) as qty 
            FROM settlements s
            JOIN order_items oi ON FIND_IN_SET(oi.id, s.order_items_ids)
            WHERE s.admin_id = :uid AND DATE_FORMAT(s.settled_at, '%Y-%m') = :month
            GROUP BY oi.service_id
        ");
        $stmt->execute(['uid' => $emp_id, 'month' => $target_month]);
        $works = $stmt->fetchAll();

        foreach ($works as $w) {
            $sid = $w['service_id']; $qty = (int)$w['qty'];
            $rate = (float)($struct['rates'][$sid] ?? 0);
            $sub_total = $qty * $rate;
            if ($qty > 0) {
                $work_details[] = ['service' => $services[$sid] ?? 'Unknown', 'qty' => $qty, 'rate' => $rate, 'total' => $sub_total];
                $total_commission += $sub_total;
            }
        }

        $salary_report[] = [
            'id' => $emp_id, 'name' => (string)$emp['full_name'], 'nic' => (string)$emp['nic_number'],
            'phone' => (string)$emp['phone'], 'image' => (string)$emp['profile_image'], 'designation' => (string)$desig, 
            'basic' => (float)$struct['basic'], 'commission' => (float)$total_commission, 'bonus' => (float)$struct['bonus'], 
            'net' => (float)($struct['basic'] + $total_commission + $struct['bonus']),
            'month' => (string)$display_month, 'is_paid' => in_array($emp_id, $paid_ids),
            'work_breakdown' => $work_details
        ];
    }
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Payroll Portal | Fabricare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    
</head>
<style>
    :root { 
        --bg-dark: #080808; 
        --glass: rgba(255, 255, 255, 0.05); 
        --glass-border: rgba(255, 255, 255, 0.1);
        --primary-neon: #00f2fe;
        --font-main: 'Plus Jakarta Sans', sans-serif;
    }
    
    body { 
        background-color: var(--bg-dark);
        background-image: linear-gradient(rgba(8, 8, 8, 0.9), rgba(8, 8, 8, 0.9)), url('uploads/resources/bg2.jpg');
        background-size: cover; background-attachment: fixed;
        color: #ffffff; font-family: var(--font-main); 
        margin: 0; padding-top: 85px; padding-bottom: 30px;
    }

    /* --- TOP NAV (Code 1 Standard) --- */
    .top-nav {
        background: rgba(10, 10, 10, 0.85); backdrop-filter: blur(30px);
        border-bottom: 1px solid var(--glass-border);
        padding: 12px 0; position: fixed; top: 0; width: 100%; z-index: 1000;
    }

    .back-btn {
        width: 38px; height: 38px; border-radius: 10px;
        background: var(--glass); border: 1px solid var(--glass-border);
        display: flex; align-items: center; justify-content: center;
        color: #fff !important; text-decoration: none; transition: 0.3s;
    }

    /* --- MONTH SWITCHER --- */
    .month-switcher {
        background: var(--glass); border: 1px solid var(--glass-border);
        padding: 8px 15px; border-radius: 50px; display: flex; align-items: center; gap: 15px;
    }
    .month-switcher a { color: var(--primary-neon) !important; text-decoration: none; font-size: 1.2rem; }
    .month-switcher span { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }

    /* --- PAYROLL TABLE (Code 1 Style) --- */
    .main-table { width: 100%; border-collapse: separate; border-spacing: 0 12px; }
    .main-table thead th { 
        padding: 10px 20px; font-size: 0.65rem; color: rgba(255,255,255,0.4); 
        text-transform: uppercase; letter-spacing: 1.5px; font-weight: 800;
    }

    .main-table tr { background: var(--glass); backdrop-filter: blur(15px); transition: 0.3s; }
    .main-table tr:hover { transform: translateY(-2px); background: rgba(255,255,255,0.08); }

    .main-table td { 
        padding: 15px 20px; vertical-align: middle; 
        border-top: 1px solid var(--glass-border); border-bottom: 1px solid var(--glass-border); 
    }
    .main-table td:first-child { border-left: 1px solid var(--glass-border); border-radius: 18px 0 0 18px; }
    .main-table td:last-child { border-right: 1px solid var(--glass-border); border-radius: 0 18px 18px 0; }

    /* --- BUTTONS & IMAGES --- */
    .emp-img { width: 45px; height: 45px; border-radius: 12px; object-fit: cover; border: 1px solid var(--glass-border); }
    
    .btn-icon-sq {
        width: 38px; height: 38px; border-radius: 10px;
        background: var(--glass); border: 1px solid var(--glass-border);
        display: inline-flex; align-items: center; justify-content: center; transition: 0.3s;
    }

    .btn-neon {
        background: #ffffff; color: #000 !important; font-weight: 800; border: none;
        border-radius: 10px; padding: 8px 20px; font-size: 0.7rem; text-transform: uppercase;
    }

    .badge.bg-success {
        background: rgba(52, 199, 89, 0.1) !important; color: #34c759 !important;
        border: 1px solid rgba(52, 199, 89, 0.2); padding: 8px 15px; border-radius: 10px; font-size: 0.7rem;
    }

    /* --- MODAL (INVOICE BOX) FIX --- */
    #invoice-box { padding: 40px; background: #0a0a0c; border-radius: 24px; color: #fff; }
    .modal-content { border: 1px solid var(--glass-border) !important; }

    /* --- MOBILE RESPONSIVE --- */
    @media (max-width: 768px) {
        body { padding-bottom: 90px; }
        .main-table thead { display: none; }
        .main-table, .main-table tbody, .main-table tr, .main-table td { display: block; width: 100%; }
        .main-table tr { margin-bottom: 15px; border-radius: 20px; border: 1px solid var(--glass-border); }
        .main-table td { 
            border: none !important; display: flex; justify-content: space-between; 
            align-items: center; padding: 10px 15px !important; font-size: 0.85rem;
        }
        .main-table td::before { 
            content: attr(data-label); font-weight: 800; color: rgba(255,255,255,0.2); 
            font-size: 0.65rem; text-transform: uppercase;
        }
        .bottom-nav {
            display: flex; position: fixed; bottom: 0; left: 0; right: 0; height: 65px;
            background: rgba(10, 10, 10, 0.98); backdrop-filter: blur(25px);
            border-top: 1px solid var(--glass-border); justify-content: space-around; 
            align-items: center; z-index: 1050; border-radius: 20px 20px 0 0;
        }
        .nav-item-m { text-align: center; color: rgba(255,255,255,0.3); text-decoration: none; font-size: 10px; flex: 1; }
        .nav-item-m.active { color: #fff; }
        .nav-item-m i { font-size: 20px; display: block; }
    }
    /* --- MOBILE NAV FIX (CODE 1 STANDARD) --- */
@media (max-width: 991px) {
    .mobile-nav {
        display: flex !important;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 65px;
        background: rgba(10, 10, 10, 0.98);
        backdrop-filter: blur(25px);
        border-top: 1px solid var(--glass-border);
        justify-content: space-around;
        align-items: center;
        z-index: 2000;
        border-radius: 20px 20px 0 0;
        padding: 0 10px;
    }

    .nav-item-m {
        text-align: center;
        color: rgba(255, 255, 255, 0.3);
        text-decoration: none;
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        transition: 0.3s;
    }

    .nav-item-m i {
        font-size: 20px;
        line-height: 1;
        margin-bottom: 4px;
        display: block;
    }

    .nav-item-m span {
        font-size: 10px;
        font-weight: 600;
        display: block;
    }

    .nav-item-m.active {
        color: #ffffff !important;
    }

    .nav-item-m.active i {
        color: var(--primary-neon);
    }

    /* Table container space for mobile nav */
    .container.py-2 {
        padding-bottom: 80px !important;
    }
}

/* Desktop එකේදී mobile nav එක hide කරන්න */
@media (min-width: 992px) {
    .mobile-nav {
        display: none !important;
    }
}
</style>
<body>
<nav class="top-nav">
    <div class="container d-flex align-items-center justify-content-between">
        
        <div class="d-flex align-items-center">
            <a href="admin_dashboard.php" class="back-btn"><i class="bi bi-chevron-left"></i></a>
            <div class="ms-3">
              <h5 class="m-0 fw-bold" style="color: #ffffff !important; text-shadow: 0 2px 10px rgba(0,0,0,0.5);">Staff Onboarding</h5>                <small class="text-white-50" style="font-size: 0.7rem;">Admin Control Panel</small>
            </div>
        </div>
        <div class="month-switcher d-none d-md-flex">
            <a href="?month=<?= $prev_month ?>" class="text-info"><i class="bi bi-caret-left-fill"></i></a>
            <span class="small fw-bold text-uppercase" style="letter-spacing: 1px;"><?= $display_month ?></span>
            <a href="?month=<?= $next_month ?>" class="text-info"><i class="bi bi-caret-right-fill"></i></a>
        </div>

        <div class="right-actions">
            <a href="salary_structure.php" class="nav-link-btn text-white" style="font-size: 1.5rem;">
                <i class="bi bi-plus-circle-fill"></i>
            </a>
        </div>

    </div>
</nav>


<div class="container py-2">
    <div class="d-flex d-md-none justify-content-center mb-4">
        <div class="month-switcher w-100 justify-content-between">
            <a href="?month=<?= $prev_month ?>" class="text-info"><i class="bi bi-chevron-left"></i></a>
            <span class="fw-bold"><?= $display_month ?></span>
            <a href="?month=<?= $next_month ?>" class="text-info"><i class="bi bi-chevron-right"></i></a>
        </div>
    </div>

    <div class="glass-card p-3">
        <div class="table-responsive">
            <table class="main-table">
                <thead>
                    <tr>
                        <th class="ps-4">Employee</th>
                        <th>Designation</th>
                        <th class="text-end">Net Salary</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($salary_report as $s): ?>
                    <tr>
                        <td data-label="Employee" class="ps-4">
                            <div class="d-flex align-items-center gap-3">
                                <img src="uploads/profiles/<?= $s['image'] ?: 'default.png' ?>" class="emp-img" onerror="this.src='assets/img/default-user.png'">
                                <div class="text-start">
                                    <div class="fw-bold"><?= htmlspecialchars($s['name']) ?></div>
                                    <div class="small text-white-50" style="font-size: 11px;"><?= $s['phone'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Role"><span class="badge bg-dark border border-secondary"><?= $s['designation'] ?></span></td>
                        <td data-label="Salary" class="text-end fw-bold text-info">LKR <?= number_format($s['net'], 2) ?></td>
                        <td data-label="Action" class="text-end pe-4">
                            <div class="d-flex gap-2 justify-content-end">
                                <button class="btn-icon-sq view-btn" data-payload="<?= base64_encode(json_encode($s)) ?>">
                                    <i class="bi bi-file-earmark-text text-info"></i>
                                </button>
                                <?php if($s['is_paid']): ?>
                                    <span class="badge bg-success py-2 px-3"><i class="bi bi-check-circle-fill me-1"></i>PAID</span>
                                <?php else: ?>
                                    <button class="btn-neon pay-now" data-id="<?= $s['id'] ?>" data-amt="<?= $s['net'] ?>" data-month="<?= $target_month ?>">PAY</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="invoiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 24px; background: #0a0a0c;">
            <div class="modal-body p-0">
                <div id="invoice-box">
                    <div class="d-flex justify-content-between border-bottom border-secondary border-opacity-25 pb-4 mb-4">
                        <h2 class="fw-bold text-info mb-0">FABRICARE</h2>
                        <div class="text-end"><div class="small text-white-50">Salary Statement</div><div class="fw-bold" id="inv-month"></div></div>
                    </div>
                    <div class="row mb-5 align-items-center">
                        <div class="col-8 d-flex align-items-center gap-3">
                            <img id="inv-img" src="" class="emp-img" style="width: 60px; height: 60px;">
                            <div><h5 class="fw-bold mb-0" id="inv-name"></h5><span class="text-info small fw-bold" id="inv-desig"></span></div>
                        </div>
                        <div class="col-4 text-end small text-white-50"><div id="inv-nic"></div><div id="inv-phone"></div></div>
                    </div>
                    <div id="inv-items-container" class="mb-4"></div>
                    <div class="d-flex justify-content-end pt-3 border-top border-secondary border-opacity-25">
                        <div class="text-end"><span class="fw-bold text-info d-block small">NET PAYABLE</span><h2 class="fw-bold text-info mb-0" id="inv-net"></h2></div>
                    </div>
                </div>
                <div class="p-3 d-flex gap-2" style="background: #111;">
                    <button class="btn btn-info flex-grow-1 fw-bold rounded-3" id="btn-share">SHARE IMAGE</button>
                    <button class="btn btn-light flex-grow-1 fw-bold rounded-3" id="btn-pdf">DOWNLOAD PDF</button>
                    <button class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">CLOSE</button>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
const invModal = new bootstrap.Modal(document.getElementById('invoiceModal'));
let activeData = null;

function b64DecodeUnicode(str) {
    return decodeURIComponent(atob(str).split('').map(function(c) {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
    }).join(''));
}

$(document).on('click', '.view-btn', function() {
    try {
        const payload = $(this).attr('data-payload');
        activeData = JSON.parse(b64DecodeUnicode(payload));
        
        $('#inv-month').text(activeData.month);
        $('#inv-name').text(activeData.name);
        $('#inv-desig').text(activeData.designation);
        $('#inv-nic').text('NIC: ' + (activeData.nic || 'N/A'));
        $('#inv-phone').text('Mob: ' + activeData.phone);
        $('#inv-img').attr('src', 'uploads/profiles/' + (activeData.image || 'default.png'));

        let html = `
            <div class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-10"><span>Basic Salary</span><b>${activeData.basic.toLocaleString('en-US', {minimumFractionDigits: 2})}</b></div>
            <div class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-10"><span>Bonus Pay</span><b>${activeData.bonus.toLocaleString('en-US', {minimumFractionDigits: 2})}</b></div>
            <div class="d-flex justify-content-between py-2 text-info"><span>Commission</span><b>${activeData.commission.toLocaleString('en-US', {minimumFractionDigits: 2})}</b></div>
        `;
        
        if(activeData.work_breakdown.length > 0) {
            html += `<div class="small text-white-50 mt-3 mb-1">Breakdown:</div>`;
            activeData.work_breakdown.forEach(w => {
                html += `<div class="d-flex justify-content-between py-1 small opacity-50"><span>${w.service} (x${w.qty})</span><span>${w.total.toLocaleString()}</span></div>`;
            });
        }

        $('#inv-items-container').html(html);
        $('#inv-net').text(activeData.net.toLocaleString('en-US', {minimumFractionDigits: 2}));
        invModal.show();
    } catch (e) {
        console.error(e);
        Swal.fire({icon:'error', title:'Error', text:'Could not load data.', background:'#0a0a0c', color:'#fff'});
    }
});

$('#btn-share').click(async function() {
    const btn = $(this); btn.text('Processing...');
    const canvas = await html2canvas(document.getElementById('invoice-box'), { backgroundColor: '#0a0a0c', scale: 2 });
    canvas.toBlob(async (blob) => {
        const file = new File([blob], `${activeData.name}_Payslip.png`, { type: 'image/png' });
        if (navigator.canShare && navigator.canShare({ files: [file] })) {
            await navigator.share({ files: [file], title: 'Payslip', text: `${activeData.name} - ${activeData.month}` });
        } else {
            const link = document.createElement('a'); link.download = file.name; link.href = URL.createObjectURL(blob); link.click();
        }
        btn.text('SHARE IMAGE');
    }, 'image/png');
});

$('#btn-pdf').click(function() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'pt', 'a4');
    html2canvas(document.getElementById('invoice-box'), { scale: 2, backgroundColor: '#0a0a0c' }).then(canvas => {
        const img = canvas.toDataURL('image/png');
        const width = doc.internal.pageSize.getWidth() - 60;
        doc.addImage(img, 'PNG', 30, 40, width, (canvas.height * width) / canvas.width);
        doc.save(`${activeData.name}_Payslip.pdf`);
    });
});

$('.pay-now').on('click', function() {
    let btn = $(this);
    Swal.fire({ 
        title: 'Confirm Payment?', 
        text: "Mark LKR " + btn.data('amt').toLocaleString() + " as Paid?", 
        icon: 'warning',
        showCancelButton: true, 
        confirmButtonColor: '#00f2fe',
        cancelButtonColor: '#333',
        background: '#0a0a0c',
        color: '#fff'
    }).then((res) => {
        if (res.isConfirmed) { 
            $.post('process_payroll.php', { 
                employee_id: btn.data('id'), 
                amount: btn.data('amt'), 
                month: btn.data('month'), 
                action: 'mark_paid' 
            }, function() { 
                location.reload(); 
            }); 
        }
    });
});
</script>
</body>
</html>