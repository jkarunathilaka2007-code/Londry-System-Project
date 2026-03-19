<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: login.php");
    exit();
}

$employee_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$now = date('H:i:s');

// අද දවසේ attendance record එක බලමු
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND work_date = ?");
$stmt->execute([$employee_id, $today]);
$attendance = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['punch_in']) && !$attendance) {
        $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, work_date, in_time) VALUES (?, ?, ?)");
        $stmt->execute([$employee_id, $today, $now]);
        header("Location: attendance.php");
        exit();
    } 
    elseif (isset($_POST['punch_out']) && $attendance && !$attendance['out_time']) {
        $stmt = $pdo->prepare("UPDATE attendance SET out_time = ? WHERE id = ?");
        $stmt->execute([$now, $attendance['id']]);
        header("Location: attendance.php");
        exit();
    }
}

$work_duration = "";
if ($attendance && $attendance['in_time'] && $attendance['out_time']) {
    $start = new DateTime($attendance['in_time']);
    $end = new DateTime($attendance['out_time']);
    $interval = $start->diff($end);
    $work_duration = $interval->format('%h hours, %i minutes');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Attendance | Fabricare</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    
    <style>
    :root {
        --bg-dark: #080808;
        --glass: rgba(255, 255, 255, 0.04);
        --glass-border: rgba(255, 255, 255, 0.12);
        --accent-neon: #00f2fe;
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

    /* --- TOP NAV (FIXED TEXT COLOR) --- */
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

/* Attendance Title Specific Fix */
.top-nav .d-flex .ms-3 h5 {
    background: none !important;
    color: #fff !important;
}

    .back-btn, .history-link {
        width: 36px; height: 36px; border-radius: 10px;
        background: var(--glass); border: 1px solid var(--glass-border);
        display: flex; align-items: center; justify-content: center;
        color: #fff !important; text-decoration: none;
    }

    /* --- ATTENDANCE CARD --- */
    .attendance-card {
        background: var(--glass);
        backdrop-filter: blur(30px);
        border: 1px solid var(--glass-border);
        border-radius: 25px;
        padding: 30px 15px;
        text-align: center;
        margin-top: 20px;
    }

    .clock { 
        font-size: 3rem; 
        font-weight: 800; 
        color: #ffffff;
        letter-spacing: -1px;
        margin-bottom: 5px;
    }

    /* --- PUNCH BUTTONS (RESPONSIVE) --- */
    .btn-punch {
        width: 150px; height: 150px; 
        border-radius: 50%; 
        border: 4px solid var(--glass-border);
        font-weight: 800; font-size: 0.9rem;
        transition: 0.3s ease-in-out;
        margin: 20px 0; 
        display: inline-flex; flex-direction: column;
        align-items: center; justify-content: center;
        text-transform: uppercase;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }

    .btn-in { background: #ffffff !important; color: #000000 !important; }
    .btn-out { background: rgba(255, 59, 48, 0.15) !important; color: #ff3b30 !important; border-color: rgba(255, 59, 48, 0.3); }

    .btn-punch:hover { transform: scale(1.03); }
    .btn-punch:active { transform: scale(0.95); }

    .duration-display {
        background: rgba(255, 255, 255, 0.03);
        border: 1px dashed var(--glass-border);
        border-radius: 15px; padding: 15px; margin-top: 20px;
    }

    /* --- MOBILE ONLY RESPONSIVE --- */
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
                <h5 class="m-0 fw-bold" style="background: linear-gradient(to right, #fff, var(--primary-neon)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Attendance</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Workshop Terminal</small>
            </div>
        </div>

        <div class="right-actions">
            <a href="attendance_history.php" class="history-link" title="History">
                <i class="bi bi-clock-history"></i>
            </a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="attendance-card">
                <div class="mb-4">
                    <div class="clock" id="liveClock">00:00:00</div>
                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3 py-2 rounded-pill text-uppercase" style="font-size: 10px; letter-spacing: 1px;">
                        <?= date('l, d F Y') ?>
                    </span>
                </div>

                <form method="POST">
                    <?php if (!$attendance): ?>
                        <button type="submit" name="punch_in" class="btn-punch btn-in">
                            <i class="bi bi-fingerprint d-block fs-1"></i>
                            PUNCH IN
                        </button>
                        <p class="text-white-50">Mark your arrival for today</p>
                    <?php elseif (!$attendance['out_time']): ?>
                        <button type="submit" name="punch_out" class="btn-punch btn-out">
                            <i class="bi bi-box-arrow-right d-block fs-1"></i>
                            PUNCH OUT
                        </button>
                        <p class="text-white-50">Punched in at: <b class="text-info"><?= date('h:i A', strtotime($attendance['in_time'])) ?></b></p>
                    <?php else: ?>
                        <button class="btn-punch" disabled>
                            <i class="bi bi-shield-check d-block fs-1"></i>
                            COMPLETED
                        </button>
                        <div class="duration-display">
                            <small class="text-white-50 d-block mb-1">Total Hours Worked</small>
                            <h4 class="m-0 fw-bold text-info"><?= $work_duration ?></h4>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="mobile-nav">
    <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
    <a href="employee_dashboard.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
    <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
</div>

<script>
    function updateClock() {
        const now = new Date();
        const time = now.toLocaleTimeString('en-US', { hour12: false });
        document.getElementById('liveClock').textContent = time;
    }
    setInterval(updateClock, 1000);
    updateClock();
</script>

</body>
</html>