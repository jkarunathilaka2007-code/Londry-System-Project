<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: login.php");
    exit();
}

$employee_id = $_SESSION['user_id'];

// මාසය සහ අවුරුද්ද තෝරාගැනීම
$view_month = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('m');
$view_year = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');

// Prev/Next මාස ගණනය කිරීම
$prev_date = date('Y-m', strtotime("$view_year-$view_month-01 -1 month"));
$next_date = date('Y-m', strtotime("$view_year-$view_month-01 +1 month"));
$prev_m = explode('-', $prev_date)[1]; $prev_y = explode('-', $prev_date)[0];
$next_m = explode('-', $next_date)[1]; $next_y = explode('-', $next_date)[0];

// දත්ත ලබාගැනීම
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND MONTH(work_date) = ? AND YEAR(work_date) = ?");
$stmt->execute([$employee_id, $view_month, $view_year]);
$history = $stmt->fetchAll();

// Total Hours
$total_stmt = $pdo->prepare("SELECT SUM(TIME_TO_SEC(TIMEDIFF(out_time, in_time))) as total_seconds FROM attendance WHERE employee_id = ? AND MONTH(work_date) = ? AND YEAR(work_date) = ? AND out_time IS NOT NULL");
$total_stmt->execute([$employee_id, $view_month, $view_year]);
$total_data = $total_stmt->fetch();
$t_seconds = $total_data['total_seconds'] ?? 0;
$m_hours = floor($t_seconds / 3600);
$m_minutes = floor(($t_seconds % 3600) / 60);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Attendance Map | Fabricare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-dark: #080808;
            --glass: rgba(255, 255, 255, 0.04);
            --glass-border: rgba(255, 255, 255, 0.12);
            --accent-neon: #00f2fe;
        }

        html, body { 
            height: 100vh; width: 100vw; overflow: hidden; margin: 0;
        }

        body { 
            background-color: var(--bg-dark);
            background-image: linear-gradient(rgba(8, 8, 8, 0.97), rgba(8, 8, 8, 0.97)), 
                              url('uploads/resources/bg2.jpg');
            background-size: cover; background-position: center; background-attachment: fixed;
            font-family: 'Plus Jakarta Sans', sans-serif; color: #ffffff;
            display: flex; flex-direction: column;
        }

        /* --- TOP NAV --- */
        .top-nav {
            background: rgba(10, 10, 10, 0.9); backdrop-filter: blur(30px);
            border-bottom: 1px solid var(--glass-border); padding: 10px 0;
        }
        .top-nav h5 { color: #ffffff !important; font-weight: 700; margin: 0; font-size: 1.1rem; }
        .back-btn {
            width: 36px; height: 36px; border-radius: 10px; background: var(--glass);
            border: 1px solid var(--glass-border); display: flex; align-items: center; justify-content: center;
            color: #fff !important; text-decoration: none;
        }

        /* --- CONTENT --- */
        .main-content { flex: 1; display: flex; flex-direction: column; padding: 15px; justify-content: center; max-width: 500px; margin: 0 auto; width: 100%; }

        .summary-mini {
            background: var(--glass); border: 1px solid var(--glass-border);
            border-left: 4px solid var(--accent-neon); border-radius: 15px; padding: 12px; margin-bottom: 15px;
        }

        /* --- CALENDAR --- */
        .calendar-container {
            background: var(--glass); backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border); border-radius: 25px; padding: 18px;
        }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .month-nav-btn {
            background: var(--glass); border: 1px solid var(--glass-border);
            color: #fff; border-radius: 8px; width: 34px; height: 34px; 
            display: flex; align-items: center; justify-content: center; text-decoration: none;
        }

        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; text-align: center; }
        .day-name { font-size: 0.65rem; color: var(--accent-neon); font-weight: 800; padding-bottom: 8px; opacity: 0.8; }
        .calendar-day {
            aspect-ratio: 1/1; display: flex; align-items: center; justify-content: center;
            border-radius: 10px; font-size: 0.85rem; cursor: pointer; transition: 0.2s;
            background: rgba(255,255,255,0.02); border: 1px solid transparent;
        }
        .day-present {
            background: rgba(0, 242, 254, 0.2) !important; color: var(--accent-neon);
            border: 1px solid var(--accent-neon) !important; font-weight: 700;
            box-shadow: 0 0 10px rgba(0, 242, 254, 0.1);
        }

        /* --- INFO POPUP --- */
        .info-layer {
            position: fixed; top: -100%; left: 50%; transform: translateX(-50%);
            width: 85%; max-width: 320px; background: rgba(10, 10, 10, 0.98);
            backdrop-filter: blur(30px); border: 1px solid var(--accent-neon);
            border-radius: 20px; padding: 25px; z-index: 2000; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .info-layer.active { top: 20%; }
        #calendarOverlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); display: none; z-index: 1999; }

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
        .nav-item-m i { font-size: 20px; display: block; margin-bottom: 2px; }
    </style>
</head>
<body>

<nav class="top-nav">
    <div class="container d-flex align-items-center">
        <a href="attendance.php" class="back-btn"><i class="bi bi-chevron-left"></i></a>
        <div class="ms-3">
            <h5 class="fw-bold">My Records</h5>
        </div>
    </div>
</nav>

<div class="main-content">
    <div class="summary-mini">
        <small class="text-white-50 d-block mb-1" style="font-size: 0.6rem; letter-spacing: 1px; text-transform: uppercase;">Work Duration</small>
        <h4 class="m-0 fw-bold" style="color: var(--accent-neon);"><?= $m_hours ?>h <?= $m_minutes ?>m</h4>
    </div>

    <div class="calendar-container">
        <div class="calendar-header">
            <a href="?m=<?= $prev_m ?>&y=<?= $prev_y ?>" class="month-nav-btn"><i class="bi bi-chevron-left"></i></a>
            <h6 class="m-0 fw-bold"><?= date('F Y', strtotime("$view_year-$view_month-01")) ?></h6>
            <a href="?m=<?= $next_m ?>&y=<?= $next_y ?>" class="month-nav-btn"><i class="bi bi-chevron-right"></i></a>
        </div>
        
        <div class="calendar-grid">
            <div class="day-name">S</div><div class="day-name">M</div><div class="day-name">T</div>
            <div class="day-name">W</div><div class="day-name">T</div><div class="day-name">F</div><div class="day-name">S</div>

            <?php
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $view_month, $view_year);
            $firstDay = date('w', strtotime("$view_year-$view_month-01"));
            for ($i = 0; $i < $firstDay; $i++) echo '<div></div>';

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = "$view_year-" . sprintf("%02d", $view_month) . "-" . sprintf("%02d", $day);
                $present = false;
                $details = "null";

                foreach ($history as $row) {
                    if ($row['work_date'] == $currentDate) {
                        $present = true;
                        $duration = ($row['in_time'] && $row['out_time']) ? (new DateTime($row['in_time']))->diff(new DateTime($row['out_time']))->format('%hH %iM') : 'In Progress';
                        $details = json_encode([
                            'date' => date('M d, Y', strtotime($currentDate)),
                            'in' => date('h:i A', strtotime($row['in_time'])),
                            'out' => $row['out_time'] ? date('h:i A', strtotime($row['out_time'])) : '-',
                            'work' => $duration
                        ]);
                        break;
                    }
                }
                $class = $present ? 'day-present' : '';
                echo "<div class='calendar-day $class' onclick='showInfo($details)'>$day</div>";
            }
            ?>
        </div>
    </div>
</div>

<div id="calendarOverlay" onclick="closeInfo()"></div>
<div id="infoLayer" class="info-layer">
    <div class="text-center mb-3">
        <h6 id="popDate" class="text-info fw-bold m-0">Date</h6>
        <hr class="border-secondary opacity-25">
    </div>
    <div class="d-flex justify-content-between mb-2"><span class="small opacity-50">Punch In:</span> <b id="popIn" class="small">-</b></div>
    <div class="d-flex justify-content-between mb-2"><span class="small opacity-50">Punch Out:</span> <b id="popOut" class="small">-</b></div>
    <div class="d-flex justify-content-between text-info mt-2 pt-2 border-top border-secondary border-opacity-25"><span>Total:</span> <b id="popWork" class="small">-</b></div>
    <button class="btn btn-sm btn-outline-light w-100 mt-4 rounded-pill opacity-75" onclick="closeInfo()">Close</button>
</div>

<div class="mobile-nav">
    <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
    <a href="employee_dashboard.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
    <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
</div>

<script>
function showInfo(data) {
    if (!data) return;
    document.getElementById('popDate').innerText = data.date;
    document.getElementById('popIn').innerText = data.in;
    document.getElementById('popOut').innerText = data.out;
    document.getElementById('popWork').innerText = data.work;
    document.getElementById('calendarOverlay').style.display = 'block';
    document.getElementById('infoLayer').classList.add('active');
}
function closeInfo() {
    document.getElementById('calendarOverlay').style.display = 'none';
    document.getElementById('infoLayer').classList.remove('active');
}
</script>
</body>
</html>