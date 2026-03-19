<?php
session_start();
require 'config.php'; // Database connection එක තියෙන file එක

$message_sent = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $subject = $_POST['subject'];
    $msg_text = $_POST['message'];

    // මෙතනදී අපි කරන්නේ Contact Form එකෙන් එන පණිවිඩය Admin ට notification එකක් විදිහට යැවීමයි
    // සාමාන්‍යයෙන් Admin ID එක 1 කියලා උපකල්පනය කරමු
    $admin_id = 1; 
    $admin_role = 'admin';
    $sender_role = 'customer'; // හෝ 'guest' ලෙස දැමිය හැක
    
    $full_message = "From: $name ($email)\nSubject: $subject\n\n$msg_text";

    $sql = "INSERT INTO notifications (sender_id, sender_role, receiver_id, receiver_role, message_text, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    
    // දැනට sender_id එක ලෙස 0 හෝ guest ලෙස දිය හැක (Login වී නැතිනම්)
    $sender_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

    if ($stmt->execute([$sender_id, $sender_role, $admin_id, $admin_role, $full_message])) {
        $message_sent = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | Fabricare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --accent: #ffffff;
            --glass: rgba(255, 255, 255, 0.08);
            --border: rgba(255, 255, 255, 0.15);
        }

        body {
            margin: 0; min-height: 100vh;
            color: #fff; font-family: 'Plus Jakarta Sans', sans-serif;
            background: url('uploads/resources/bg2.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
        }

        body::before {
            content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.85); z-index: -1;
        }

        .container { max-width: 900px; padding-top: 60px; padding-bottom: 60px; }

        .glass-card {
            background: var(--glass);
            backdrop-filter: blur(15px);
            border: 1px solid var(--border);
            border-radius: 25px;
            padding: 40px;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid var(--border) !important;
            color: #fff !important;
            border-radius: 12px;
            padding: 12px 15px;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.1) !important;
            box-shadow: none;
            border-color: var(--accent) !important;
        }

        .contact-info-icon {
            width: 45px; height: 45px;
            background: #fff; color: #000;
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%; font-size: 1.2rem;
        }

        .btn-send {
            background: #fff; color: #000;
            border: none; border-radius: 12px;
            padding: 12px; font-weight: 700;
            width: 100%; transition: 0.3s;
        }

        .btn-send:hover { background: rgba(255,255,255,0.9); transform: translateY(-2px); }

        .back-link {
            color: rgba(255,255,255,0.5); text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px; margin-bottom: 25px;
        }
                    /* --- TOP NAV (FIXED) --- */
.top-nav {
    background: rgba(10, 10, 10, 0.9) !important;
    backdrop-filter: blur(30px);
    border-bottom: 1px solid var(--glass-border);
    padding: px 0;
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

<div class="container">

    <div class="row g-5">
        <div class="col-lg-4">
            <h2 class="fw-bold mb-4">Get in <br>Touch.</h2>
            <p class="text-white-50 mb-5">Have a question or feedback? We're here to help you 24/7.</p>
            
            <div class="d-flex align-items-center mb-4">
                <div class="contact-info-icon me-3"><i class="bi bi-geo-alt"></i></div>
                <div><small class="d-block opacity-50 text-uppercase">Address</small><strong>Colombo, Sri Lanka</strong></div>
            </div>

            <div class="d-flex align-items-center mb-4">
                <div class="contact-info-icon me-3"><i class="bi bi-telephone"></i></div>
                <div><small class="d-block opacity-50 text-uppercase">Phone</small><strong>+94 77 123 4567</strong></div>
            </div>

            <div class="d-flex align-items-center">
                <div class="contact-info-icon me-3"><i class="bi bi-envelope"></i></div>
                <div><small class="d-block opacity-50 text-uppercase">Email</small><strong>hello@fabricare.com</strong></div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="glass-card">
                <?php if($message_sent): ?>
                    <div class="alert alert-success bg-success text-white border-0 rounded-4 p-4 text-center">
                        <i class="bi bi-check-circle-fill d-block mb-2" style="font-size: 2rem;"></i>
                        <h5 class="fw-bold">Message Sent!</h5>
                        <p class="small mb-0">Our team will get back to you shortly.</p>
                    </div>
                <?php else: ?>
                    <form action="" method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small opacity-50 mb-2">Full Name</label>
                                <input type="text" name="name" class="form-control" placeholder="John Doe" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small opacity-50 mb-2">Email Address</label>
                                <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                            </div>
                            <div class="col-12">
                                <label class="small opacity-50 mb-2">Subject</label>
                                <input type="text" name="subject" class="form-control" placeholder="Order Update / Inquiry" required>
                            </div>
                            <div class="col-12">
                                <label class="small opacity-50 mb-2">How can we help?</label>
                                <textarea name="message" class="form-control" rows="5" placeholder="Type your message here..." required></textarea>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn-send">SEND MESSAGE</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<div class="mobile-nav">
    <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
    <a href="index.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
    <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>