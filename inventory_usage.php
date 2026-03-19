<?php
session_start();
require 'config.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'] ?? null; 
$success_msg = null;
$error_msg = null;

// --- HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        if (isset($_POST['update_inventory'])) {
            $item_id = $_POST['item_id'];
            $qty = $_POST['quantity'];
            $upd = $pdo->prepare("UPDATE inventory SET current_stock = current_stock - ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$qty, $item_id]);
            $log = $pdo->prepare("INSERT INTO inventory_updates (item_id, admin_id, order_id, update_type, quantity) VALUES (?, ?, ?, 'OUT', ?)");
            $log->execute([$item_id, $admin_id, $order_id, $qty]);
            $success_msg = "Stock usage recorded!";
        }
        if (isset($_POST['add_new_item'])) {
            $name = $_POST['item_name'];
            $qty = $_POST['initial_stock'];
            $price = $_POST['unit_price'];
            $ins = $pdo->prepare("INSERT INTO inventory (item_name, current_stock, unit_price, low_stock_alert, updated_at) VALUES (?, ?, ?, 0, NOW())");
            $ins->execute([$name, $qty, $price]);
            $new_item_id = $pdo->lastInsertId();
            $log = $pdo->prepare("INSERT INTO inventory_updates (item_id, admin_id, update_type, quantity) VALUES (?, ?, 'IN', ?)");
            $log->execute([$new_item_id, $admin_id, $qty]);
            $success_msg = "New item added!";
        }
        if (isset($_POST['restock_item'])) {
            $item_id = $_POST['item_id'];
            $qty = $_POST['add_quantity'];
            $price = $_POST['unit_price']; 
            $upd = $pdo->prepare("UPDATE inventory SET current_stock = current_stock + ?, unit_price = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$qty, $price, $item_id]);
            $log = $pdo->prepare("INSERT INTO inventory_updates (item_id, admin_id, update_type, quantity) VALUES (?, ?, 'IN', ?)");
            $log->execute([$item_id, $admin_id, $qty]);
            $success_msg = "Inventory restocked!";
        }
        $pdo->query("UPDATE inventory SET low_stock_alert = CASE WHEN current_stock < 2 THEN 1 ELSE 0 END");
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Error: " . $e->getMessage();
    }
}

$items = $pdo->query("SELECT * FROM inventory ORDER BY low_stock_alert DESC, item_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Inventory | Fabricare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
    :root {
        --bg-dark: #080808;
        --glass: rgba(255, 255, 255, 0.04);
        --glass-border: rgba(255, 255, 255, 0.12);
        --accent-red: #ff3b30;
    }

    body {
        background-color: var(--bg-dark);
        background-image: linear-gradient(rgba(8, 8, 8, 0.96), rgba(8, 8, 8, 0.96)), url('uploads/resources/bg2.jpg');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        min-height: 100vh;
        font-family: 'Plus Jakarta Sans', sans-serif;
        color: #ffffff !important;
        margin: 0;
        font-size: 14px;
    }

    /* --- TOP NAV (CLEAN) --- */
    .top-nav {
        background: rgba(10, 10, 10, 0.85);
        backdrop-filter: blur(30px);
        border-bottom: 1px solid var(--glass-border);
        padding: 8px 0;
        position: sticky; top: 0; z-index: 1000;
    }

    .btn-nav-action {
        font-size: 10px;
        font-weight: 800;
        border-radius: 8px;
        padding: 5px 12px;
        text-transform: uppercase;
    }

    /* --- FORM CONTROLS (TRANSPARENT FIX) --- */
    .form-control, .form-select {
        background: rgba(255, 255, 255, 0.05) !important;
        backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border) !important;
        color: #ffffff !important;
        border-radius: 10px;
        padding: 10px 12px;
        font-size: 13px;
    }

    /* Dropdown options text fix */
    .form-select option {
        background: #111 !important;
        color: #fff !important;
    }

    /* --- INVENTORY CARDS (RESIZED FOR MOBILE) --- */
    .glass-panel {
        background: var(--glass);
        backdrop-filter: blur(20px);
        border: 1px solid var(--glass-border);
        border-radius: 18px;
        padding: 15px;
        margin-bottom: 15px;
    }

    .inv-card {
        background: var(--glass);
        border: 1px solid var(--glass-border);
        border-radius: 15px;
        padding: 12px;
        position: relative;
        margin-bottom: 10px;
    }

    .item-title {
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 2px;
    }

    .stock-val {
        font-size: 18px;
        font-weight: 800;
    }

    /* --- MOBILE RESPONSIVE LOGIC --- */
    @media (max-width: 991px) {
        body { font-size: 13px; }
        
        /* 2 Columns for Inventory */
        .inventory-grid { 
            display: flex; 
            flex-wrap: wrap; 
            margin: 0 -5px; 
        }
        .inventory-grid > div { 
            padding: 5px; 
            flex: 0 0 50%; 
            max-width: 50%; 
        }

        .inv-card { padding: 10px; }
        .stock-val { font-size: 16px; }
        .item-title { font-size: 12px; }

        .btn-usage {
            padding: 10px;
            font-size: 12px;
        }
    }

    /* --- MOBILE NAV (SHOW ONLY ON MOBILE) --- */
    .mobile-nav {
        position: fixed; bottom: 0; left: 0; right: 0;
        height: 65px; background: rgba(10, 10, 10, 0.98);
        backdrop-filter: blur(25px); border-radius: 20px 20px 0 0;
        border-top: 1px solid var(--glass-border);
        display: flex; justify-content: space-around; align-items: center; z-index: 1050;
    }

    @media (min-width: 992px) {
        .mobile-nav { display: none !important; }
    }

    .nav-item-m { text-align: center; color: rgba(255,255,255,0.3); text-decoration: none; font-size: 9px; flex: 1; }
    .nav-item-m.active { color: #fff; }
    .nav-item-m i { font-size: 20px; display: block; }

    /* --- MODALS --- */
    .modal-content {
        background: #0a0a0a;
        border: 1px solid var(--glass-border);
        border-radius: 22px;
    }
    
    .btn-usage {
        background: #ffffff !important;
        color: #000000 !important;
        font-weight: 800;
        border-radius: 12px;
        border: none;
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
                <a href="employee_dashboard.php" class="back-btn"><i class="bi bi-chevron-left"></i></a>
                <div class="ms-3">
                    <h6 class="m-0 fw-bold">Inventory Management</h6>
                    <small class="text-white-50" style="font-size: 10px;">Stock Control</small>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-light btn-nav-action" data-bs-toggle="modal" data-bs-target="#newModal">+ NEW</button>
                <button class="btn btn-light btn-nav-action" data-bs-toggle="modal" data-bs-target="#restockModal">RESTOCK</button>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
        <?php if($success_msg): ?>
            <div class="alert alert-success bg-success bg-opacity-10 text-success border-0 rounded-4 mb-4 small">
                <i class="bi bi-check-circle-fill me-2"></i> <?= $success_msg ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-4">
                <div class="glass-panel">
                    <h6 class="fw-bold mb-4"><i class="bi bi-box-arrow-up text-danger me-2"></i>Record Usage</h6>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="small text-white-50 mb-2">Material</label>
                            <select name="item_id" class="form-select" required>
                                <option value="">Select Item...</option>
                                <?php foreach($items as $i): ?>
                                    <option value="<?= $i['id'] ?>"><?= htmlspecialchars($i['item_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="small text-white-50 mb-2">Quantity (Out)</label>
                            <input type="number" name="quantity" step="0.01" class="form-control" placeholder="0.00" required>
                        </div>
                        <button type="submit" name="update_inventory" class="btn btn-usage w-100 py-3">Update Stock</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <p class="small fw-bold text-white-50 text-uppercase mb-3">Live Inventory Status</p>
                <div class="inventory-grid">
                    <?php foreach($items as $i): ?>
                        <div>
                            <div class="inv-card <?= $i['low_stock_alert'] ? 'low-stock-card' : '' ?>">
                                <?php if($i['low_stock_alert']): ?>
                                    <span class="low-stock-badge">Low</span>
                                <?php endif; ?>
                                
                                <div>
                                    <span class="d-block fw-bold mb-1" style="font-size: 0.9rem;"><?= htmlspecialchars($i['item_name']) ?></span>
                                    <span class="text-white-50" style="font-size: 10px;">LKR <?= number_format($i['unit_price'], 2) ?></span>
                                </div>

                                <div class="mt-4 pt-3 border-top border-white border-opacity-10 d-flex justify-content-between align-items-end">
                                    <div>
                                        <small class="text-white-50 d-block" style="font-size: 9px; font-weight: 800;">STOCK</small>
                                        <span class="fs-4 fw-bold <?= $i['low_stock_alert'] ? 'text-danger' : 'text-white' ?>"><?= (float)$i['current_stock'] ?></span>
                                    </div>
                                    <i class="bi bi-pencil-square text-white-50"></i>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-body p-4">
                        <h5 class="fw-bold mb-4">Add New Product</h5>
                        <div class="mb-3"><label class="small mb-2 opacity-50">Item Name</label><input type="text" name="item_name" class="form-control" required></div>
                        <div class="mb-3"><label class="small mb-2 opacity-50">Opening Qty</label><input type="number" name="initial_stock" step="0.01" class="form-control" required></div>
                        <div class="mb-4"><label class="small mb-2 opacity-50">Unit Price</label><input type="number" name="unit_price" step="0.01" class="form-control" required></div>
                        <button type="submit" name="add_new_item" class="btn btn-usage w-100 py-3">Save Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="restockModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-body p-4">
                        <h5 class="fw-bold mb-4">Restock Inventory</h5>
                        <div class="mb-3">
                            <label class="small mb-2 opacity-50">Select Item</label>
                            <select name="item_id" id="rs_select" class="form-select" onchange="autoFillPrice()" required>
                                <option value="" data-price="0">Choose...</option>
                                <?php foreach($items as $i): ?>
                                    <option value="<?= $i['id'] ?>" data-price="<?= $i['unit_price'] ?>"><?= htmlspecialchars($i['item_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3"><label class="small mb-2 opacity-50">Quantity to Add</label><input type="number" name="add_quantity" step="0.01" class="form-control" required></div>
                        <div class="mb-4"><label class="small mb-2 opacity-50">Cost Price (LKR)</label><input type="number" name="unit_price" id="rs_price" class="form-control"></div>
                        <button type="submit" name="restock_item" class="btn btn-usage w-100 py-3">Confirm Restock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <nav class="mobile-nav">
        <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
        <a href="admin_dashboard.php" class="nav-item-m active"><i class="bi bi-box-seam"></i><span>Stock</span></a>
        <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function autoFillPrice() {
        const select = document.getElementById('rs_select');
        const price = select.options[select.selectedIndex].getAttribute('data-price');
        document.getElementById('rs_price').value = price;
    }
    </script>
</body>
</html>