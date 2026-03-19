<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$status = "";
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: inventory.php");
    exit();
}

// 1. DELETE LOGIC
if (isset($_POST['delete_item'])) {
    $delete_stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
    if ($delete_stmt->execute([$id])) {
        header("Location: inventory.php?msg=deleted");
        exit();
    }
}

// 2. GET CURRENT DATA
$stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    header("Location: inventory.php");
    exit();
}

// 3. UPDATE LOGIC
if (isset($_POST['update_item'])) {
    $name = $_POST['item_name'];
    $stock = $_POST['current_stock'];
    $price = $_POST['unit_price'];

    $update_stmt = $pdo->prepare("UPDATE inventory SET item_name = ?, current_stock = ?, unit_price = ? WHERE id = ?");
    if ($update_stmt->execute([$name, $stock, $price, $id])) {
        $status = "success";
        // Refresh data for display
        $item['item_name'] = $name;
        $item['current_stock'] = $stock;
        $item['unit_price'] = $price;
    } else {
        $status = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Manage Inventory | Fabricare</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --primary-neon: #00f2fe;
            --dark-deep: #08080a;
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body {
            background: radial-gradient(circle at 0% 0%, #1e1b4b 0%, transparent 50%),
                        radial-gradient(circle at 100% 100%, #2e1065 0%, transparent 50%),
                        var(--dark-deep);
            background-attachment: fixed;
            min-height: 100vh;
            font-family: 'Space Grotesk', sans-serif;
            color: #ffffff;
            margin: 0;
            overflow-x: hidden;
        }

        /* --- TOP NAV --- */
        .top-nav {
            background: rgba(8, 8, 10, 0.85); backdrop-filter: blur(25px);
            border-bottom: 1px solid var(--glass-border); padding: 15px 0;
            position: sticky; top: 0; z-index: 1000;
        }

        /* --- GLASS CARD --- */
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            padding: 35px;
            max-width: 480px;
            margin: 40px auto;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        }

        .form-label { color: rgba(255,255,255,0.5); font-size: 0.75rem; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 500; }
        
        .form-control {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--glass-border);
            color: #fff; border-radius: 12px; padding: 12px;
        }
        .form-control:focus {
            background: rgba(255,255,255,0.08); border-color: var(--primary-neon);
            color: #fff; box-shadow: 0 0 15px rgba(0, 242, 254, 0.1);
        }

        .btn-update {
            background: linear-gradient(45deg, #00f2fe, #4facfe);
            border: none; color: #08080a; font-weight: 700;
            border-radius: 15px; padding: 15px; width: 100%;
            text-transform: uppercase; letter-spacing: 1px; transition: 0.3s;
        }
        .btn-update:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0, 242, 254, 0.4); }

        .btn-delete {
            background: rgba(255, 77, 109, 0.05);
            border: 1px solid rgba(255, 77, 109, 0.3);
            color: #ff4d6d; border-radius: 15px; padding: 12px;
            width: 100%; font-weight: 600; text-transform: uppercase;
            transition: 0.3s; margin-top: 15px;
        }
        .btn-delete:hover { background: #ff4d6d; color: #fff; }

        /* --- SIDE TOAST ALERT --- */
        #side-toast {
            position: fixed; top: 100px; right: -400px;
            background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px);
            border-left: 4px solid var(--primary-neon);
            border-top: 1px solid var(--glass-border); border-bottom: 1px solid var(--glass-border);
            color: #fff; padding: 18px 25px; border-radius: 15px 0 0 15px;
            display: flex; align-items: center; z-index: 9999;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            box-shadow: -10px 10px 30px rgba(0,0,0,0.5);
        }
        #side-toast.show { right: 0; }
        .toast-icon { font-size: 1.4rem; color: var(--primary-neon); margin-right: 15px; }

        @media (max-width: 576px) { .glass-card { margin: 20px 15px; padding: 25px; } }
    </style>
</head>
<body>

    <div id="side-toast">
        <i class="bi bi-check2-circle toast-icon"></i>
        <div class="fw-bold small">STOCK UPDATED SUCCESSFULLY</div>
    </div>

    <nav class="top-nav">
        <div class="container d-flex align-items-center justify-content-between">
            <a href="inventory.php" style="color: var(--primary-neon); font-size: 1.5rem;"><i class="bi bi-arrow-left"></i></a>
            <h5 class="fw-bold m-0 text-white">Edit <span style="color: var(--primary-neon);">Configuration</span></h5>
            <div style="width: 24px;"></div>
        </div>
    </nav>

    <div class="container">
        <div class="glass-card">
            <form method="POST" id="updateForm">
                <div class="mb-4">
                    <label class="form-label">Item Identification</label>
                    <input type="text" name="item_name" class="form-control" value="<?= htmlspecialchars($item['item_name']) ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Stock Quantity (Units)</label>
                    <input type="number" name="current_stock" class="form-control" value="<?= (int)$item['current_stock'] ?>" required>
                </div>

                <div class="mb-5">
                    <label class="form-label">Cost Per Unit</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-info" style="border: 1px solid var(--glass-border);">Rs.</span>
                        <input type="number" step="0.01" name="unit_price" class="form-control border-start-0" value="<?= $item['unit_price'] ?>" required>
                    </div>
                </div>

                <button type="submit" name="update_item" class="btn btn-update">Update Inventory</button>
            </form>

            <div class="text-center my-3 opacity-25 small">OR</div>

            <form method="POST" id="deleteForm">
                <input type="hidden" name="delete_item" value="1">
                <button type="button" class="btn btn-delete" onclick="confirmDelete()">
                    <i class="bi bi-trash3 me-2"></i> Remove from Stock
                </button>
            </form>
        </div>
    </div>

    <script>
        // Side Toast Function
        function triggerToast() {
            const toast = document.getElementById('side-toast');
            toast.classList.add('show');
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        // Delete Confirmation (Professional Alert)
        function confirmDelete() {
            Swal.fire({
                title: 'Are you sure?',
                text: "This item will be permanently removed!",
                icon: 'warning',
                showCancelButton: true,
                background: '#111',
                color: '#fff',
                confirmButtonColor: '#ff4d6d',
                cancelButtonColor: '#333',
                confirmButtonText: 'Yes, Delete it!',
                backdrop: `rgba(0,0,0,0.8)`
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deleteForm').submit();
                }
            })
        }

        // Trigger Success Toast on PHP Success
        <?php if($status == "success"): ?>
            triggerToast();
        <?php endif; ?>
    </script>
</body>
</html>