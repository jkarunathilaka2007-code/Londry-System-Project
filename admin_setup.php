<?php
require 'config.php';

$message = "";
$type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $gmail = filter_var($_POST['admin_gmail'], FILTER_SANITIZE_EMAIL);
    $pw = password_hash($_POST['admin_pw'], PASSWORD_DEFAULT);

    try {
        // ඇඩ්මින් කෙනෙක් ඉන්නවද බලමු
        $check = $pdo->query("SELECT id FROM system_admin WHERE id = 1");
        
        if ($check->fetch()) {
            // දැනටමත් ඉන්නවා නම් Update කරන්න
            $sql = "UPDATE system_admin SET gmail = ?, password = ? WHERE id = 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$gmail, $pw]);
            $message = "Admin credentials updated successfully!";
        } else {
            // නැත්නම් අලුතින් ඇතුළත් කරන්න
            $sql = "INSERT INTO system_admin (id, gmail, password) VALUES (1, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$gmail, $pw]);
            $message = "Super Admin created successfully!";
        }
        $type = "success";
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup | Laundry Care</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-neon: #00f2fe; --secondary-neon: #4facfe; --dark-deep: #08080a;
            --glass-card: rgba(18, 18, 22, 0.85); --glass-border: rgba(255, 255, 255, 0.1);
        }
        body {
            background: radial-gradient(circle at 50% 50%, #1e1b4b 0%, var(--dark-deep) 100%);
            min-height: 100vh; font-family: 'Space Grotesk', sans-serif;
            display: flex; align-items: center; justify-content: center; color: #fff;
        }
        .setup-card {
            background: var(--glass-card); backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border); border-radius: 30px;
            padding: 40px; width: 100%; max-width: 450px; box-shadow: 0 25px 60px rgba(0,0,0,0.7);
        }
        .st-input {
            background: rgba(255, 255, 255, 0.03) !important; border: 1px solid var(--glass-border) !important;
            border-radius: 15px !important; color: #fff !important; padding: 15px !important;
        }
        .btn-neon {
            background: linear-gradient(135deg, var(--primary-neon), var(--secondary-neon));
            border: none; border-radius: 15px; padding: 15px; color: #000; font-weight: 700; width: 100%;
        }
        .alert-custom {
            border-radius: 12px; padding: 12px; font-size: 0.9rem; margin-bottom: 20px;
            background: rgba(0, 242, 254, 0.1); border: 1px solid var(--primary-neon); color: var(--primary-neon);
        }
        .alert-error { border-color: #ff4d4d; color: #ff4d4d; background: rgba(255, 77, 77, 0.1); }
    </style>
</head>
<body>

    <div class="setup-card">
        <div class="text-center mb-4">
            <div style="font-size: 50px; color: var(--primary-neon);"><i class="bi bi-shield-lock"></i></div>
            <h2 class="fw-bold mt-2">System Admin</h2>
            <p class="text-white-50">Set or Update Super Admin Access</p>
        </div>

        <?php if($message): ?>
            <div class="alert-custom <?= $type == 'error' ? 'alert-error' : '' ?>">
                <i class="bi <?= $type == 'error' ? 'bi-exclamation-circle' : 'bi-check-circle' ?> me-2"></i>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label text-white-50 small fw-bold">ADMIN GMAIL</label>
                <input type="email" name="admin_gmail" class="form-control st-input" placeholder="admin@laundrycare.com" required>
            </div>
            <div class="mb-4">
                <label class="form-label text-white-50 small fw-bold">ACCESS PASSWORD</label>
                <input type="password" name="admin_pw" class="form-control st-input" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-neon">SAVE ADMIN CONFIG</button>
        </form>
        
        <div class="text-center mt-4">
            <a href="login.php" class="text-white-50 text-decoration-none small">Go to Login Page <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>

</body>
</html>