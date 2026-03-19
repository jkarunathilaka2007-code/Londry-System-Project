<?php
session_start();
require 'config.php';

// 1. ආරක්ෂාව සහ Role එක පරීක්ෂා කිරීම
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Form එකෙන් එන දත්ත ලබාගැනීම
    $qtys = $_POST['qty'] ?? []; 
    $item_dates = $_POST['item_date'] ?? [];
    $order_date = $_POST['order_date'];
    $global_due_date = $_POST['due_date']; 
    $pickup_req = isset($_POST['pickup_required']) ? 1 : 0;

    if (empty($qtys)) {
        header("Location: add_order.php?error=no_items");
        exit();
    }

    try {
        $pdo->beginTransaction();

        $grand_total = 0;
        $order_items_to_save = [];

        // 2. සර්විස් වල ගණන් හැදීම
        foreach ($qtys as $service_id => $quantity) {
            $quantity = floatval($quantity);
            if ($quantity <= 0) continue;

            $stmt = $pdo->prepare("SELECT service_name, price FROM services WHERE id = ?");
            $stmt->execute([$service_id]);
            $service = $stmt->fetch();

            if ($service) {
                $subtotal = $service['price'] * $quantity;
                $grand_total += $subtotal;

                $specific_due_date = (!empty($item_dates[$service_id])) ? $item_dates[$service_id] : $global_due_date;

                $order_items_to_save[] = [
                    'service_id' => $service_id,
                    'qty' => $quantity,
                    'price' => $service['price'],
                    'subtotal' => $subtotal,
                    'due_date' => $specific_due_date
                ];
            }
        }

        // 3. Orders Table එකට Insert කිරීම 
        // 🔥 මෙතනින් 'status' column එක අයින් කළා මොකද දැන් ඒක මේ table එකේ නැති නිසා
        $sql_order = "INSERT INTO orders (customer_id, total_price, pickup_required, order_date) 
                      VALUES (?, ?, ?, ?)";
        $stmt_order = $pdo->prepare($sql_order);
        $stmt_order->execute([$user_id, $grand_total, $pickup_req, $order_date]);
        
        $new_order_id = $pdo->lastInsertId();

        // 4. Order Items Table එකට Insert කිරීම
        // 🔥 මෙතන status එක default 'pending' විදිහට වැටෙනවා (DB එකේ default දාලා තියෙන නිසා)
        $sql_item = "INSERT INTO order_items (order_id, service_id, quantity, unit_price, subtotal, item_due_date, status) 
                     VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        $stmt_item = $pdo->prepare($sql_item);
        
        foreach ($order_items_to_save as $item) {
            $stmt_item->execute([
                $new_order_id,
                $item['service_id'],
                $item['qty'],
                $item['price'],
                $item['subtotal'],
                $item['due_date']
            ]);
        }

        $pdo->commit();
        header("Location: orders.php?success=placed&order_id=" . $new_order_id);
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Transaction Failed: " . $e->getMessage());
    }
} else {
    header("Location: add_order.php");
    exit();
}