<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Data Collection
    $b_name = htmlspecialchars($_POST['b_name']);
    $b_type = htmlspecialchars($_POST['b_type']);
    $b_reg  = htmlspecialchars($_POST['b_reg']);
    $b_addr = htmlspecialchars($_POST['b_addr']);
    $f_name = htmlspecialchars($_POST['f_name']);
    $phone  = htmlspecialchars($_POST['phone']);
    $email  = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $nic    = htmlspecialchars($_POST['nic']);
    $pw     = password_hash($_POST['pw'], PASSWORD_DEFAULT);

    // 2. Image Handling
    $b_logo = "default_logo.png";
    $p_img  = "default_profile.png";
    if (!is_dir('uploads/logos')) mkdir('uploads/logos', 0777, true);
    if (!is_dir('uploads/profiles')) mkdir('uploads/profiles', 0777, true);

    if (isset($_FILES['b_logo']) && $_FILES['b_logo']['error'] == 0) {
        $b_logo = "LOGO_" . time() . "_" . $_FILES['b_logo']['name'];
        move_uploaded_file($_FILES['b_logo']['tmp_name'], 'uploads/logos/' . $b_logo);
    }
    if (isset($_FILES['p_img']) && $_FILES['p_img']['error'] == 0) {
        $p_img = "USER_" . time() . "_" . $_FILES['p_img']['name'];
        move_uploaded_file($_FILES['p_img']['tmp_name'], 'uploads/profiles/' . $p_img);
    }

    try {
        $pdo->beginTransaction();

        $stmt1 = $pdo->prepare("INSERT INTO companies (business_name, business_type, reg_number, business_address, business_logo) VALUES (?, ?, ?, ?, ?)");
        $stmt1->execute([$b_name, $b_type, $b_reg, $b_addr, $b_logo]);
        $company_id = $pdo->lastInsertId();

        $stmt2 = $pdo->prepare("INSERT INTO customers (company_id, full_name, phone_number, email_address, nic_number, profile_image, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt2->execute([$company_id, $f_name, $phone, $email, $nic, $p_img, $pw]);

        $pdo->commit();

        // --- SUCCESS UI (Black screen එක වෙනුවට පෙන්වන ලස්සන Message එක) ---
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <link href='https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&display=swap' rel='stylesheet'>
            <style>
                body { 
                    background: #08080a; 
                    font-family: 'Space Grotesk', sans-serif; 
                    display: flex; justify-content: center; align-items: center; 
                    height: 100vh; margin: 0; color: #fff;
                }
                .success-card {
                    background: rgba(255,255,255,0.03);
                    border: 1px solid rgba(0, 242, 254, 0.3);
                    padding: 40px; border-radius: 24px; text-align: center;
                    box-shadow: 0 0 50px rgba(0, 242, 254, 0.1);
                    max-width: 320px;
                }
                .icon { font-size: 50px; color: #00f2fe; margin-bottom: 20px; }
                h2 { margin: 0 0 10px 0; color: #00f2fe; }
                p { color: #888; font-size: 14px; line-height: 1.6; }
                .loader {
                    width: 100%; height: 4px; background: rgba(255,255,255,0.1);
                    margin-top: 25px; border-radius: 10px; overflow: hidden;
                }
                .loader-bar {
                    width: 0%; height: 100%; background: #00f2fe;
                    animation: load 3s linear forwards;
                }
                @keyframes load { to { width: 100%; } }
            </style>
        </head>
        <body>
            <div class='success-card'>
                <div class='icon'>✓</div>
                <h2>Success!</h2>
                <p>Your application has been successfully received. You will be able to log in after approval by the Admin.</p>
                <div class='loader'><div class='loader-bar'></div></div>
                <p style='font-size: 11px; margin-top: 15px;'>Redirecting to login...</p>
            </div>
            <script>
                // තත්පර 3කට පසු Login page එකට යවන්න
                setTimeout(function(){ window.location.href = 'login.php'; }, 3000);
            </script>
        </body>
        </html>";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<div style='color:white; background:red; padding:20px;'>Error: " . $e->getMessage() . "</div>";
    }
}
?>