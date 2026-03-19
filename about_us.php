<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>About Us | Fabricare</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --accent: #ffffff;
            --glass: rgba(255, 255, 255, 0.06);
            --border: rgba(255, 255, 255, 0.1);
        }

        body {
            margin: 0; padding: 0; min-height: 100vh;
            color: #fff; font-family: 'Plus Jakarta Sans', sans-serif;
            background: url('uploads/resources/bg2.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            overflow-x: hidden;
        }

        /* Dark Layer Overlay */
        body::before {
            content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.85); z-index: -1;
        }

        .container { padding-top: 60px; padding-bottom: 80px; }

        .about-header { text-align: center; margin-bottom: 50px; }
        .about-header h1 { 
            font-weight: 800; font-size: 2.8rem; letter-spacing: -2px; 
        }

        /* Glass Card for Content */
        .glass-panel {
            background: var(--glass);
            backdrop-filter: blur(15px);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 40px;
            margin-bottom: 30px;
        }

        .feature-box {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 25px;
            height: 100%;
            transition: 0.3s;
        }
        .feature-box:hover {
            background: rgba(255,255,255,0.08);
            transform: translateY(-5px);
        }
        .feature-icon {
            font-size: 2rem; color: var(--accent); margin-bottom: 15px;
        }

        .stat-number { font-size: 2rem; font-weight: 800; display: block; }
        .stat-label { font-size: 0.75rem; text-transform: uppercase; opacity: 0.5; letter-spacing: 1px; }

        .back-link {
            color: rgba(255,255,255,0.5); text-decoration: none; font-size: 0.9rem;
            display: inline-flex; align-items: center; gap: 8px; margin-bottom: 30px;
        }
        .back-link:hover { color: #fff; }

        /* Responsive Mobile */
        @media (max-width: 768px) {
            .about-header h1 { font-size: 2.2rem; }
            .glass-panel { padding: 25px; }
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
    <div class="about-header">
        <span class="badge rounded-pill border border-secondary mb-3 px-3 py-2 fw-normal">SINCE 2026</span>
        <h1>The Future of <br> Laundry Care.</h1>
        <p class="text-white-50 mt-3 mx-auto" style="max-width: 600px;">
            Fabricare is more than just a laundry service. We combine advanced technology with professional care to keep your wardrobe fresh and long-lasting.
        </p>
    </div>

    <div class="glass-panel text-center">
        <h3 class="fw-bold mb-4">Our Mission</h3>
        <p class="lead opacity-75">
            To provide a premium, effortless laundry experience through digital innovation, ensuring every garment receives the specialized attention it deserves.
        </p>
        
        <div class="row mt-5 g-4">
            <div class="col-6 col-md-3">
                <span class="stat-number">10k+</span>
                <span class="stat-label">Orders Done</span>
            </div>
            <div class="col-6 col-md-3">
                <span class="stat-number">24h</span>
                <span class="stat-label">Fast Delivery</span>
            </div>
            <div class="col-6 col-md-3">
                <span class="stat-number">99%</span>
                <span class="stat-label">Happy Clients</span>
            </div>
            <div class="col-6 col-md-3">
                <span class="stat-number">100%</span>
                <span class="stat-label">Eco Friendly</span>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="feature-box">
                <i class="bi bi-shield-check feature-icon"></i>
                <h5 class="fw-bold">Premium Quality</h5>
                <p class="small text-white-50">We use high-grade detergents and advanced steam technology to protect your fabrics.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-box">
                <i class="bi bi-phone feature-icon"></i>
                <h5 class="fw-bold">Smart Tracking</h5>
                <p class="small text-white-50">Track your order status in real-time and communicate directly with our staff.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-box">
                <i class="bi bi-truck feature-icon"></i>
                <h5 class="fw-bold">Doorstep Pickup</h5>
                <p class="small text-white-50">Schedule a pickup at your convenience and we'll handle the logistics.</p>
            </div>
        </div>
    </div>

    <div class="text-center opacity-25 mt-5">
        <p class="small">&copy; 2026 Fabricare Laundry Management System. All Rights Reserved.</p>
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