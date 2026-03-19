<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$is_logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['role'] ?? ''; 
$is_customer = ($is_logged_in && $user_role == 'customer');

$dashboard_url = 'login.php';
if ($is_logged_in) {
    if ($user_role == 'admin') { $dashboard_url = 'admin_dashboard.php'; }
    elseif ($user_role == 'customer') { $dashboard_url = 'customer_dashboard.php'; }
    elseif ($user_role == 'employee') { $dashboard_url = 'employee_dashboard.php'; }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fabri Care | Premium Laundry</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root { --bg-dark: #080808; --glass: rgba(255, 255, 255, 0.03); --border: rgba(255, 255, 255, 0.1); --text-gray: #a0a0a0; }
        body { background-color: var(--bg-dark); color: #ffffff; font-family: 'Inter', sans-serif; margin: 0; padding: 0; overflow-x: hidden; }
        h1, h2, .nav-link-item, .btn-premium, .shortcut-card span { font-family: 'Playfair Display', serif; text-transform: uppercase; }

        /* --- Hero Section --- */
        .hero-section {
            position: relative;
            width: 100%;
            height: 100vh; /* Desktop Height */
            background: linear-gradient(rgba(0,0,0,0.65), rgba(0,0,0,0.85)), url('uploads/resources/banner.png');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        /* --- Navbar Fixes --- */
        .fabricare-navbar {
            position: absolute; top: 0; width: 100%; padding: 30px 50px;
            display: flex; justify-content: space-between; align-items: center; z-index: 1000;
        }

        .btn-premium {
            padding: 8px 25px; border-radius: 50px; font-size: 11px; font-weight: 700;
            letter-spacing: 1px; text-decoration: none; transition: 0.3s;
        }
        .btn-outline { border: 1px solid rgba(255,255,255,0.4); color: #fff; }
        .btn-filled { background: #fff; color: #000; }

        .hero-title { font-size: clamp(2.2rem, 8vw, 4.5rem); font-weight: 800; line-height: 1.1; margin-bottom: 15px; }

        /* --- Shortcut Cards - New Placement --- */
        .shortcut-wrapper {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translate(-50%, 50%); /* භාගයක් පල්ලෙහාට */
            width: 100%;
            z-index: 100;
        }

        .shortcut-container {
            display: flex;
            gap: 15px;
            justify-content: center;
            padding: 0 20px;
        }

        .shortcut-card {
    width: 160px; 
    height: 200px;
    /* Background එක ඉතාමත් තුනී සුදු පාටක් කළා (0.05) */
    background: rgba(255, 255, 255, 0.05); 
    /* Border එකත් ගොඩක් විනිවිද පේන විදිහට හැදුවා */
    border: 1px solid rgba(255, 255, 255, 0.1); 
    border-radius: 15px;
    /* Blur එක 15px තියන්න, එතකොට තමයි ලස්සන */
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    display: flex; 
    flex-direction: column; 
    align-items: center; 
    justify-content: center;
    text-decoration: none; 
    /* Icon සහ Text වල තද ගතිය පොඩ්ඩක් අඩු කළා */
    color: rgba(255, 255, 255, 0.8); 
    transition: 0.3s;
}

.shortcut-card.clickable:hover { 
    transform: translateY(-5px); 
    border-color: rgba(255, 255, 255, 0.5);
    background: rgba(255, 255, 255, 0.1); /* Hover එකේදී පොඩ්ඩක් තද වෙනවා */
    color: #fff; 
}

        .shortcut-card.clickable:hover { transform: translateY(-5px); border-color: #fff; }
        .shortcut-card i { font-size: 32px; margin-bottom: 15px; color: #fff; }
        .shortcut-card span { font-size: 10px; letter-spacing: 1px; font-weight: 700; }

        /* --- Mobile Specific Fixes --- */
        @media (max-width: 576px) {
            .hero-section { height: 50vh !important; } /* Banner Height එක ගොඩක් අඩු කළා */
            
            .fabricare-navbar { padding: 15px; }
            .btn-premium { min-width: 80px; padding: 6px 12px; font-size: 9px; }

            .shortcut-wrapper { transform: translate(-50%, 75%); } /* Mobile එකේදී පොඩ්ඩක් ඉහළට ගත්තා */
            
            .shortcut-container {
                justify-content: flex-start;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 20px;
            }
            .shortcut-container::-webkit-scrollbar { display: none; }
            
            .shortcut-card { min-width: 140px; height: 180px; }
        }

        /* --- Side Nav (Same as before) --- */
        .side-nav {
            height: 100%; width: 0; position: fixed; z-index: 2001; top: 0; left: 0;
            background: rgba(8, 8, 8, 0.98); backdrop-filter: blur(20px);
            overflow-x: hidden; transition: 0.4s; border-right: 1px solid var(--border);
        }
        .side-nav-header { padding: 30px; display: flex; justify-content: space-between; align-items: center; }
        .side-nav-links { padding: 10px 30px; display: flex; flex-direction: column; }
        .side-nav-links a { text-decoration: none; color: #a0a0a0; font-size: 15px; padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .nav-overlay { position: fixed; display: none; width: 100%; height: 100%; top: 0; left: 0; background: rgba(0,0,0,0.7); z-index: 2000; }

        /* Process Section spacing */
        .process-section { padding-top: 150px; padding-bottom: 50px; }
        .process-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 20px; }
        .process-img { width: 100%; border-radius: 10px; border: 1px solid var(--border); }
        
        @media (max-width: 768px) { .process-grid { grid-template-columns: repeat(2, 1fr); } }
        /* About Section Styles */
.about-section {
    padding: 80px 0;
    background: var(--bg-dark);
}

.about-card {
    background: rgba(255, 255, 255, 0.02); /* ඉතාමත් විනිවිද පේන Glass effect එක */
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 30px;
    padding: 60px 40px;
    backdrop-filter: blur(20px);
    text-align: center;
    position: relative;
}

.about-logo {
    width: 120px;
    height: 120px;
    background: #fff; /* Logo එක පේන්න සුදු පසුබිමක් */
    border-radius: 20px;
    padding: 10px;
    margin: 0 auto 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}

.about-logo img {
    max-width: 100%;
    height: auto;
}

.about-title {
    font-size: 3rem;
    font-weight: 800;
    letter-spacing: 2px;
    margin-bottom: 30px;
    color: #fff;
}

.about-text {
    font-size: 15px;
    line-height: 1.8;
    color: var(--text-gray);
    max-width: 900px;
    margin: 0 auto;
    text-transform: uppercase; /* Screenshot එකේ විදිහටම All Caps */
    letter-spacing: 1px;
}

@media (max-width: 768px) {
    .about-title { font-size: 2rem; }
    .about-card { padding: 40px 20px; }
    .about-text { font-size: 13px; }
}
/* Testimonials Section Styles */
.testimonials-section {
    padding: 60px 0;
    background: var(--bg-dark);
}

/* Section එක වටේට යන සුදු Border එක (The Layer) */
.section-border-layer {
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 40px;
    padding: 40px 20px;
    position: relative;
}

.testimonial-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 20px;
    margin-top: 30px;
}

.testimonial-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 30px 15px;
    width: 300px;
    text-align: center;
    backdrop-filter: blur(15px);
}

/* User Avatar with Playfair Font */
.user-avatar {
    width: 65px;
    height: 65px;
    border: 1.5px solid #fff;
    border-radius: 50%;
    margin: 0 auto 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Playfair Display', serif !important;
    font-size: 28px;
    font-weight: 700;
    color: #fff;
}

.user-name {
    font-family: 'Playfair Display', serif !important;
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
    color: #fff;
    margin-bottom: 5px;
}

.star-rating {
    color: #ffcc00;
    font-size: 11px;
    margin-bottom: 15px;
}

.testimonial-text {
    font-size: 10px;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.6);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* --- Mobile Fix (100% Same as Image) --- */
@media (max-width: 576px) {
    .testimonial-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr); /* එක පේළියට 2ක් */
        gap: 10px;
    }

    .testimonial-card {
        width: 100%;
        padding: 20px 10px;
    }

    /* 3 වෙනි Card එක මැදට ගැනීම */
    .testimonial-card:nth-child(3) {
        grid-column: span 2;
        width: 70%;
        margin: 0 auto;
    }

    .section-border-layer {
        border-radius: 30px;
        padding: 30px 10px;
    }
}
/* Contact Section එකට අදාළ විශේෂ CSS */
.contact-section {
    padding: 60px 0;
    background: var(--bg-dark);
}

/* මේක තමයි ඔයා ඉල්ලපු වටේට යන Border එක */
.contact-border-layer {
    border: 1px solid rgba(255, 255, 255, 0.08); /* About එකේ පාටමයි */
    border-radius: 40px;
    padding: 40px 25px;
    position: relative;
}

.contact-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 15px;
}

/* Social & Info Cards */
.social-btn, .info-card {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 15px;
    display: flex;
    align-items: center;
    gap: 15px;
    text-decoration: none;
    transition: 0.3s;
}

.info-card {
    display: block; /* Info cards වල අකුරු මැදට එන්න */
    text-align: center;
    padding: 25px 10px;
}

.social-btn i {
    font-size: 24px;
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
}

.whatsapp-icon { background: #25D366; color: #fff; }
.facebook-icon { background: #1877F2; color: #fff; }
.youtube-icon { background: #FF0000; color: #fff; }

.social-info h5, .info-card h4 {
    font-family: 'Playfair Display', serif !important;
    color: #fff;
    margin: 0;
    text-transform: uppercase;
}

.social-info h5 { font-size: 13px; letter-spacing: 1px; }
.info-card h4 { font-size: 16px; margin-bottom: 8px; letter-spacing: 1.5px; }

.social-info span, .info-card p {
    color: var(--text-gray);
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

@media (max-width: 768px) {
    .contact-grid { grid-template-columns: 1fr; }
    .contact-border-layer { border-radius: 30px; padding: 30px 15px; }
}
/* App Download Section Styles */
.app-section {
    padding: 60px 0;
    background: var(--bg-dark);
}

.app-border-layer {
    border: 1px solid rgba(255, 255, 255, 0.08); /* අනිත් Section වල පාටමයි */
    border-radius: 40px;
    padding: 50px 20px;
    text-align: center;
    position: relative;
    backdrop-filter: blur(10px);
}

.app-title {
    font-family: 'Playfair Display', serif !important;
    font-size: 2.2rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 15px;
    text-transform: uppercase;
    letter-spacing: 2px;
}

.app-subtitle {
    font-size: 12px;
    color: var(--text-gray);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 35px;
}

.store-buttons {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.store-btn {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    padding: 12px 25px;
    display: flex;
    align-items: center;
    gap: 15px;
    text-decoration: none;
    color: #fff;
    transition: 0.3s;
    min-width: 200px;
}

.store-btn:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.4);
    transform: translateY(-5px);
    color: #fff;
}

.store-btn i {
    font-size: 28px;
}

.btn-text {
    text-align: left;
}

.btn-text small {
    display: block;
    font-size: 9px;
    color: var(--text-gray);
    text-transform: uppercase;
}

.btn-text strong {
    display: block;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 0.5px;
}

@media (max-width: 576px) {
    .app-title { font-size: 1.6rem; }
    .store-btn { width: 100%; justify-content: center; }
    .app-border-layer { border-radius: 30px; padding: 40px 15px; }
}
/* Footer Styles */
.footer-section {
    padding: 50px 0 30px;
    background: var(--bg-dark);
}

.footer-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.footer-logo {
    font-family: 'Playfair Display', serif !important;
    font-size: 24px;
    font-weight: 800;
    color: #fff;
    letter-spacing: 3px;
    margin-bottom: 20px;
}

.footer-social-links {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
}

.footer-social-links a {
    width: 45px;
    height: 45px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 18px;
    transition: 0.3s;
    text-decoration: none;
}

.footer-social-links a:hover {
    background: #fff;
    color: #000;
    transform: translateY(-5px);
}

.footer-nav-links {
    display: flex;
    gap: 30px;
    margin-bottom: 30px;
    flex-wrap: wrap;
    justify-content: center;
}

.footer-nav-links a {
    color: var(--text-gray);
    text-decoration: none;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    transition: 0.3s;
}

.footer-nav-links a:hover {
    color: #fff;
}

.copyright-text {
    font-size: 10px;
    color: rgba(255, 255, 255, 0.3);
    text-transform: uppercase;
    letter-spacing: 1px;
}

@media (max-width: 576px) {
    .footer-nav-links { gap: 15px; }
    .footer-nav-links a { font-size: 10px; }
}
    </style>
</head>
<body>

    <div id="sideNav" class="side-nav">
        <div class="side-nav-header">
            <h4 class="m-0">FABRICARE</h4>
            <i class="fa-solid fa-xmark fs-2" onclick="toggleNav()"></i>
        </div>
        <div class="side-nav-links">
            <?php if (!$is_logged_in): ?><a href="login.php">Login</a><?php endif; ?>
            <a href="register.php">Register</a>    
            <a href="notifications.php">Notifications</a>
            <a href="faq.php">FAQ</a>
            <a href="about_us.php">About</a>
            <a href="contact_us.php">Contact</a>
            <?php if ($is_logged_in): ?><a href="logout.php" style="color:#ff5d5d">Logout</a><?php endif; ?>
        </div>
    </div>
    <div id="navOverlay" class="nav-overlay" onclick="toggleNav()"></div>

    <header class="hero-section">
        <nav class="fabricare-navbar">
            <div onclick="toggleNav()" style="cursor: pointer;"><i class="fa-solid fa-bars-staggered fs-3 text-white"></i></div>
            <div class="nav-auth d-flex gap-2">
                <?php if ($is_logged_in): ?>
                    <a href="<?= $dashboard_url ?>" class="btn-premium btn-outline">DASHBOARD</a>
                    <a href="logout.php" class="btn-premium btn-filled">LOGOUT</a>
                <?php else: ?>
                    <a href="login.php" class="btn-premium btn-outline">LOGIN</a>
                    <a href="register.php" class="btn-premium btn-filled">SIGN IN</a>
                <?php endif; ?>
            </div>
        </nav>

        <div class="container">
            <h1 class="hero-title">EXPERIENCE <br> PREMIUM CARE.</h1>
            <p class="hero-subtitle">Your Cloths Deserve The Best. Trust Our Smart Automated Laundry Systems For A Royal Finish</p>
        </div>

        <div class="shortcut-wrapper">
            <div class="shortcut-container">
                <?php
                $tools = [
                    ['icon' => 'fa-circle-plus', 'text' => 'New Order', 'url' => 'add_order.php'],
                    ['icon' => 'fa-location-crosshairs', 'text' => 'Track Order', 'url' => 'orders.php'],
                    ['icon' => 'fa-history', 'text' => 'History', 'url' => 'orders.php'],
                    ['icon' => 'fa-user-gear', 'text' => 'Profile', 'url' => 'customer_dashboard.php']
                ];
                foreach ($tools as $t):
                    $link = $is_customer ? $t['url'] : 'javascript:void(0)';
                ?>
                <a href="<?= $link ?>" class="shortcut-card <?= $is_customer ? 'clickable' : 'disabled' ?>">
                    <i class="fa-solid <?= $t['icon'] ?>"></i>
                    <span><?= $t['text'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </header>

    <section id="process" class="process-section">
        <div class="container">
            <h6 class="text-gray letter-spacing-2">PROCESS</h6>
            <h2 class="mb-4">HOW WE CARE FOR YOU</h2>
            <div class="process-grid">
                <img src="uploads/resources/img1.png" class="process-img">
                <img src="uploads/resources/img2.png" class="process-img">
                <img src="uploads/resources/img3.png" class="process-img">
                <img src="uploads/resources/img4.png" class="process-img">
            </div>
        </div>
    </section>
    <section id="about" class="about-section">
    <div class="container">
        <span class="section-label text-center d-block">ABOUT</span>
        
        <div class="about-card mt-4">
            <div class="about-logo">
                <img src="uploads/resources/logo.png" alt="Fabri Care Logo">
            </div>
            
            <h2 class="about-title">FABRICARE</h2>
            
            <p class="about-text">
                LaundryCare is a robust, enterprise-grade industrial laundry management system designed for 
                high-volume operations in hotels, restaurants, and large corporations. Unlike basic software, 
                it automates the entire lifecycle of heavy-duty washing, drying, and finishing processes. 
                Featuring advanced RFID and QR tracking, it monitors thousands of linens and uniforms in real-time, 
                eliminating inventory loss. The system optimizes resource consumption—water, energy, and chemicals—to 
                drive sustainability and reduce overhead. With specialized modules for hospitality hygiene and 
                predictive maintenance for industrial machinery, LaundryCare serves as a complete ERP solution, 
                ensuring maximum efficiency, rapid turnaround times, and seamless large-scale fabric management.
            </p>
        </div>
    </div>
</section>
<hr class="section-divider">

<section id="testimonials" class="testimonials-section">
    <div class="container">
        <div class="section-border-layer">
            <span class="section-label text-center d-block">TESTIMONIALS</span>
            
            <div class="testimonial-grid">
                <div class="testimonial-card">
                    <div class="user-avatar">S</div>
                    <h4 class="user-name">Samantha Perera</h4>
                    <div class="star-rating">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    </div>
                    <p class="testimonial-text">"THE AUTOMATED TRACKING SYSTEM IS AMAZING. I KNEW EXACTLY WHEN MY SUIT WAS READY."</p>
                </div>

                <div class="testimonial-card">
                    <div class="user-avatar">N</div>
                    <h4 class="user-name">Namal Kavindya</h4>
                    <div class="star-rating">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    </div>
                    <p class="testimonial-text">"BEST LAUNDRY SERVICE IN COLOMBO. THE CLOTHES SMELLED FRESH FOR DAYS."</p>
                </div>

                <div class="testimonial-card">
                    <div class="user-avatar">R</div>
                    <h4 class="user-name">Ruwan Perera</h4>
                    <div class="star-rating">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    </div>
                    <p class="testimonial-text">"VERY PROFESSIONAL STAFF AND THE PREMIUM PACKAGE IS WORTH EVERY RUPEE."</p>
                </div>
            </div>
        </div> </div>
</section>
<hr class="section-divider">

<section id="contact" class="contact-section">
    <div class="container">
        <div class="contact-border-layer">
            <span class="section-label text-center d-block">CONTACT US</span>
            
            <div class="contact-grid mt-4">
                <a href="#" class="social-btn">
                    <i class="fa-brands fa-whatsapp whatsapp-icon"></i>
                    <div class="social-info">
                        <h5>FABRICARE GROUP</h5>
                        <span>CLICK HERE TO JOIN</span>
                    </div>
                </a>
                
                <a href="#" class="social-btn">
                    <i class="fa-brands fa-facebook-f facebook-icon"></i>
                    <div class="social-info">
                        <h5>FABRICARE PAGE</h5>
                        <span>CLICK HERE TO JOIN</span>
                    </div>
                </a>
                
                <a href="#" class="social-btn">
                    <i class="fa-brands fa-youtube youtube-icon"></i>
                    <div class="social-info">
                        <h5>FABRICARE CHNNEL</h5>
                        <span>CLICK HERE TO WATCH</span>
                    </div>
                </a>
            </div>

            <div class="contact-grid">
                <div class="info-card">
                    <h4>FIND US</h4>
                    <p>NO 10, BADULLA ROAD, BANDARAWELA</p>
                </div>
                
                <div class="info-card">
                    <h4>CALL US</h4>
                    <p>0701581655</p>
                </div>
                
                <div class="info-card">
                    <h4>MAIL US</h4>
                    <p>FABRICARE@GMAIL.COM</p>
                </div>
            </div>
        </div> </div>
</section>
<hr class="section-divider">

<section id="download-app" class="app-section">
    <div class="container">
        <div class="app-border-layer">
            <span class="section-label d-block mb-3">MOBILE APP</span>
            <h2 class="app-title">DOWNLOAD <br> FABRICARE APP</h2>
            <p class="app-subtitle">Get the premium laundry experience on your fingertips. Track, order, and manage anywhere.</p>
            
            <div class="store-buttons">
                <a href="download_folder.php?type=apple" class="store-btn">
                    <i class="fa-brands fa-apple"></i>
                    <div class="btn-text">
                        <small>Download on the</small>
                        <strong>App Store</strong>
                    </div>
                </a>

                <a href="download_folder.php?type=android" class="store-btn">
                    <i class="fa-brands fa-google-play"></i>
                    <div class="btn-text">
                        <small>Get it on</small>
                        <strong>Google Play</strong>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>
<hr class="section-divider">

<footer class="footer-section">
    <div class="container">
        <div class="footer-content">
            <div class="footer-logo">FABRICARE</div>
            
            <div class="footer-nav-links">
                <a href="#">Home</a>
                <a href="#about">About</a>
                <a href="#services">Services</a>
                <a href="#testimonials">Testimonials</a>
                <a href="#contact">Contact</a>
            </div>

            <div class="footer-social-links">
                <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#"><i class="fa-brands fa-instagram"></i></a>
                <a href="#"><i class="fa-brands fa-x-twitter"></i></a>
                <a href="#"><i class="fa-brands fa-linkedin-in"></i></a>
            </div>

            <p class="copyright-text">
                &copy; 2026 FABRICARE LAUNDRY SYSTEMS. ALL RIGHTS RESERVED. <br>
                DESIGNED BY JANITH KARUNATHILAKA
            </p>
        </div>
    </div>
</footer>

    <script>
        function toggleNav() {
            const sideNav = document.getElementById("sideNav");
            const overlay = document.getElementById("navOverlay");
            if (sideNav.style.width === "280px") {
                sideNav.style.width = "0";
                overlay.style.display = "none";
            } else {
                sideNav.style.width = "280px";
                overlay.style.display = "block";
            }
        }
    </script>
</body>
</html>