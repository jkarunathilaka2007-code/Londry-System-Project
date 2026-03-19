<?php
session_start();
require 'config.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_input = $_POST['email'];
    $password = $_POST['password'];

    // 1. System Admin Check
    $stmt = $pdo->prepare("SELECT * FROM system_admin WHERE gmail = ?");
    $stmt->execute([$user_input]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user_name'] = "System Admin"; 
        $_SESSION['role'] = 'admin';
        header("Location: index.php"); 
        exit();
    }

    // 2. Customer Check
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE email_address = ?");
    $stmt->execute([$user_input]);
    $customer = $stmt->fetch();

    if ($customer && password_verify($password, $customer['password'])) {
        if ($customer['status'] == 'Active') {
            $_SESSION['user_id'] = $customer['id'];
            $_SESSION['user_name'] = $customer['full_name']; 
            $_SESSION['role'] = 'customer';
            header("Location: index.php");
        } else {
            $error = "Your account is still pending approval.";
        }
        exit();
    }

    // 3. Employee Check
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE email = ?");
    $stmt->execute([$user_input]);
    $employee = $stmt->fetch();

    if ($employee && password_verify($password, $employee['password'])) {
        $_SESSION['user_id'] = $employee['id'];
        $_SESSION['user_name'] = $employee['name']; 
        $_SESSION['role'] = 'employee';
        header("Location: index.php");
        exit();
    }

    $error = "Invalid email or password!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | FabriCare Premium</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Plus+Jakarta+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-dark: #0a0a0a;
            --text-gray: rgba(255, 255, 255, 0.6);
            --glass: rgba(255, 255, 255, 0.02);
            --glass-border: rgba(255, 255, 255, 0.08);
        }

        body {
    background-color: var(--bg-dark);
    background-image: linear-gradient(rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0.75)), 
                      url('uploads/resources/bg2.jpg');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    min-height: 100vh;
    font-family: 'Plus Jakarta Sans', sans-serif;
    color: #fff;
    margin: 0;

    /* මෙන්න මේ ටික අනිවාර්යයෙන්ම තියෙන්න ඕනේ form එක මැදට එන්න */
    display: flex;
    align-items: center;    /* Vertical center */
    justify-content: center; /* Horizontal center */
    padding: 20px;
}

        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        .login-card {
            background: var(--glass);
            backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 40px; /* Landing page එකේ radius එකමයි */
            padding: 50px 40px;
            box-shadow: 0 40px 100px rgba(0,0,0,0.8);
            position: relative;
            overflow: hidden;
        }

        .brand-name {
            font-family: 'Playfair Display', serif !important;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: 4px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .login-subtitle {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--text-gray);
            margin-bottom: 40px;
        }

        .form-label {
            font-size: 10px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--text-gray);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 15px 20px;
            color: #fff !important;
            font-size: 14px;
            transition: 0.3s;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: none;
            outline: none;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.2);
            font-size: 12px;
        }

        .btn-login {
            background: #fff;
            color: #000;
            border: none;
            border-radius: 15px;
            padding: 16px;
            font-family: 'Playfair Display', serif;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            width: 100%;
            margin-top: 20px;
            transition: 0.3s;
        }

        .btn-login:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255,255,255,0.1);
        }

        .error-box {
            background: rgba(255, 77, 77, 0.05);
            border: 1px solid rgba(255, 77, 77, 0.2);
            color: #ff6b6b;
            border-radius: 12px;
            padding: 12px;
            font-size: 11px;
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: center;
        }

        .footer-links {
            margin-top: 30px;
            text-align: center;
        }

        .footer-links a {
            color: var(--text-gray);
            text-decoration: none;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: 0.3s;
        }

        .footer-links a:hover {
            color: #fff;
        }

        /* Mobile Adjustments */
        @media (max-width: 480px) {
            .login-card {
                padding: 40px 25px;
                border-radius: 30px;
            }
            .brand-name { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-card">
            <div class="text-center">
                <div class="brand-name">FABRICARE</div>
                <div class="login-subtitle">Premium Laundry Management</div>
            </div>

            <?php if($error): ?>
                <div class="error-box">
                    <i class="fa-solid fa-circle-exclamation me-2"></i><?= $error ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-4">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="ENTER YOUR EMAIL" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="ENTER YOUR PASSWORD" required>
                </div>
                
                <button type="submit" class="btn-login">Sign In</button>
            </form>

            <div class="footer-links">
                <p class="mb-2" style="font-size: 11px; color: rgba(255,255,255,0.3);">
                    NEW TO FABRICARE? <a href="register.php" style="color: #fff; font-weight: 700;">CREATE ACCOUNT</a>
                </p>
                <a href="index.php"><i class="fa-solid fa-arrow-left me-2"></i>Back to Website</a>
            </div>
        </div>
    </div>

</body>
</html>