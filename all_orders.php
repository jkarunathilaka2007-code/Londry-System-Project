<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'config.php';

// Admin හෝ Employee දෙන්නටම අවසර ඇත
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'employee')) {
    header("Location: login.php");
    exit();
}

// 🔥 AJAX Update Logic - දැන් update වෙන්නේ order_items ටේබල් එක
if (isset($_POST['update_status'])) {
    $item_row_id = intval($_POST['item_row_id']);
    $new_status = strtolower(trim($_POST['new_status'])); // lowercase logic එක මෙතනටත් දැම්මා

    try {
        $stmt = $pdo->prepare("UPDATE order_items SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $item_row_id])) {
            echo "success";
        } else {
            echo "error";
        }
    } catch (PDOException $e) {
        echo "error: " . $e->getMessage();
    }
    exit();
}

try {
    // Delivered, Completed සහ Cancelled නොවන (Pending/Processing) අයිටම් පමණක් ගනී
    $query = "
        SELECT 
            oi.id as item_row_id, 
            oi.quantity, 
            oi.item_due_date, 
            oi.status as item_status,
            o.id as order_id, 
            comp.business_name, 
            s.service_name
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN customers c ON o.customer_id = c.id 
        JOIN companies comp ON c.company_id = comp.id
        JOIN services s ON oi.service_id = s.id
        WHERE oi.status NOT IN ('cancelled', 'delivered', 'completed') 
        ORDER BY 
            CASE 
                WHEN oi.status = 'pending' THEN 1
                WHEN oi.status = 'processing' THEN 2
            END ASC,
            oi.item_due_date ASC
    ";
    $stmt = $pdo->query($query);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { 
    die("Database Error: " . $e->getMessage()); 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registry | Fabricare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
    :root { 
        --primary-neon: #00f2fe; 
        --dark-deep: #080808; 
        --warning-red: #ff4757; 
        --glass: rgba(255, 255, 255, 0.05);
        --glass-border: rgba(255, 255, 255, 0.12);
    }

    body { 
        background-color: var(--dark-deep);
        background-image: linear-gradient(rgba(8, 8, 8, 0.97), rgba(8, 8, 8, 0.97)), 
                          url('uploads/resources/bg2.jpg');
        background-size: cover; background-position: center; background-attachment: fixed;
        color: #fff; font-family: 'Plus Jakarta Sans', sans-serif; 
        min-height: 100vh; margin: 0; padding-bottom: 80px;
    }

    /* --- TOP NAV --- */
    .top-nav { 
        background: rgba(10, 10, 10, 0.9); backdrop-filter: blur(25px); 
        border-bottom: 1px solid var(--glass-border); padding: 10px 0; 
        position: sticky; top: 0; z-index: 1000; 
    }
    
    .top-nav h5 { 
        color: #ffffff !important; font-weight: 700; margin: 0; font-size: 1rem;
        background: none !important; -webkit-text-fill-color: #ffffff !important;
    }

    .back-btn {
        width: 34px; height: 34px; border-radius: 8px;
        background: var(--glass); border: 1px solid var(--glass-border);
        display: flex; align-items: center; justify-content: center;
        color: #fff !important; text-decoration: none;
    }

    /* --- COMPACT ORDER CARDS --- */
    .glass-card { 
        background: var(--glass); border: 1px solid var(--glass-border); 
        border-radius: 12px; padding: 10px 15px; margin-bottom: 8px; 
    }

    .card-critical { border-left: 3px solid var(--warning-red); }
    .card-completed { border-left: 3px solid #2ecc71; }

    .business-name { font-size: 0.85rem; font-weight: 700; color: #fff; }
    .service-info { font-size: 0.75rem; color: rgba(255,255,255,0.6); }

    /* --- TRANSPARENT SELECT BOX FIX --- */
    .status-select { 
        background: transparent !important; 
        color: #ffffff !important; 
        border: 1px solid var(--glass-border); 
        font-size: 11px; 
        font-weight: 600; 
        border-radius: 8px; 
        padding: 6px 8px; 
        width: 100%; 
        outline: none;
        appearance: none; /* Default arrow එක අයින් කරන්න */
        -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='white' class='bi bi-chevron-down' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
    }

    /* Dropdown options වලට background එකක් ඕනේ අකුරු පේන්න */
    .status-select option { 
        background-color: #111 !important; 
        color: #ffffff !important; 
    }
    
    #statusToast {
        position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%);
        background: var(--primary-neon); color: #000; font-weight: 800;
        padding: 8px 20px; border-radius: 50px; display: none; z-index: 10000;
        font-size: 11px;
    }

    /* --- MOBILE NAV --- */
    .bottom-nav { 
        position: fixed; bottom: 0; left: 0; right: 0; height: 60px; 
        background: rgba(10, 10, 10, 0.98); border-top: 1px solid var(--glass-border); 
        display: flex; justify-content: space-around; align-items: center; z-index: 1000;
    }
    
    @media (min-width: 992px) { 
        .bottom-nav { display: none !important; } 
    }

    .nav-item-m { text-align: center; color: rgba(255,255,255,0.3); text-decoration: none; font-size: 9px; flex: 1; }
    .nav-item-m.active { color: #fff; }
    .nav-item-m i { font-size: 18px; display: block; }
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

    <div class="container">
        <?php foreach ($order_items as $item): 
            $status = strtolower($item['item_status']);
            $due = strtotime($item['item_due_date']);
            $days_left = ($due - strtotime(date('Y-m-d'))) / 86400;

            $card_class = "";
            if ($status === 'completed') $card_class = "card-completed";
            elseif ($status === 'delivered') $card_class = "card-delivered";
            elseif ($days_left <= 1 && $status !== 'completed') $card_class = "card-critical";
        ?>
            <div class="glass-card <?= $card_class ?>">
                <div class="row align-items-center">
                    <div class="col-6">
                        <div class="fw-bold text-white mb-1" style="font-size: 14px;">
                            <?= htmlspecialchars($item['business_name']) ?>
                        </div>
                        <div class="text-white-50 small mb-1">
                            <?= htmlspecialchars($item['service_name']) ?> (x<?= $item['quantity'] ?>)
                        </div>
                        <div style="font-size: 11px; color: <?= ($days_left <= 1) ? 'var(--warning-red)' : '#777' ?>;">
                            <i class="bi bi-clock me-1"></i> Due: <?= date('M d', $due) ?>
                        </div>
                    </div>
                    <div class="col-6">
                        <select class="status-select" onchange="updateItemStatus(<?= $item['item_row_id'] ?>, this.value)">
                            <option value="processing" <?= $status == 'processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="completed" <?= $status == 'completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="statusToast">SAVED TO CLOUD...</div>

    <nav class="bottom-nav">
    <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
    <a href="employee_dashboard.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
    <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
</nav>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    function updateItemStatus(rowId, newStatus) {
        // Dropdown එක disable කරමු update වෙනකම්
        const selectBox = event.target;
        selectBox.style.opacity = "0.5";

        $.ajax({
            url: 'all_orders.php',
            type: 'POST',
            data: {
                update_status: true,
                item_row_id: rowId,
                new_status: newStatus
            },
            success: function(response) {
                if(response.includes("success")) {
                    $("#statusToast").fadeIn().delay(800).fadeOut();
                    setTimeout(() => { location.reload(); }, 1000);
                } else {
                    alert("Update Failed!");
                    selectBox.style.opacity = "1";
                }
            }
        });
    }
    </script>
</body>
</html>