<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin | Staff Onboarding</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    
    <style>
    :root {
        --bg-dark: #080808;
        --glass: rgba(255, 255, 255, 0.05);
        --glass-border: rgba(255, 255, 255, 0.12);
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

    /* --- SIDE TOAST NOTIFICATIONS --- */
    #toast-container { position: fixed; top: 25px; right: 20px; z-index: 9999; }
    .toast-msg {
        background: rgba(20, 20, 20, 0.9); backdrop-filter: blur(15px);
        border: 1px solid var(--glass-border); border-left: 4px solid #fff;
        color: #fff; padding: 14px 20px; border-radius: 12px; margin-bottom: 10px;
        display: flex; align-items: center; gap: 12px; min-width: 280px;
        animation: toastIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    }
    .toast-msg.error { border-left-color: #ff4d4d; }
    @keyframes toastIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

    /* --- TOP NAV --- */
    .top-nav {
        background: rgba(10, 10, 10, 0.8);
        backdrop-filter: blur(30px);
        border-bottom: 1px solid var(--glass-border);
        padding: 15px 0;
        position: sticky; top: 0; z-index: 1000;
    }

    .top-nav h5 {
        color: #ffffff !important;
        font-weight: 700 !important;
        text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        background: none !important;
        -webkit-text-fill-color: initial !important;
    }

    .back-btn {
        width: 42px; height: 42px; border-radius: 12px;
        background: var(--glass); border: 1px solid var(--glass-border);
        display: flex; align-items: center; justify-content: center;
        color: #fff !important; text-decoration: none; transition: 0.3s;
    }

    /* --- MAIN CARD --- */
    .main-card {
        background: rgba(255, 255, 255, 0.03); 
        backdrop-filter: blur(25px);
        border: 1px solid var(--glass-border); 
        border-radius: 30px;
        padding: 30px; 
        margin-top: 25px; 
        margin-bottom: 50px;
        box-shadow: 0 25px 60px rgba(0,0,0,0.4);
    }

    .form-label {
        font-size: 0.75rem; 
        font-weight: 600; 
        color: rgba(255,255,255,0.5);
        text-transform: uppercase; 
        letter-spacing: 1.5px; 
        margin-bottom: 10px;
    }

    .st-input, .st-select {
        background: rgba(255, 255, 255, 0.05) !important;
        border: 1px solid var(--glass-border) !important;
        border-radius: 15px !important; 
        color: #fff !important;
        padding: 14px 18px !important; 
        font-size: 15px !important;
        transition: 0.3s;
    }

    .st-input:focus, .st-select:focus {
        background: rgba(255, 255, 255, 0.08) !important;
        border-color: rgba(255, 255, 255, 0.3) !important;
        box-shadow: none !important;
    }

    .st-select option { background: #121216; color: #fff; }

    .btn-neon {
        background: #ffffff !important;
        border: none; 
        border-radius: 18px; 
        padding: 16px;
        color: #000 !important; 
        font-weight: 800; 
        width: 100%; 
        transition: 0.3s;
        text-transform: uppercase; 
        letter-spacing: 2px;
        font-size: 14px;
        margin-top: 20px;
    }

    .btn-neon:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(255,255,255,0.1);
        opacity: 0.9;
    }

    /* --- NAVIGATION LOGIC --- */
    .mobile-nav { display: none !important; }

    @media (max-width: 991px) {
        .main-container { padding-bottom: 120px !important; }
        
        .mobile-nav {
            display: flex !important; 
            position: fixed; bottom: 0px; left: 0px; right: 0px;
            height: 65px; background: rgba(15, 15, 15, 0.95);
            backdrop-filter: blur(25px); border-radius: 25px 25px 0 0;
            border: 1px solid var(--glass-border); justify-content: space-around; 
            align-items: center; z-index: 1050;
        }
    }

    .nav-item-m { text-align: center; color: rgba(255,255,255,0.3); text-decoration: none; font-size: 10px; font-weight: 700; }
    .nav-item-m.active { color: #fff; }
    .nav-item-m i { font-size: 22px; display: block; }
</style>
</head>
<body>

    <div id="toast-container"></div>

    <nav class="top-nav">
        <div class="container d-flex align-items-center">
            <a href="staff.php" class="back-btn"><i class="bi bi-chevron-left"></i></a>
            <div class="ms-3">
                <h5 class="m-0 fw-bold" style="background: linear-gradient(to right, #fff, var(--primary-neon)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Staff Onboarding</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Admin Control Panel</small>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="main-card">
                    <form id="empForm" action="process_employee.php" method="POST" enctype="multipart/form-data">
                        
                        <h3 class="fw-bold mb-4" style="color: var(--primary-neon);">New Employee Profile</h3>
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="emp_name" class="form-control st-input" placeholder="e.g. Kasun Perera" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NIC Number</label>
                                <input type="text" name="emp_nic" class="form-control st-input" placeholder="National ID" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="emp_phone" class="form-control st-input" placeholder="07XXXXXXXX" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="emp_email" class="form-control st-input" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Emergency Contact</label>
                                <input type="tel" name="emp_emergency" class="form-control st-input" placeholder="Family member's No">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Job Role</label>
                                <select name="emp_role" class="form-select st-select" required>
                                    <option value="Staff">General Staff</option>
                                    <option value="Manager">Manager</option>
                                    <option value="Washer">Washer</option>
                                    <option value="Ironing">Ironing Specialist</option>
                                    <option value="Delivery">Delivery Rider</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Profile Image</label>
                                <input type="file" name="emp_img" class="form-control st-input" accept="image/*">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Login Password</label>
                                <input type="password" name="emp_pw" id="pw" class="form-control st-input" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="emp_cpw" id="cpw" class="form-control st-input" required>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-neon">Create Employee Account</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="mobile-nav">
        <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
        <a href="admin_dashboard.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
        <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
    </div>

    <script>
        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast-msg ${type}`;
            toast.innerHTML = `<i class="bi bi-exclamation-circle"></i> <span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('fade-out');
                setTimeout(() => toast.remove(), 500);
            }, 4000);
        }

        document.getElementById('empForm').onsubmit = function(e) {
            const pw = document.getElementById('pw').value;
            const cpw = document.getElementById('cpw').value;

            if (pw !== cpw) {
                showToast("මුරපද දෙක ගැලපෙන්නේ නැත!", "error");
                e.preventDefault();
                return false;
            }
            return true;
        };
    </script>
</body>
</html>