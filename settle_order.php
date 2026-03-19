<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'config.php';

date_default_timezone_set('Asia/Colombo');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_role = $_SESSION['role'] ?? 'admin';

// 1. Fetch Companies with Ready Item Count
try {
    $comp_query = "SELECT c.id, c.business_name, 
                   (SELECT COUNT(*) FROM order_items oi 
                    JOIN orders o ON oi.order_id = o.id 
                    JOIN customers cust ON o.customer_id = cust.id 
                    WHERE cust.company_id = c.id AND oi.status = 'completed') as active_count
                   FROM companies c 
                   ORDER BY active_count DESC, c.business_name ASC";
    $companies = $pdo->query($comp_query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

// 2. AJAX get items
if (isset($_GET['action']) && $_GET['action'] == 'get_items') {
    $company_id = intval($_GET['company_id']);
    $query = "SELECT oi.id, s.service_name, oi.quantity, oi.subtotal, o.id as order_id, o.pickup_required 
              FROM order_items oi 
              JOIN orders o ON oi.order_id = o.id 
              JOIN customers cust ON o.customer_id = cust.id 
              JOIN services s ON oi.service_id = s.id 
              WHERE cust.company_id = ? AND oi.status = 'completed'";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$company_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

$settled_success = false;
$final_data = [];

// 3. Process Settlement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_settle'])) {
    $selected_items = $_POST['items'] ?? [];
    $delivery_fee = floatval($_POST['delivery_fee'] ?? 0);
    $company_id = intval($_POST['selected_company_id']);
    $bill_create_time = date('Y-m-d H:i:s'); 
    
    $total_bill_amount = $delivery_fee;
    $item_ids_string = implode(',', $selected_items);

    try {
        $pdo->beginTransaction();
        foreach ($selected_items as $item_id) {
            $upd = $pdo->prepare("UPDATE order_items SET status = 'delivered', created_at = ? WHERE id = ?");
            $upd->execute([$bill_create_time, $item_id]);
            
            $info = $pdo->prepare("SELECT s.service_name, oi.quantity, oi.subtotal FROM order_items oi JOIN services s ON oi.service_id = s.id WHERE oi.id = ?");
            $info->execute([$item_id]);
            $row = $info->fetch(PDO::FETCH_ASSOC);
            $final_data['items'][] = $row;
            $total_bill_amount += $row['subtotal'];
        }

        $settle_sql = "INSERT INTO settlements (company_id, admin_id, admin_type, total_amount, delivery_fee, items_count, order_items_ids, settled_at) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($settle_sql)->execute([$company_id, $admin_id, $admin_role, $total_bill_amount, $delivery_fee, count($selected_items), $item_ids_string, $bill_create_time]);

        $comp_info = $pdo->prepare("SELECT business_name FROM companies WHERE id = ?");
        $comp_info->execute([$company_id]);
        $final_data['company'] = $comp_info->fetch(PDO::FETCH_ASSOC);
        $final_data['delivery_fee'] = $delivery_fee;
        $final_data['total_all'] = $total_bill_amount;
        $final_data['bill_time'] = $bill_create_time;
        
        $pdo->commit();
        $settled_success = true;
    } catch (Exception $e) { $pdo->rollBack(); die("Error: " . $e->getMessage()); }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Fabricare | Settlement</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
    :root { 
        --primary-neon: #ffffff17; 
        --dark-deep: #080808; 
        --glass: rgba(255, 255, 255, 0.05);
        --glass-border: rgba(255, 255, 255, 0.12);
        --gold: #ffb800;
    }

    body { 
        background-color: var(--dark-deep);
        background-image: linear-gradient(rgba(8, 8, 8, 0.97), rgba(8, 8, 8, 0.97)), 
                          url('uploads/resources/bg2.jpg');
        background-size: cover; background-position: center; background-attachment: fixed;
        color: #fff; font-family: 'Plus Jakarta Sans', sans-serif; 
        min-height: 100vh; margin: 0; padding-bottom: 110px;
    }

    /* --- TOP NAV (FORCED WHITE) --- */
    .top-nav { 
        background: rgba(10, 10, 10, 0.9); backdrop-filter: blur(25px); 
        border-bottom: 1px solid var(--glass-border); padding: 12px 0; 
        position: sticky; top: 0; z-index: 1000; 
    }
    
    .top-nav h5 { 
        color: #ffffff !important; font-weight: 700; margin: 0; font-size: 1rem;
        background: none !important; -webkit-text-fill-color: #ffffff !important;
    }

    .back-btn {
        width: 36px; height: 36px; border-radius: 10px;
        background: var(--glass); border: 1px solid var(--glass-border);
        display: flex; align-items: center; justify-content: center;
        color: #fff !important; text-decoration: none;
    }

    /* --- ALIGNMENT & PANELS --- */
    .glass-panel { 
        background: var(--glass); backdrop-filter: blur(15px);
        border: 1px solid var(--glass-border); border-radius: 20px; 
        padding: 18px; margin-bottom: 15px; width: 100%;
    }

    /* --- SELECT2 ALIGNMENT FIX --- */
    .select2-container--default .select2-selection--single { 
        background: rgba(255,255,255,0.08) !important; border: 1px solid var(--glass-border) !important; 
        height: 52px !important; border-radius: 14px !important; display: flex !important; align-items: center !important;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered { 
        color: #fff !important; padding-left: 15px !important; line-height: 52px !important;
    }

    .select2-dropdown { 
        background-color: #0f0f13 !important; border: 1px solid var(--glass-border) !important; 
        border-radius: 14px !important; color: #fff !important; overflow: hidden;
    }

    .select2-results__option--highlighted[aria-selected] { 
        background-color: var(--primary-neon) !important; color: #000 !important; 
    }

    /* --- ITEM CARDS ALIGNMENT --- */
    .item-card { 
        background: var(--glass); border: 1px solid var(--glass-border); 
        border-radius: 15px; padding: 14px; margin-bottom: 10px; 
        display: flex; align-items: center; justify-content: space-between;
    }
    
    .is-pickup { border-left: 4px solid var(--gold) !important; background: rgba(255, 184, 0, 0.05) !important; }

    .form-check-input { width: 22px; height: 22px; margin-right: 12px; cursor: pointer; }

    /* --- BUTTONS --- */
    .btn-action { 
        height: 58px; border-radius: 16px; font-weight: 700; font-size: 0.95rem; 
        text-transform: uppercase; letter-spacing: 0.5px; width: 100%; border: none;
    }
    
    .btn-confirm { 
        background: #fff; color: #000; 
        box-shadow: 0 10px 25px rgba(255, 255, 255, 0.1); 
    }
    .btn-confirm:hover { background: var(--primary-neon); transform: translateY(-2px); }

    /* --- RECEIPT ALIGNMENT --- */
    .receipt-box { 
        background: #ffffff; color: #000; padding: 25px; border-radius: 15px; 
        font-family: 'Courier New', monospace; max-width: 100%; margin: 0 auto; 
        box-shadow: 0 15px 40px rgba(0,0,0,0.5);
    }

    .receipt-box table { width: 100%; border-collapse: collapse; }
    .receipt-box td { padding: 5px 0; vertical-align: top; }

    /* --- BOTTOM NAV (ONLY MOBILE) --- */
    .bottom-nav { 
        position: fixed; bottom: 0; left: 0; right: 0; height: 65px; 
        background: rgba(10, 10, 10, 0.98); border-top: 1px solid var(--glass-border); 
        display: flex; justify-content: space-around; align-items: center; z-index: 1000;
    }
    
    @media (min-width: 992px) { 
        .bottom-nav { display: none !important; } 
        .container { max-width: 500px !important; }
    }

    .nav-item-m { text-align: center; color: rgba(255,255,255,0.3); text-decoration: none; font-size: 10px; flex: 1; }
    .nav-item-m.active { color: #fff; }
    .nav-item-m i { font-size: 22px; display: block; }
</style>
</head>
<body>
    <nav class="top-nav">
        <div class="container d-flex align-items-center">
            <a href="employee_dashboard.php" class="back-btn"><i class="bi bi-chevron-left"></i></a>
            <div class="ms-3">
                <h5 class="m-0 fw-bold" style="background: linear-gradient(to right, #fff, var(--primary-neon)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Staff Onboarding</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Admin Control Panel</small>
            </div>
        </div>
    </nav>

<div class="container" style="max-width: 550px;">
    <?php if (!$settled_success): ?>
        

        <div class="mb-4">
            <label class="text-white-50 small mb-2 ms-2">CHOOSE CLIENT BUSINESS</label>
            <select id="businessSelect">
                <option value="">Search company...</option>
                <?php foreach ($companies as $c): ?>
                    <option value="<?= $c['id'] ?>" data-active="<?= $c['active_count'] ?>">
                        <?= htmlspecialchars($c['business_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <form id="settleForm" method="POST" style="display:none;">
            <input type="hidden" name="selected_company_id" id="hidden_company_id">
            
            <div id="itemsList"></div>

            <div id="deliverySection" class="glass-panel mt-4 opacity-50">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-white-50 small fw-bold">DELIVERY / LOGISTICS</span>
                    <i id="lockIcon" class="bi bi-lock-fill text-danger"></i>
                </div>
                <div class="d-flex align-items-center">
                    <span class="fs-4 fw-bold text-white-50 me-2">LKR</span>
                    <input type="number" id="deliveryInput" name="delivery_fee" class="form-control bg-transparent border-0 text-info fs-1 fw-bold p-0" value="0" placeholder="0" disabled>
                </div>
            </div>

            <div class="fixed-bottom p-3 d-md-relative" style="background: rgba(8,8,10,0.8); backdrop-filter: blur(10px);">
                <button type="submit" name="process_settle" class="btn btn-confirm btn-action">
                    <i class="bi bi-receipt-cutoff me-2"></i> COMPLETE SETTLEMENT
                </button>
            </div>
        </form>

    <?php else: ?>
        <div class="text-center py-4">
            <div class="bg-success d-inline-block p-3 rounded-circle mb-3 shadow">
                <i class="bi bi-check2-all fs-1 text-white"></i>
            </div>
            <h3 class="fw-bold">Payment Recorded</h3>
        </div>

        <div id="captureArea" class="receipt-box">
            <div class="text-center border-bottom border-dark pb-2 mb-3">
                <h2 class="fw-bold m-0" style="letter-spacing: -2px;">FABRICARE</h2>
                <p class="small m-0">Laundromat Services</p>
            </div>
            <div class="mb-3" style="font-size: 13px;">
                <div class="d-flex justify-content-between"><span><b>CLIENT:</b></span><span class="text-uppercase"><?= $final_data['company']['business_name'] ?></span></div>
                <div class="d-flex justify-content-between"><span><b>REF NO:</b></span><span>SET-<?= time() ?></span></div>
                <div class="d-flex justify-content-between"><span><b>TIME:</b></span><span><?= date('d/m/Y h:i A') ?></span></div>
            </div>
            <table class="table table-sm border-0 mb-3" style="font-size: 13px;">
                <tbody class="border-top border-dark">
                    <?php $sub = 0; foreach ($final_data['items'] as $item): ?>
                    <tr><td><?= $item['service_name'] ?> (x<?= $item['quantity'] ?>)</td><td class="text-end"><?= number_format($item['subtotal'], 2) ?></td></tr>
                    <?php $sub += $item['subtotal']; endforeach; ?>
                    <?php if($final_data['delivery_fee'] > 0): ?>
                    <tr><td>Delivery Fee</td><td class="text-end"><?= number_format($final_data['delivery_fee'], 2) ?></td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot class="border-top border-dark border-double">
                    <tr class="fw-bold fs-6"><td>TOTAL</td><td class="text-end">LKR <?= number_format($final_data['total_all'], 2) ?></td></tr>
                </tfoot>
            </table>
            <div class="text-center mt-3 pt-2 border-top small opacity-75">Thank you for your business!</div>
        </div>

        <button id="waShare" class="btn btn-success btn-action mt-4 shadow">
            <i class="bi bi-whatsapp me-2"></i> SEND TO WHATSAPP
        </button>
        <div class="text-center mt-4">
            <a href="settle_order.php" class="text-white-50 text-decoration-none">← Start New Settlement</a>
        </div>
    <?php endif; ?>
</div>
<nav class="bottom-nav">
    <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
    <a href="admin_dashboard.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
    <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
</nav>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
$(document).ready(function() {
    function formatRepo(repo) {
        if (!repo.id) return repo.text;
        let count = $(repo.element).data('active');
        return $(`<div class="d-flex align-items-center"><span>${repo.text}</span>${count > 0 ? `<span class="badge-ready">${count} READY</span>` : ''}</div>`);
    }

    $('#businessSelect').select2({
        templateResult: formatRepo,
        templateSelection: formatRepo,
        placeholder: "Search business name...",
        allowClear: true
    });

    $('#businessSelect').on('change', function() {
        const cid = $(this).val();
        $('#hidden_company_id').val(cid);
        if(!cid) { $('#settleForm').hide(); return; }

        $.getJSON('settle_order.php?action=get_items&company_id=' + cid, function(data) {
            $('#itemsList').empty();
            let hasPickup = false;
            if(data.length > 0) {
                $('#settleForm').fadeIn();
                data.forEach(item => {
                    if(item.pickup_required == 1) hasPickup = true;
                    $('#itemsList').append(`
                        <div class="item-card ${item.pickup_required == 1 ? 'is-pickup' : ''} d-flex align-items-center">
                            <input type="checkbox" name="items[]" value="${item.id}" class="form-check-input fs-5 me-3" checked>
                            <div class="flex-grow-1">
                                <span class="fw-bold text-white d-block">${item.service_name}</span>
                                <small class="text-white-50">#ORD-${item.order_id} | Qty: ${item.quantity}</small>
                            </div>
                            <div class="text-info fw-bold">LKR ${parseFloat(item.subtotal).toLocaleString()}</div>
                        </div>
                    `);
                });

                if(hasPickup) {
                    $('#deliverySection').removeClass('opacity-50');
                    $('#deliveryInput').prop('disabled', false).focus();
                    $('#lockIcon').removeClass('bi-lock-fill text-danger').addClass('bi-unlock-fill text-success');
                } else {
                    $('#deliverySection').addClass('opacity-50');
                    $('#deliveryInput').prop('disabled', true).val(0);
                    $('#lockIcon').removeClass('bi-unlock-fill text-success').addClass('bi-lock-fill text-danger');
                }
            } else {
                alert("No ready orders found!");
                $('#settleForm').hide();
            }
        });
    });

    $('#waShare').on('click', async function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Generating...');
        
        const canvas = await html2canvas(document.getElementById('captureArea'), { scale: 3 });
        canvas.toBlob(async (blob) => {
            const file = new File([blob], 'Receipt.png', { type: 'image/png' });
            if (navigator.share && navigator.canShare({ files: [file] })) {
                await navigator.share({ files: [file], title: 'Fabricare Bill' });
            } else {
                const link = document.createElement('a');
                link.download = 'Fabricare_Receipt.png';
                link.href = canvas.toDataURL();
                link.click();
            }
            btn.prop('disabled', false).html('<i class="bi bi-whatsapp me-2"></i> SEND TO WHATSAPP');
        });
    });
});
</script>
</body>
</html>