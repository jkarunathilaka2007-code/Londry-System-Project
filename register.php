<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Laundry Care | Premium Registration</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- UI ROOT & DEFAULTS --- */
:root {
    --bg-dark: #080808;
    --text-white: #ffffff;
    --text-dim: rgba(255, 255, 255, 0.5);
    --glass: rgba(255, 255, 255, 0.05);
    --glass-border: rgba(255, 255, 255, 0.12);
}

body {
    background-color: var(--bg-dark); /* Image එක load වෙනකම් පේන්න */
    background-image: linear-gradient(rgba(10, 10, 10, 0.85), rgba(10, 10, 10, 0.75)), /* Image එක උඩින් අඳුරු තට්ටුවක් */
                      url('uploads/resources/bg2.jpg'); /* ඔයාගේ Image එක */
    background-size: cover; /* Page එක පුරාම පේන්න */
    background-position: center; /* මැදින් පේන්න */
    background-attachment: fixed; /* Scroll වෙනකොට image එක static වෙලා තියෙන්න */
    min-height: 100vh;
    font-family: 'Plus Jakarta Sans', sans-serif;
    color: #fff;
    margin: 0;
}

/* --- STEP TRANSITION LOGIC (මෙන්න මේකයි ඕනේ වුණේ) --- */
.tab-pane {
    display: none; /* මුලින් ඔක්කොම hide කරනවා */
    animation: fadeIn 0.4s ease-out forwards;
}

.tab-pane.active {
    display: block !important; /* Active step එක විතරක් පෙන්වනවා */
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* --- NAVIGATION & HEADERS --- */
.top-nav {
    background: rgba(8, 8, 8, 0.9);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--glass-border);
    padding: 12px 0;
    position: sticky;
    top: 0;
    z-index: 1000;
}

.nav-brand-text {
    font-family: 'Playfair Display', serif !important;
    font-size: 1.2rem;
    font-weight: 800;
    letter-spacing: 2px;
    text-transform: uppercase;
}

/* --- MAIN REGISTRATION CARD --- */
.main-card {
    background: var(--glass);
    backdrop-filter: blur(30px);
    border: 1px solid var(--glass-border);
    border-radius: 35px;
    padding: 40px;
    margin-top: 25px;
    box-shadow: 0 40px 100px rgba(0,0,0,0.8);
}

.step-title {
    font-family: 'Playfair Display', serif !important;
    font-size: 1.8rem;
    font-weight: 800;
    margin-bottom: 25px;
    text-transform: uppercase;
    color: #fff;
}

/* --- FORM CONTROLS --- */
.form-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: var(--text-white);
    margin-bottom: 8px;
    display: block;
}

.st-input {
    background: rgba(255, 255, 255, 0.06) !important;
    border: 1px solid var(--glass-border) !important;
    border-radius: 14px !important;
    color: #fff !important;
    padding: 14px 18px !important;
    font-size: 15px !important;
    transition: 0.3s !important;
    width: 100%;
}

.st-input:focus {
    background: rgba(255, 255, 255, 0.1) !important;
    border-color: #fff !important;
    box-shadow: 0 0 15px rgba(255,255,255,0.05) !important;
    outline: none;
}

/* --- BUTTONS --- */
.btn-premium {
    background: #ffffff;
    color: #000000;
    border: none;
    border-radius: 14px;
    padding: 16px;
    font-family: 'Playfair Display', serif;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    width: 100%;
    margin-top: 15px;
    transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.btn-premium:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(255,255,255,0.1);
}

.btn-link {
    color: var(--text-dim) !important;
    font-size: 12px;
    text-decoration: none !important;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

/* --- MOBILE BOTTOM NAV --- */
.mobile-nav {
    position: fixed;
    bottom: 20px;
    left: 20px;
    right: 20px;
    height: 75px;
    background: rgba(12, 12, 12, 0.95);
    backdrop-filter: blur(25px);
    border-radius: 25px;
    border: 1px solid var(--glass-border);
    display: flex;
    justify-content: space-around;
    align-items: center;
    z-index: 1000;
}

.nav-item-m {
    text-align: center;
    color: rgba(255,255,255,0.3);
    text-decoration: none;
    font-size: 10px;
    font-weight: 700;
}

.nav-item-m.active {
    color: #fff;
}

.nav-item-m i {
    font-size: 22px;
    display: block;
    margin-bottom: 2px;
}

/* --- 100% RESPONSIVE ADJUSTMENTS --- */
@media (max-width: 991px) {
    .main-card { padding: 30px; border-radius: 30px; }
}

@media (max-width: 576px) {
    .main-card {
        padding: 25px 20px;
        margin-top: 15px;
        border-radius: 25px;
        border: none; /* Mobile වලදී පිරිසිදු පෙනුමට border අයින් කළ හැක */
        background: rgba(255,255,255,0.03);
    }
    
    .step-title { font-size: 1.4rem; }
    
    .btn-premium {
        padding: 14px;
        font-size: 13px;
    }

    /* Mobile එකේදී input එක focus කළාම zoom වෙන එක වැළැක්වීමට */
    .st-input { font-size: 16px !important; } 
}
    </style>
</head>
<body>

    <div id="toast-container"></div>

    <nav class="top-nav">
        <div class="container d-flex align-items-center">
            <a href="index.php" class="back-btn"><i class="bi bi-chevron-left"></i></a>
            <div class="ms-3">
                <h5 class="m-0 fw-bold" style="background: linear-gradient(to right, #fff, var(--primary-neon)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Account Setup</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Step <span id="step-num">1</span> of 3</small>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="main-card">
                    <form id="regForm" action="process_register.php" method="POST" enctype="multipart/form-data">
                        
                        <div class="tab-pane active" id="step1">
                            <h3 class="fw-bold mb-4" style="color: var(--primary-neon);">Business Identity</h3>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Business Name</label>
                                    <input type="text" name="b_name" class="form-control st-input" placeholder="Enter Official Name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Business Type</label>
                                    <input type="text" name="b_type" class="form-control st-input" placeholder="e.g. Hotel / Garment" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">BR / Registration Number</label>
                                    <input type="text" name="b_reg" class="form-control st-input" placeholder="Enter Registration ID" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Official Address</label>
                                    <textarea name="b_addr" class="form-control st-input" rows="2" placeholder="Full Location Details" required></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Business Logo</label>
                                    <input type="file" name="b_logo" class="form-control st-input" accept="image/*">
                                </div>
                            </div>
                            <button type="button" class="btn btn-neon mt-4" onclick="changeStep(2)">Proceed Next <i class="bi bi-arrow-right-short"></i></button>
                        </div>

                        <div class="tab-pane" id="step2">
                            <h3 class="fw-bold mb-4" style="color: var(--primary-neon);">Representative Info</h3>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="f_name" class="form-control st-input" placeholder="Contact Person's Name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control st-input" placeholder="07XXXXXXXX" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control st-input" placeholder="email@business.com" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">NIC Number</label>
                                    <input type="text" name="nic" class="form-control st-input" placeholder="National Identity Card No" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Profile Image</label>
                                    <input type="file" name="p_img" class="form-control st-input" accept="image/*">
                                </div>
                            </div>
                            <div class="d-grid gap-2 mt-4">
                                <button type="button" class="btn btn-neon" onclick="changeStep(3)">Continue</button>
                                <button type="button" class="btn btn-link text-secondary text-decoration-none fw-bold" onclick="changeStep(1)">Go Back</button>
                            </div>
                        </div>

                        <div class="tab-pane" id="step3">
                            <h3 class="fw-bold mb-4" style="color: var(--primary-neon);">Security Setup</h3>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Access Password</label>
                                    <input type="password" name="pw" id="pw" class="form-control st-input" placeholder="Minimum 6 characters" required minlength="6">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" name="cpw" id="cpw" class="form-control st-input" placeholder="Re-enter password" required>
                                </div>
                            </div>
                            <div class="mt-4 p-3 rounded-4 bg-info bg-opacity-10 border border-info border-opacity-20">
                                <small class="text-info d-flex"><i class="bi bi-shield-check me-2 fs-5"></i> Admin will verify your information before activation.</small>
                            </div>
                            <button type="submit" class="btn btn-neon mt-4">Submit Application</button>
                            <button type="button" class="btn btn-link text-secondary w-100 mt-2 text-decoration-none fw-bold" onclick="changeStep(2)">Back to Info</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    

    <script>
        // --- CUSTOM TOAST LOGIC ---
        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast-msg ${type}`;
            
            const icon = type === 'error' ? 'bi-exclamation-circle' : (type === 'success' ? 'bi-check-circle' : 'bi-info-circle');
            toast.innerHTML = `<i class="bi ${icon}"></i> <span>${message}</span>`;
            
            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('fade-out');
                setTimeout(() => toast.remove(), 500);
            }, 4000);
        }

        // --- STEP TRANSITION & VALIDATION ---
        function changeStep(target) {
            const currentTab = document.querySelector('.tab-pane.active');
            const currentNum = parseInt(currentTab.id.replace('step', ''));

            // Only validate if moving FORWARD
            if (target > currentNum) {
                const requiredInputs = currentTab.querySelectorAll('input[required], textarea[required]');
                let valid = true;

                for (let input of requiredInputs) {
                    if (!input.checkValidity()) {
                        const labelName = input.previousElementSibling ? input.previousElementSibling.innerText : "Field";
                        showToast(`${labelName} Complete it!`, 'error');
                        input.focus();
                        valid = false;
                        break;
                    }
                }
                if (!valid) return;
            }

            // UI Updates
            document.querySelectorAll('.tab-pane').forEach(t => t.classList.remove('active'));
            document.getElementById('step' + target).classList.add('active');
            document.getElementById('step-num').innerText = target;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // --- FINAL SUBMISSION CHECK ---
        document.getElementById('regForm').onsubmit = function(e) {
            const pw = document.getElementById('pw').value;
            const cpw = document.getElementById('cpw').value;

            if (pw !== cpw) {
                showToast("The two passwords do not match!", "error");
                e.preventDefault();
                return false;
            }
            return true;
        };
    </script>
</body>
</html>