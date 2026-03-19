<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$status = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['service_name'];
    $price = $_POST['price'];
    $lead_time = $_POST['lead_time'];
    
    $image_name = "";
    if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] == 0) {
        $target_dir = "uploads/services/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $file_ext = pathinfo($_FILES["service_image"]["name"], PATHINFO_EXTENSION);
        $image_name = time() . "_" . uniqid() . "." . $file_ext;
        move_uploaded_file($_FILES["service_image"]["tmp_name"], $target_dir . $image_name);
    }

    $stmt = $pdo->prepare("INSERT INTO services (service_name, service_image, price, lead_time) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$name, $image_name, $price, $lead_time])) {
        $status = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Config Services | Fabricare</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    
    <style>
    :root {
        --bg-dark: #080808;
        --glass: rgba(255, 255, 255, 0.05);
        --glass-border: rgba(255, 255, 255, 0.12);
        --accent: #ffffff;
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
        color: #ffffff !important;
        margin: 0;
        overflow-x: hidden;
    }

    /* --- TOP NAV (FIXED) --- */
    .top-nav {
        background: rgba(10, 10, 10, 0.8);
        backdrop-filter: blur(30px);
        border-bottom: 1px solid var(--glass-border);
        padding: 12px 0;
        position: sticky; top: 0; z-index: 1000;
    }

    .top-nav h5 {
        color: #ffffff !important;
        font-weight: 700 !important;
        margin: 0;
        background: none !important;
        -webkit-text-fill-color: initial !important;
        display: block !important;
    }

    .back-btn {
        width: 40px; height: 40px; border-radius: 12px;
        background: var(--glass); border: 1px solid var(--glass-border);
        display: flex; align-items: center; justify-content: center;
        color: #fff !important; text-decoration: none;
    }

    .main-container { padding: 30px 15px 120px 15px; }

    /* --- GLASS CARD --- */
    .glass-card {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(25px);
        border: 1px solid var(--glass-border);
        border-radius: 30px;
        padding: 35px;
        max-width: 500px;
        margin: 0 auto;
        box-shadow: 0 20px 50px rgba(0,0,0,0.3);
    }

    /* --- IMAGE PREVIEW AREA --- */
    .preview-box {
        width: 100%;
        height: 220px;
        border: 2px dashed var(--glass-border);
        border-radius: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        margin-bottom: 25px;
        position: relative;
        background: rgba(255,255,255,0.02);
        transition: 0.3s;
    }

    .preview-box:hover { border-color: rgba(255,255,255,0.3); background: rgba(255,255,255,0.04); }

    #imgPreview {
        width: 100%; height: 100%;
        object-fit: contain; /* Image එකේ හැඩය විනාශ වෙන්නේ නැති වෙන්න */
        display: none;
        padding: 10px;
    }

    .upload-label { cursor: pointer; text-align: center; color: rgba(255,255,255,0.4); }
    .upload-label i { font-size: 3rem; margin-bottom: 10px; display: block; }

    /* --- FORM STYLING --- */
    .form-label-custom {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 1px;
        color: rgba(255,255,255,0.5);
        margin-bottom: 8px;
        text-transform: uppercase;
    }

    .form-control {
        background: rgba(255, 255, 255, 0.05) !important;
        border: 1px solid var(--glass-border) !important;
        color: #fff !important;
        border-radius: 15px;
        padding: 14px;
        transition: 0.3s;
    }

    .form-control:focus {
        background: rgba(255, 255, 255, 0.08) !important;
        border-color: rgba(255,255,255,0.3) !important;
        box-shadow: 0 0 0 4px rgba(255,255,255,0.05);
    }

    .btn-submit {
        background: #ffffff;
        color: #000;
        border: none;
        font-weight: 800;
        border-radius: 18px;
        padding: 16px;
        width: 100%;
        text-transform: uppercase;
        margin-top: 10px;
        transition: 0.3s;
        letter-spacing: 1px;
    }

    .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.2); background: #f0f0f0; }

    /* --- MOBILE NAV (TOP ROUNDED ONLY) --- */
    .mobile-nav { display: none !important; }

    @media (max-width: 991px) {
        .mobile-nav {
            display: flex !important; 
            position: fixed; bottom: 0px; left: 0px; right: 0px;
            height: 70px; background: rgba(15, 15, 15, 0.96);
            backdrop-filter: blur(25px); 
            border-radius: 25px 25px 0 0; 
            border-top: 1px solid var(--glass-border); 
            justify-content: space-around; 
            align-items: center; 
            z-index: 1050;
        }
    }

    .nav-item-m { text-align: center; color: rgba(255,255,255,0.3); text-decoration: none; font-size: 10px; font-weight: 700; flex: 1; }
    .nav-item-m.active { color: #fff; }
    .nav-item-m i { font-size: 22px; display: block; margin-bottom: 2px; }
</style>
</head>
<body>

    <nav class="top-nav">
        <div class="container d-flex align-items-center">
            <a href="services.php" class="back-btn"><i class="bi bi-chevron-left"></i></a>
            <div class="ms-3">
                <h5 class="m-0 fw-bold" style="background: linear-gradient(to right, #fff, var(--primary-neon)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Staff Onboarding</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Admin Control Panel</small>
            </div>
        </div>
    </nav>

    <div class="container main-container">
        
        <?php if($status == "success"): ?>
            <div class="alert alert-success bg-success bg-opacity-10 text-success border-0 rounded-4 text-center mb-4 mx-auto" style="max-width: 500px;">
                Item added successfully!
            </div>
        <?php endif; ?>

        <div class="glass-card">
            <form method="POST" enctype="multipart/form-data">
                
                <div class="preview-box" onclick="document.getElementById('fileInput').click()">
                    <div class="upload-label" id="placeholderText">
                        <i class="bi bi-cloud-arrow-up fs-1"></i>
                        <p class="small m-0">Click to upload item image</p>
                    </div>
                    <img src="" id="imgPreview">
                </div>

                <input type="file" name="service_image" id="fileInput" accept="image/*" style="display: none;" onchange="previewFile()" required>

                <div class="mb-3">
                    <label class="small text-white-50 mb-2">PRODUCT NAME</label>
                    <input type="text" name="service_name" class="form-control" placeholder="e.g. T-Shirt" required>
                </div>

                <div class="mb-3">
                    <label class="small text-white-50 mb-2">LEAD TIME</label>
                    <input type="text" name="lead_time" class="form-control" placeholder="e.g. 24 Hours" required>
                </div>

                <div class="mb-4">
                    <label class="small text-white-50 mb-2">PRICE (LKR)</label>
                    <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" required>
                </div>

                <button type="submit" class="btn btn-submit">Save Item</button>
            </form>
        </div>
    </div>

<div class="mobile-nav">
        <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
        <a href="admin_dashboard.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
        <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
</div>

    <script>
        function previewFile() {
            const preview = document.getElementById('imgPreview');
            const placeholder = document.getElementById('placeholderText');
            const file = document.getElementById('fileInput').files[0];
            const reader = new FileReader();

            reader.onloadend = function () {
                preview.src = reader.result;
                preview.style.display = "block";
                placeholder.style.display = "none";
            }

            if (file) { reader.readAsDataURL(file); }
        }
    </script>
</body>
</html>