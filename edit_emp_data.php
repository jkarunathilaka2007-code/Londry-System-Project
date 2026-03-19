<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: login.php");
    exit();
}

$emp_id = $_SESSION['user_id'];
$msg = "";

// 1. Employee දත්ත ලබාගැනීම
try {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$emp_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

// 2. Personal Data Update කිරීම
if (isset($_POST['update_personal'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $emergency = $_POST['emergency_contact'];

    try {
        $up = $pdo->prepare("UPDATE employees SET full_name=?, email=?, phone=?, emergency_contact=? WHERE id=?");
        if ($up->execute([$full_name, $email, $phone, $emergency, $emp_id])) {
            $msg = "Profile updated successfully!";
            header("Refresh:1"); // දත්ත update වූ පසු page එක refresh කිරීමට
        }
    } catch (PDOException $e) { $msg = "Error: " . $e->getMessage(); }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | Fabricare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { 
            --dark-deep: #0f172a;
            --neon: #ffffff;
            --border: rgba(255, 255, 255, 0.1);
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
        .settings-card {
            background: rgba(10, 10, 10, 0.9); backdrop-filter: blur(20px);
            border: 1px solid var(--border); border-radius: 20px; padding: 30px;
        }
        .nav-tabs { border: none; margin-bottom: 30px; gap: 10px; }
        .nav-link { 
            color: #94a3b8; border: 1px solid var(--border) !important; 
            border-radius: 12px !important; padding: 10px 25px; transition: 0.3s;
        }
        .nav-link.active { background: var(--neon) !important; color: #0f172a !important; border: none !important; font-weight: 600; }
        .form-control { 
            background: rgba(255,255,255,0.03); border: 1px solid var(--border); 
            color: #fff; border-radius: 10px; padding: 12px;
        }
        .form-control:focus { background: rgba(255,255,255,0.05); color: #fff; border-color: var(--neon); box-shadow: none; }
        label { font-size: 13px; color: var(--neon); margin-bottom: 8px; font-weight: 600; text-transform: uppercase; }
        .btn-update { background: var(--neon); color: #0f172a; font-weight: 700; border-radius: 10px; padding: 12px; border: none; }
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
        width: 34px; height: 34px; border-radius: 8px;
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
    </div>
</nav>

<div class="container py-5">
    <div class="max-width mx-auto" style="max-width: 700px;">
        

        <?php if($msg): ?>
            <div class="alert alert-info bg-dark text-info border-info small"><?= $msg ?></div>
        <?php endif; ?>

        <div class="settings-card shadow-xl">
            <ul class="nav nav-tabs" id="settingsTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button">Personal Info</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button">Security</button>
                </li>
            </ul>

            <div class="tab-content" id="settingsTabContent">
                <div class="tab-pane fade show active" id="personal">
                    <form action="" method="POST">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label>Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label>Email Address</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label>Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label>NIC Number</label>
                                <input type="text" class="form-control opacity-50" value="<?= htmlspecialchars($user['nic_number']) ?>" readonly>
                                <small class="text-white-50" style="font-size: 10px;">Contact admin to change NIC</small>
                            </div>
                            <div class="col-md-6">
                                <label>Emergency Contact</label>
                                <input type="text" name="emergency_contact" class="form-control" value="<?= htmlspecialchars($user['emergency_contact']) ?>">
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" name="update_personal" class="btn btn-update w-100">SAVE CHANGES</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="tab-pane fade" id="password">
                    <div id="verify-section">
                        <label>Current Password</label>
                        <div class="input-group mb-3">
                            <input type="password" id="curr_pass" class="form-control" placeholder="Enter current password to verify">
                            <button class="btn btn-outline-info" type="button" onclick="verifyPassword()">CHECK</button>
                        </div>
                        <div id="verify-msg"></div>
                    </div>

                    <div id="new-pass-section" style="display: none;" class="animate__animated animate__fadeIn">
                        <div class="alert alert-success bg-success bg-opacity-10 border-success text-success small">Password Verified! You can now set a new one.</div>
                        <div class="mb-3">
                            <label>New Password</label>
                            <input type="password" id="new_pass" class="form-control" placeholder="Enter new password">
                        </div>
                        <div class="mb-3">
                            <label>Confirm New Password</label>
                            <input type="password" id="confirm_pass" class="form-control" placeholder="Repeat new password">
                        </div>
                        <button type="button" onclick="updatePassword()" class="btn btn-update w-100">UPDATE PASSWORD</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="mobile-nav">
    <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
    <a href="employee_dashboard.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
    <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
// 1. Password එක Verify කරන AJAX එක
function verifyPassword() {
    const curr = $('#curr_pass').val();
    if(!curr) return alert("Please enter current password");

    $.post('ajax_verify_pass.php', { password: curr }, function(res) {
        if(res.trim() === 'success') {
            $('#verify-section').hide();
            $('#new-pass-section').show();
        } else {
            $('#verify-msg').html('<small class="text-danger">Password incorrect. Please try again.</small>');
        }
    });
}

// 2. New Password එක Update කරන එක
function updatePassword() {
    const p1 = $('#new_pass').val();
    const p2 = $('#confirm_pass').val();

    if(p1 !== p2) return alert("Passwords do not match!");
    if(p1.length < 4) return alert("Password too short!");

    $.post('ajax_update_pass.php', { new_pass: p1 }, function(res) {
        if(res.trim() === 'success') {
            alert("Password updated successfully!");
            location.reload();
        } else {
            alert("Error updating password.");
        }
    });
}
</script>

</body>
</html>