<?php
session_start();
require 'config.php';

// User check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$cu_id = $_SESSION['user_id'];
$msg = "";

// 1. Data Fetch
try {
    $stmt = $pdo->prepare("SELECT c.*, co.id AS biz_id, co.business_name, co.business_type, co.reg_number, co.business_address, co.business_logo 
                           FROM customers c 
                           LEFT JOIN companies co ON c.company_id = co.id 
                           WHERE c.id = ?");
    $stmt->execute([$cu_id]);
    $user = $stmt->fetch();
    $company_id = $user['biz_id'];
} catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }

// 2. Save Logic
if (isset($_POST['save_all'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email_address'];
    $phone = $_POST['phone_number'];
    $biz_name = $_POST['business_name'];
    $biz_type = $_POST['business_type'];
    $biz_reg = $_POST['reg_number'];
    $biz_addr = $_POST['business_address'];

    try {
        $pdo->beginTransaction();

        // --- Profile Image ---
        $p_img = $user['profile_image'];
        if (!empty($_FILES['profile_img']['name'])) {
            if ($user['profile_image'] && file_exists('uploads/profiles/'.$user['profile_image'])) {
                @unlink('uploads/profiles/'.$user['profile_image']);
            }
            $p_img = 'USER_' . time() . '_' . $_FILES['profile_img']['name'];
            move_uploaded_file($_FILES['profile_img']['tmp_name'], 'uploads/profiles/' . $p_img);
        }

        // --- Business Logo ---
        $b_img = $user['business_logo'];
        if (!empty($_FILES['biz_logo']['name'])) {
            if ($user['business_logo'] && file_exists('uploads/logos/'.$user['business_logo'])) {
                @unlink('uploads/logos/'.$user['business_logo']);
            }
            $b_img = 'LOGO_' . time() . '_' . $_FILES['biz_logo']['name'];
            move_uploaded_file($_FILES['biz_logo']['tmp_name'], 'uploads/logos/' . $b_img);
        }

        // Update Customer
        $up1 = $pdo->prepare("UPDATE customers SET full_name=?, email_address=?, phone_number=?, profile_image=? WHERE id=?");
        $up1->execute([$full_name, $email, $phone, $p_img, $cu_id]);

        // Update Company
        if($company_id) {
            $up2 = $pdo->prepare("UPDATE companies SET business_name=?, business_type=?, reg_number=?, business_address=?, business_logo=? WHERE id=?");
            $up2->execute([$biz_name, $biz_type, $biz_reg, $biz_addr, $b_img, $company_id]);
        }

        $pdo->commit();
        echo "<script>alert('Update Successful!'); window.location.href='cu_settings.php';</script>";
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Update Failed: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | Fabricare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
        --bg-dark: #080808;
        --glass: rgba(255, 255, 255, 0.05);
        --glass-border: rgba(255, 255, 255, 0.1);
    }

    body {
        background-color: var(--bg-dark);
        background-image: linear-gradient(rgba(8, 8, 8, 0.85), rgba(8, 8, 8, 0.85)), 
                          url('uploads/resources/bg2.jpg');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        min-height: 100vh;
        font-family: 'Plus Jakarta Sans', sans-serif;
        color: #ffffff;
        margin: 0;
    }
        .card-custom { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 15px; padding: 25px; }
        .nav-link { color: #94a3b8; }
        .nav-link.active { color: var(--neon) !important; font-weight: bold; border-bottom: 2px solid var(--neon) !important; }
        .form-control { background: #090e16; border: 1px solid #334155; color: #fff; }
        .preview-img { width: 80px; height: 80px; object-fit: cover; border-radius: 10px; border: 2px solid var(--neon); }
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
            <a href="customer_dashboard.php" class="back-btn"><i class="bi bi-chevron-left"></i></a>
            <div class="ms-3">
                <h5 class="m-0 fw-bold" style="background: linear-gradient(to right, #fff, var(--primary-neon)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Attendance</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Workshop Terminal</small>
            </div>
        </div>
    </div>
</nav>

<div class="container py-5">
    <form action="cu_settings.php" method="POST" enctype="multipart/form-data">
        <div class="d-flex justify-content-between mb-4">
            <button type="submit" name="save_all" class="btn btn-info btn-sm px-4 fw-bold">SAVE CHANGES</button>
        </div>

        <ul class="nav nav-tabs border-0 mb-4" role="tablist">
            <li class="nav-item"><button class="nav-link active bg-transparent border-0" data-bs-toggle="tab" data-bs-target="#personal" type="button">Personal</button></li>
            <li class="nav-item"><button class="nav-link bg-transparent border-0" data-bs-toggle="tab" data-bs-target="#business" type="button">Business</button></li>
            <li class="nav-item"><button class="nav-link bg-transparent border-0" data-bs-toggle="tab" data-bs-target="#security" type="button">Security</button></li>
        </ul>

        <div class="tab-content card-custom">
            <div class="tab-pane fade show active" id="personal">
                <div class="row g-3">
                    <div class="col-12 d-flex align-items-center gap-3 mb-3">
                        <img src="uploads/profiles/<?= $user['profile_image'] ?>" class="preview-img">
                        <input type="file" name="profile_img" class="form-control form-control-sm w-auto">
                    </div>
                    <div class="col-md-6"><label class="small text-info">Full Name</label><input type="text" name="full_name" class="form-control" value="<?= $user['full_name'] ?>"></div>
                    <div class="col-md-6"><label class="small text-info">Email</label><input type="email" name="email_address" class="form-control" value="<?= $user['email_address'] ?>"></div>
                    <div class="col-md-6"><label class="small text-info">Phone</label><input type="text" name="phone_number" class="form-control" value="<?= $user['phone_number'] ?>"></div>
                </div>
            </div>

            <div class="tab-pane fade" id="business">
                <div class="row g-3">
                    <div class="col-12 d-flex align-items-center gap-3 mb-3">
                        <img src="uploads/logos/<?= $user['business_logo'] ?>" class="preview-img">
                        <input type="file" name="biz_logo" class="form-control form-control-sm w-auto">
                    </div>
                    <div class="col-md-12"><label class="small text-info">Business Name</label><input type="text" name="business_name" class="form-control" value="<?= $user['business_name'] ?>"></div>
                    <div class="col-md-6"><label class="small text-info">Business Type</label><input type="text" name="business_type" class="form-control" value="<?= $user['business_type'] ?>"></div>
                    <div class="col-md-6"><label class="small text-info">Reg Number</label><input type="text" name="reg_number" class="form-control" value="<?= $user['reg_number'] ?>"></div>
                    <div class="col-12"><label class="small text-info">Address</label><textarea name="business_address" class="form-control" rows="2"><?= $user['business_address'] ?></textarea></div>
                </div>
            </div>

            <div class="tab-pane fade" id="security">
                <div id="v-box">
                    <p class="small text-white-50">You must verify your current password to change it.</p>
                    <div class="input-group w-50">
                        <input type="password" id="old_p" class="form-control" placeholder="Current Password">
                        <button type="button" class="btn btn-outline-info" onclick="verify()">VERIFY</button>
                    </div>
                </div>
                <div id="n-box" style="display:none;">
                    <div class="mb-3"><label class="small text-info">New Password</label><input type="password" id="n1" class="form-control"></div>
                    <div class="mb-3"><label class="small text-info">Confirm New Password</label><input type="password" id="n2" class="form-control"></div>
                    <button type="button" class="btn btn-info px-4" onclick="updateP()">UPDATE PASSWORD</button>
                </div>
            </div>
        </div>
    </form>
</div>
<div class="mobile-nav">
    <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
    <a href="customer_dashboard.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
    <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// 1. Current password එක check කරන කොටස
function verify() {
    let p = $('#old_p').val();
    if(p === "") return alert("Enter current password");

    $.post('ajax_cu_verify_pass.php', {password: p}, function(res){
        if(res.trim() === 'success') {
            // මෙතනදී password එක හරි නම් විතරක් අලුත් fields පෙන්වනවා
            $('#v-box').hide();
            $('#n-box').fadeIn(); 
        } else {
            alert('Current password incorrect!');
        }
    });
}

// 2. අලුත් password එක save කරන කොටස
function updateP() {
    let p1 = $('#n1').val();
    let p2 = $('#n2').val();

    if(p1 === "" || p1 !== p2) {
        alert("Passwords do not match!");
        return;
    }

    $.post('ajax_cu_update_pass.php', {new_pass: p1}, function(res){
        if(res.trim() === 'success') {
            alert('Password updated successfully!'); // මෙන්න මෙතනයි මැසේජ් එක එන්න ඕනේ
            location.reload();
        } else {
            alert('Error updating password.');
        }
    });
}
function updateP() {
    let p1 = $('#n1').val();
    if(p1 !== $('#n2').val()) return alert('Mismatch!');
    $.post('ajax_cu_update_pass.php', {new_pass: p1}, function(res){
        if(res.trim()==='success') alert('Password updated! Please refresh.');
    });
}
</script>
</body>
</html>