<?php
session_start();
require 'config.php';

// ඇඩ්මින් පරීක්ෂාව
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// AJAX: Designation එක සිලෙක්ට් කළ විට පරණ දත්ත ලබා ගැනීම
if (isset($_GET['fetch_designation'])) {
    $dsg = $_GET['fetch_designation'];
    $stmt = $pdo->prepare("SELECT * FROM salary_structures WHERE designation = ?");
    $stmt->execute([$dsg]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        $data['service_commissions'] = json_decode($data['service_commissions'], true);
        echo json_encode($data);
    } else {
        echo json_encode(null);
    }
    exit;
}

try {
    $services = $pdo->query("SELECT id, service_name, service_image FROM services ORDER BY service_name ASC")->fetchAll();
    $designations = $pdo->query("SELECT DISTINCT designation FROM employees WHERE designation IS NOT NULL AND designation != ''")->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$msg = "";

// දත්ත සේව් කිරීම සහ UPDATE කිරීම
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_structure'])) {
    $designation = $_POST['designation'];
    $basic_salary = floatval($_POST['basic_salary']);
    $bonus = floatval($_POST['bonus']);
    
    $commissions = [];
    if (isset($_POST['service_rate'])) {
        foreach ($_POST['service_rate'] as $s_id => $rate) {
            if ($rate !== '' && $rate !== null) {
                $commissions[$s_id] = floatval($rate);
            }
        }
    }
    $comm_json = json_encode($commissions);

    try {
        $stmt = $pdo->prepare("INSERT INTO salary_structures (designation, basic_salary, service_commissions, bonus) 
                               VALUES (?, ?, ?, ?) 
                               ON DUPLICATE KEY UPDATE basic_salary = VALUES(basic_salary), 
                                                       service_commissions = VALUES(service_commissions), 
                                                       bonus = VALUES(bonus)");
        $stmt->execute([$designation, $basic_salary, $comm_json, $bonus]);
        
        $msg = "<div class='alert alert-success border-0 bg-success bg-opacity-10 text-success rounded-4 shadow-sm mb-4'>
                    <i class='bi bi-check-circle-fill me-2'></i>Structure for <b>$designation</b> saved successfully!
                </div>";
    } catch (PDOException $e) {
        $msg = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Salary Configurator | Fabricare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary-neon: #00f2fe; 
            --dark-deep: #050506; 
            --glass-border: rgba(255, 255, 255, 0.1); 
            --glass-bg: rgba(255, 255, 255, 0.03); 
        }
        
        body { 
            background: radial-gradient(circle at 50% 0%, #1a1a2e 0%, var(--dark-deep) 100%);
            color: #fff; font-family: 'Space Grotesk', sans-serif; margin: 0; padding-top: 85px; padding-bottom: 80px;
            overflow-x: hidden;
        }

        /* --- TOP NAV & BACK BTN --- */
        .top-nav {
            background: rgba(8, 8, 10, 0.85); backdrop-filter: blur(25px);
            border-bottom: 1px solid var(--glass-border); padding: 15px 0;
            position: fixed; top: 0; width: 100%; z-index: 1000;
        }

        .back-btn {
            width: 42px; height: 42px; border-radius: 12px;
            background: rgba(255, 255, 255, 0.05); border: 1px solid var(--glass-border);
            display: flex; align-items: center; justify-content: center;
            color: var(--primary-neon); text-decoration: none;
        }

        /* --- CARDS & INPUTS --- */
        .glass-card { background: var(--glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); border-radius: 24px; padding: 25px; }
        
        .form-control, .form-select { 
            background: rgba(0,0,0,0.4) !important; border: 1px solid var(--glass-border) !important; 
            color: #fff !important; border-radius: 12px; padding: 12px; transition: 0.3s;
        }
        .form-control:focus, .form-select:focus { border-color: var(--primary-neon) !important; box-shadow: 0 0 15px rgba(0, 242, 254, 0.2); }

        .service-item { 
            background: rgba(255,255,255,0.02); border: 1px solid var(--glass-border); 
            border-radius: 18px; padding: 12px; transition: 0.3s;
        }
        .service-item:hover { background: rgba(255,255,255,0.05); border-color: var(--primary-neon); }
        .service-img { width: 40px; height: 40px; border-radius: 10px; object-fit: cover; border: 1px solid var(--glass-border); }

        .btn-save { 
            background: linear-gradient(135deg, #00f2fe 0%, #00c6ff 100%); 
            color: #000; font-weight: 800; border: none; border-radius: 14px; padding: 14px; 
            box-shadow: 0 4px 15px rgba(0, 242, 254, 0.3); transition: 0.3s;
        }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0, 242, 254, 0.5); }

        /* --- BOTTOM NAV (MOBILE ONLY) --- */
        .bottom-nav {
            position: fixed; bottom: 0; left: 0; right: 0; height: 65px;
            background: rgba(5, 5, 5, 0.95); backdrop-filter: blur(15px);
            border-top: 1px solid var(--glass-border); display: none;
            justify-content: space-around; align-items: center; z-index: 1000;
        }
        .nav-item-m { text-align: center; color: rgba(255,255,255,0.4); text-decoration: none; font-size: 10px; flex: 1; }
        .nav-item-m.active { color: var(--primary-neon); }
        .nav-item-m i { font-size: 20px; display: block; }

        @media (max-width: 768px) {
            .bottom-nav { display: flex; }
            .glass-card { padding: 20px; }
        }

        .loading-shimmer { opacity: 0.4; pointer-events: none; }
    </style>
</head>
<body>

<nav class="top-nav">
    <div class="container d-flex align-items-center justify-content-between px-3">
        <div class="d-flex align-items-center">
            <a href="salaries.php" class="back-btn"><i class="bi bi-chevron-left"></i></a>
            <div class="ms-3">
                <h5 class="m-0 fw-bold" style="background: linear-gradient(to right, #fff, var(--primary-neon)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Salary Structure</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Configurator</small>
            </div>
        </div>
    </div>
</nav>

<div class="container pb-4 mt-2">
    <?= $msg ?>

    <form method="POST" id="salaryForm">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="glass-card mb-4" id="generalSettings">
                    <h6 class="fw-bold mb-4 text-info text-uppercase" style="letter-spacing: 1px;">General Settings</h6>
                    
                    <div class="mb-3">
                        <label class="small text-white-50 mb-2">Designation</label>
                        <select name="designation" id="designationSelect" class="form-select" required>
                            <option value="">-- Select Role --</option>
                            <?php foreach($designations as $d): ?>
                                <option value="<?= htmlspecialchars($d['designation']) ?>"><?= htmlspecialchars($d['designation']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small text-white-50 mb-2">Basic Salary (LKR)</label>
                        <input type="number" name="basic_salary" id="basic_salary" class="form-control" placeholder="0.00" step="0.01" required>
                    </div>

                    <div class="mb-4">
                        <label class="small text-white-50 mb-2">Monthly Bonus (LKR)</label>
                        <input type="number" name="bonus" id="bonus" class="form-control" placeholder="0.00" step="0.01" value="0">
                    </div>

                    <button type="submit" name="save_structure" class="btn btn-save w-100">
                        <i class="bi bi-shield-check me-2"></i>SAVE CHANGES
                    </button>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="glass-card" id="commissionBox">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                        <h6 class="fw-bold m-0 text-info text-uppercase" style="letter-spacing: 1px;">Service Commissions</h6>
                        <div class="input-group" style="max-width: 300px;">
                            <span class="input-group-text bg-transparent border-secondary border-opacity-25 text-white-50"><i class="bi bi-search"></i></span>
                            <input type="text" id="serviceSearch" class="form-control form-control-sm" placeholder="Search service...">
                        </div>
                    </div>

                    <div class="row g-3" id="serviceList">
                        <?php foreach($services as $s): ?>
                            <div class="col-md-6 service-card" data-name="<?= strtolower($s['service_name']) ?>">
                                <div class="service-item d-flex align-items-center gap-3">
                                    <img src="uploads/services/<?= $s['service_image'] ?>" class="service-img" onerror="this.src='assets/img/default-service.png'">
                                    <div class="flex-grow-1">
                                        <div class="small fw-bold"><?= $s['service_name'] ?></div>
                                        <div class="text-white-50 tiny" style="font-size: 9px;">LKR / UNIT</div>
                                    </div>
                                    <div style="width: 90px;">
                                        <input type="number" name="service_rate[<?= $s['id'] ?>]" data-service-id="<?= $s['id'] ?>" class="form-control form-control-sm text-end comm-input" placeholder="0" step="0.01">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="bottom-nav">
    <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
    <a href="admin_dashboard.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
    <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // 1. Fetching Data on Designation Change
    $('#designationSelect').on('change', function() {
        let role = $(this).val();
        if (!role) {
            $('#salaryForm')[0].reset();
            return;
        }

        $('#generalSettings, #commissionBox').addClass('loading-shimmer');

        $.ajax({
            url: 'salary_structure.php',
            type: 'GET',
            data: { fetch_designation: role },
            success: function(response) {
                let data = JSON.parse(response);
                
                $('#basic_salary').val('');
                $('#bonus').val('0');
                $('.comm-input').val('');

                if (data) {
                    $('#basic_salary').val(data.basic_salary);
                    $('#bonus').val(data.bonus);
                    
                    if (data.service_commissions) {
                        Object.keys(data.service_commissions).forEach(id => {
                            $(`.comm-input[data-service-id="${id}"]`).val(data.service_commissions[id]);
                        });
                    }
                }
                $('#generalSettings, #commissionBox').removeClass('loading-shimmer');
            }
        });
    });

    // 2. Real-time Search Logic
    $('#serviceSearch').on('input', function() {
        let q = $(this).val().toLowerCase();
        $('.service-card').each(function() {
            $(this).toggle($(this).data('name').includes(q));
        });
    });
});
</script>

</body>
</html>