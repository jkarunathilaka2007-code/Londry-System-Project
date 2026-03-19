<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Support & FAQ | Fabricare</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --accent: #ffffff;
            --card-bg: rgba(255, 255, 255, 0.06);
            --border: rgba(255, 255, 255, 0.1);
        }

        body {
            margin: 0; padding: 0; min-height: 100vh;
            color: #fff; font-family: 'Plus Jakarta Sans', sans-serif;
            background: url('uploads/resources/bg2.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            overflow-x: hidden; /* Horizontal scroll වැලැක්වීමට */
        }

        /* Dark Overlay Layer */
        body::before {
            content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.9); z-index: -1;
        }

        .container { 
            max-width: 900px; 
            padding: 40px 20px; 
        }

        /* Header Responsive Styling */
        .faq-header { text-align: center; margin-bottom: 40px; }
        .faq-header h2 { 
            font-weight: 800; 
            font-size: calc(1.8rem + 1vw); /* Screen එක අනුව font size එක වෙනස් වේ */
            letter-spacing: -1px; 
        }

        /* Accordion Custom Responsive Styling */
        .accordion-item {
            background: var(--card-bg) !important;
            backdrop-filter: blur(12px);
            border: 1px solid var(--border) !important;
            border-radius: 16px !important;
            margin-bottom: 12px;
            overflow: hidden;
            transition: transform 0.2s ease;
        }

        .accordion-button {
            background: transparent !important;
            color: #fff !important;
            font-weight: 600;
            padding: 18px 20px;
            font-size: 0.95rem;
            box-shadow: none !important;
            text-align: left;
        }

        .accordion-button:not(.collapsed) {
            color: var(--accent) !important;
            border-bottom: 1px solid var(--border);
        }

        .accordion-button::after {
            filter: invert(1);
            transform: scale(0.8);
        }

        .accordion-body {
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
            font-size: 0.92rem;
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
        }

        /* Contact Card Responsive */
        .contact-card {
            background: #ffffff0a;
            color: #ffffff;
            padding: 30px 20px;
            border-radius: 24px;
            text-align: center;
            margin-top: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        

        /* Mobile Specific Adjustments */
        @media (max-width: 576px) {
            .container { padding-top: 30px; }
            .accordion-button { padding: 15px; font-size: 0.9rem; }
            .contact-card { padding: 25px 15px; }
            .btn-contact { width: 100%; } /* Mobile එකේදී බටන් එක full width වෙනවා */
        }
        .top-nav {
    background: rgba(10, 10, 10, 0.9) !important;
    backdrop-filter: blur(30px);
    border-bottom: 1px solid var(--glass-border);
    padding: 0px 0;
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
            <a href="index.php" class="back-btn"><i class="bi bi-chevron-left"></i></a>
            <div class="ms-3">
                <h5 class="m-0 fw-bold" style="background: linear-gradient(to right, #fff, var(--primary-neon)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Attendance</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Workshop Terminal</small>
            </div>
        </div>
    </div>
</nav>

<div class="container">
    
    
    <div class="faq-header">
        <span class="badge rounded-pill bg-white text-dark mb-3 px-3 py-2" style="font-size: 0.65rem; letter-spacing: 1px;">SUPPORT CENTER</span>
        <h2>How can we help?</h2>
        <p class="text-white-50 small">Frequently asked questions about Fabricare laundry services.</p>
    </div>

    <div class="accordion" id="faqAccordion">
        
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#q1">
                    What services does Fabricare offer?
                </button>
            </h2>
            <div id="q1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    We provide professional Wash & Fold, Dry Cleaning, Steam Pressing, and specialized care for delicate fabrics like silk and wool.
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q2">
                    How do I schedule a pickup?
                </button>
            </h2>
            <div id="q2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Go to the 'New Order' section on your dashboard, select your items, and choose a preferred pickup date and time slot. Our team will handle the rest!
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q3">
                    Is there a minimum order value?
                </button>
            </h2>
            <div id="q3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Yes, we have a small minimum order value depending on your location to cover transportation costs. This will be shown during checkout.
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q4">
                    What is the typical turnaround time?
                </button>
            </h2>
            <div id="q4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Standard orders take 24-48 hours. Express 24-hour delivery is also available for an additional small fee.
                </div>
            </div>
        </div>

    </div>

    <div class="contact-card">
        <h5 class="fw-bold mb-2">Can't find what you need?</h5>
        <p class="text-secondary small mb-4">Chat with our support team for specialized help.</p>
        <a href="contact_us.php" class="btn btn-dark rounded-pill px-5 py-2 fw-bold btn-contact">
            CONTACT SUPPORT
        </a>
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