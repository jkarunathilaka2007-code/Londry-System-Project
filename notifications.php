<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$my_id = $_SESSION['user_id'];
$my_role = $_SESSION['role'];

// --- DELETE LOGIC ---
if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    $stmt_file = $pdo->prepare("SELECT media_url FROM notifications WHERE id = ? AND sender_id = ? AND sender_role = ?");
    $stmt_file->execute([$del_id, $my_id, $my_role]);
    $notif = $stmt_file->fetch();

    if ($notif) {
        if (!empty($notif['media_url'])) {
            $file_path = "uploads/notifications/" . $notif['media_url'];
            if (file_exists($file_path)) { unlink($file_path); }
        }
        $pdo->prepare("DELETE FROM notifications WHERE id = ?")->execute([$del_id]);
        header("Location: notifications.php?status=deleted");
        exit();
    }
}

// --- QUERIES ---
$inbox_query = "SELECT n.*, 
                CASE 
                    WHEN n.sender_role = 'admin' THEN (SELECT gmail FROM system_admin WHERE id = n.sender_id)
                    WHEN n.sender_role = 'employee' THEN (SELECT full_name FROM employees WHERE id = n.sender_id)
                    WHEN n.sender_role = 'customer' THEN (SELECT full_name FROM customers WHERE id = n.sender_id)
                END as sender_name
                FROM notifications n WHERE n.receiver_id = ? AND n.receiver_role = ? ORDER BY n.created_at DESC";
$stmt_inbox = $pdo->prepare($inbox_query);
$stmt_inbox->execute([$my_id, $my_role]);
$inbox_messages = $stmt_inbox->fetchAll(PDO::FETCH_ASSOC);

$sent_query = "SELECT n.*, 
               CASE 
                   WHEN n.receiver_role = 'admin' THEN (SELECT gmail FROM system_admin WHERE id = n.receiver_id)
                   WHEN n.receiver_role = 'employee' THEN (SELECT full_name FROM employees WHERE id = n.receiver_id)
                   WHEN n.receiver_role = 'customer' THEN (SELECT full_name FROM customers WHERE id = n.receiver_id)
               END as receiver_name
               FROM notifications n WHERE n.sender_id = ? AND n.sender_role = ? ORDER BY n.created_at DESC";
$stmt_sent = $pdo->prepare($sent_query);
$stmt_sent->execute([$my_id, $my_role]);
$sent_messages = $stmt_sent->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Messages | Fabricare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --accent: #ffffff; /* Blue අයින් කරලා White කළා */
            --card-bg: rgba(255, 255, 255, 0.08);
            --border: rgba(255, 255, 255, 0.15);
        }

        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: #fff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: url('uploads/resources/bg2.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
        }

        /* Dark Layer Overlay */
        body::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.85); /* කළු ලේයර් එක */
            z-index: -1;
        }

        .header-bar {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-tabs-custom {
            display: flex;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            margin: 15px;
            padding: 4px;
            border: 1px solid var(--border);
        }
        .nav-tabs-custom .nav-link {
            flex: 1; border: none; color: #aaa;
            font-size: 0.85rem; padding: 10px;
            border-radius: 7px; text-align: center;
            background: transparent;
        }
        .nav-tabs-custom .nav-link.active {
            background: #fff; color: #000; font-weight: bold;
        }

        .msg-bubble {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 15px;
            margin: 0 15px 15px 15px;
        }

        .sender-name { font-size: 0.95rem; font-weight: 700; color: #fff; }
        .msg-text { font-size: 0.88rem; color: rgba(255,255,255,0.7); line-height: 1.5; margin-top: 5px; }
        .time-text { font-size: 0.65rem; opacity: 0.4; margin-top: 10px; }

        .media-thumb { 
            width: 100%; max-width: 250px; height: 140px; 
            object-fit: cover; border-radius: 10px; 
            margin-top: 12px; border: 1px solid var(--border); 
        }

        .btn-compose {
            position: fixed; bottom: 25px; right: 20px;
            width: 55px; height: 55px; border-radius: 50%;
            background: #fff; color: #000;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; box-shadow: 0 5px 15px rgba(255,255,255,0.2);
            z-index: 999; text-decoration: none;
        }

        .role-tag {
            font-size: 0.6rem; border: 1px solid rgba(255,255,255,0.3);
            padding: 1px 6px; border-radius: 4px; margin-left: 8px;
            text-transform: uppercase; opacity: 0.6;
        }
            /* --- TOP NAV (FIXED) --- */
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
            <a href="index.php" class="back-btn"><i class="bi bi-chevron-left"></i></a>
            <div class="ms-3">
                <h5 class="m-0 fw-bold" style="background: linear-gradient(to right, #fff, var(--primary-neon)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Attendance</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Workshop Terminal</small>
            </div>
        </div>
    </div>
</nav>


<div class="nav nav-tabs-custom" role="tablist">
    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#inbox">Inbox</button>
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sent">Sent Items</button>
</div>

<div class="tab-content pb-5">
    <div class="tab-pane fade show active" id="inbox">
        <?php if(empty($inbox_messages)): ?>
            <div class="text-center py-5 opacity-25 small">No new messages</div>
        <?php else: ?>
            <?php foreach($inbox_messages as $msg): ?>
                <div class="msg-bubble">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="sender-name">
                            <?= htmlspecialchars($msg['sender_name']) ?>
                            <span class="role-tag"><?= $msg['sender_role'] ?></span>
                        </div>
                        <span class="time-text"><?= date('h:i A', strtotime($msg['created_at'])) ?></span>
                    </div>
                    <div class="msg-text"><?= nl2br(htmlspecialchars($msg['message_text'])) ?></div>
                    
                    <?php if($msg['media_url']): ?>
                        <?php if($msg['media_type'] == 'image'): ?>
                            <img src="uploads/notifications/<?= $msg['media_url'] ?>" class="media-thumb" onclick="window.open(this.src)">
                        <?php else: ?>
                            <video src="uploads/notifications/<?= $msg['media_url'] ?>" controls class="media-thumb"></video>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="tab-pane fade" id="sent">
        <?php if(empty($sent_messages)): ?>
            <div class="text-center py-5 opacity-25 small">No sent items</div>
        <?php else: ?>
            <?php foreach($sent_messages as $msg): ?>
                <div class="msg-bubble" style="border-left: 2px solid #fff;">
                    <div class="d-flex justify-content-between">
                        <span class="small opacity-50 fw-bold">To: <?= $msg['receiver_name'] ?></span>
                        <div class="dropdown">
                            <i class="bi bi-three-dots opacity-50" data-bs-toggle="dropdown"></i>
                            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow">
                                <li><a class="dropdown-item small" href="edit_notification.php?id=<?= $msg['id'] ?>"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                <li><a class="dropdown-item small text-danger" href="?delete_id=<?= $msg['id'] ?>" onclick="return confirm('Delete message and file?')"><i class="bi bi-trash me-2"></i>Delete</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="msg-text"><?= nl2br(htmlspecialchars($msg['message_text'])) ?></div>
                    <div class="time-text"><?= date('M d, Y', strtotime($msg['created_at'])) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<a href="add_notification.php" class="btn-compose">
    <i class="bi bi-plus-lg"></i>
</a>
<div class="mobile-nav">
    <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
    <a href="add_notification.php" class="nav-item-m active"><i class="bi bi-plus"></i><span>create</span></a>
    <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>