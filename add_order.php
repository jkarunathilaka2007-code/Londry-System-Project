<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->query("SELECT * FROM services ORDER BY service_name ASC");
$services = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Create Order | Fabricare</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
        --bg-dark: #080808;
        --glass: rgba(255, 255, 255, 0.04);
        --glass-border: rgba(255, 255, 255, 0.12);
        --accent-neon: #fcfcfc;
        --nav-height: 65px; 
        --bottom-bar-height: 75px;
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
        padding-bottom: 160px !important;
    }
        /* Stepper */
        .step-header { display: flex; justify-content: space-between; margin: 30px 0; position: relative; }
        .step-header::before { content: ''; position: absolute; top: 15px; left: 0; width: 100%; height: 1px; background: var(--glass-border); z-index: 1; }
        .step-circle { width: 32px; height: 32px; border-radius: 50%; background: #08080a; border: 1px solid var(--glass-border); display: flex; align-items: center; justify-content: center; z-index: 2; font-size: 11px; font-weight: 700; transition: 0.4s; }
        .step-circle.active { border-color: var(--primary-neon); color: var(--primary-neon); box-shadow: 0 0 15px rgba(255, 255, 255, 0.3); }
        .step-circle.done { background: var(--primary-neon); color: #000; border-color: var(--primary-neon); }

        .step-panel { display: none; animation: slideUp 0.4s ease; }
        .step-panel.active { display: block; }

        .glass-card { background: var(--card-bg); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); border-radius: 20px; overflow: hidden; transition: 0.3s; position: relative; color: #fff; }
        .product-card { cursor: pointer; }
        .product-card:hover { border-color: rgba(255, 255, 255, 0.3); transform: translateY(-3px); }
        .product-card.selected { border-color: var(--primary-neon); background: rgba(0, 242, 254, 0.08); box-shadow: 0 0 20px rgba(0, 242, 254, 0.15); }
        
        .selected-badge { position: absolute; top: 15px; right: 15px; background: var(--primary-neon); color: #000; border-radius: 50%; width: 22px; height: 22px; display: none; align-items: center; justify-content: center; z-index: 10; font-size: 12px; }
        .product-card.selected .selected-badge { display: flex; }

        .card-img-container { width: 100%; height: 160px; background: rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center; }
        .product-card-img { width: 100%; height: 100%; object-fit: contain; padding: 15px; }
        
        .qty-input-box { background: rgba(0,0,0,0.4) !important; border: 1px solid var(--glass-border) !important; color: #fff !important; border-radius: 10px !important; }

        .search-input { background: rgba(255,255,255,0.05); backdrop-filter: blur(15px); border: 1px solid var(--glass-border); border-radius: 50px; padding: 12px 25px; color: #fff; width: 100%; outline: none; margin-bottom: 25px; }

        .bottom-bar { 
    position: fixed; 
    bottom: 65px; 
    left: 0; 
    right: 0; 
    height: auto; 
    background: rgba(15, 15, 15, 0.98); 
    backdrop-filter: blur(20px); 
    border-top: 1px solid var(--glass-border); 
    padding: 12px 20px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    /* මේ පේළි දෙක අනිවාර්යයෙන්ම බලන්න */
    z-index: 2000 !important; 
    pointer-events: all !important; 
}
        @keyframes slideUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
        .top-nav {
    background: rgba(10, 10, 10, 0.9) !important;
    backdrop-filter: blur(30px);
    border-bottom: 1px solid var(--glass-border);
    padding: 10px 0;
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
/* --- MOBILE NAV --- */
    .mobile-nav {
        position: fixed; bottom: 0; left: 0; right: 0;
        height: 65px; background: rgba(10, 10, 10, 0.98);
        backdrop-filter: blur(25px); border-radius: 20px 20px 0 0;
        border-top: 1px solid var(--glass-border);
        display: flex; justify-content: space-around; align-items: center; z-index: 1050 !important;
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
    @media (min-width: 992px) {
    .bottom-bar { 
        bottom: 0; 
    }
    body { 
        padding-bottom: 80px !important; 
    }
} 
#btnNext {
    position: relative;
    z-index: 2001 !important;
    pointer-events: auto !important;
}   
    </style>
</head>
<body>
    <nav class="top-nav">
    <div class="container d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <a href="customer_dashboard.php" class="back-btn"><i class="bi bi-chevron-left"></i></a>
            <div class="ms-3">
                <h5 class="m-0 fw-bold" style="background: linear-gradient(to right, #fff, var(--primary-neon)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Attendance</h5>
                <small class="text-white-50" style="font-size: 0.7rem;">Workshop Terminal</small>
            </div>
        </div>
    </div>
</nav>

<div class="container py-2">
    <div class="step-header px-4">
        <div class="step-circle active" id="node1">1</div>
        <div class="step-circle" id="node2">2</div>
        <div class="step-circle" id="node3">3</div>
    </div>

    <form id="mainOrderForm" action="save_order.php" method="POST">
        
        <div class="step-panel active" id="panel1">
            <input type="text" class="search-input" id="serviceSearch" placeholder="Search services...">
            <div class="row g-3" id="serviceGrid">
                <?php foreach($services as $item): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="glass-card product-card" data-name="<?= strtolower($item['service_name']) ?>" 
                         onclick="selectService(<?= $item['id'] ?>, '<?= htmlspecialchars($item['service_name']) ?>', <?= $item['price'] ?>, '<?= $item['service_image'] ?>', this)">
                        <div class="selected-badge"><i class="bi bi-check"></i></div>
                        <div class="card-img-container">
                            <img src="uploads/services/<?= $item['service_image'] ?>" class="product-card-img" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3003/3003984.png'">
                        </div>
                        <div class="p-3 text-center border-top border-white border-opacity-10">
                            <h6 class="fw-bold text-uppercase small m-0 mb-1"><?= htmlspecialchars($item['service_name']) ?></h6>
                            <div class="text-info fw-bold">LKR <?= number_format($item['price'], 2) ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="step-panel" id="panel2">
            <div class="mx-auto" style="max-width: 700px;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0">Set <span class="text-info">Details</span></h5>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="splitDatesToggle" onchange="toggleDateVisibility()">
                        <label class="form-check-label small text-white-50" for="splitDatesToggle">Different delivery dates?</label>
                    </div>
                </div>
                <div id="qtyListContainer"></div>
            </div>
        </div>

        <div class="step-panel" id="panel3">
            <div class="mx-auto" style="max-width: 550px;">
                <div class="glass-card p-4 shadow-lg">
                    <h5 class="fw-bold mb-4 border-bottom border-secondary pb-2">Finalize <span class="text-info">Order</span></h5>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="text-white-50 mb-1" style="font-size: 10px;">Drop-off Date</label>
                            <input type="date" name="order_date" id="order_date" class="form-control form-control-sm qty-input-box" 
                                   value="<?= date('Y-m-d') ?>" onchange="updateAllDates()">
                        </div>
                        <div class="col-6" id="globalDueContainer">
                          <label class="text-white-50 mb-1" style="font-size: 10px;">Est. Completion Date</label>
                            <input type="date" name="due_date" id="due_date" class="form-control form-control-sm qty-input-box">
                        </div>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" name="pickup_required" id="pickupCheck">
                        <label class="form-check-label ms-2" for="pickupCheck">Request Pickup & Delivery</label>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="agreeCheck" required>
                        <label class="form-check-label ms-2 small" for="agreeCheck">I agree to the service terms.</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="bottom-bar">
            <div>
                <div class="small opacity-50 text-uppercase" style="font-size: 9px; letter-spacing: 1px;">Total Amount</div>
                <div class="fw-bold text-info fs-5">LKR <span id="grandTotal">0.00</span></div>
            </div>
            <div>
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 me-2 d-none" id="btnBack" onclick="moveStep(-1)">Back</button>
                <button type="button" class="btn btn-info rounded-pill px-4 fw-bold shadow-sm" id="btnNext" onclick="moveStep(1)" disabled>Next Step</button>
            </div>
        </div>
    </form>
</div>
<div class="mobile-nav">
    <a href="index.php" class="nav-item-m"><i class="bi bi-house"></i><span>Home</span></a>
    <a href="customer_dashboard.php" class="nav-item-m active"><i class="bi bi-person"></i><span>Profile</span></a>
    <a href="logout.php" class="nav-item-m text-danger"><i class="bi bi-power"></i><span>Logout</span></a>
</div>

<script>
    let currentStep = 1;
    let basket = {};

    function selectService(id, name, price, img, el) {
        if (basket[id]) { 
            delete basket[id]; 
            el.classList.remove('selected'); 
        } else { 
            basket[id] = { id, name, price, img, qty: 1, date: '' }; 
            el.classList.add('selected'); 
        }
        document.getElementById('btnNext').disabled = Object.keys(basket).length === 0;
        updateTotal();
    }

    function toggleDateVisibility() {
        const isSplit = document.getElementById('splitDatesToggle').checked;
        document.querySelectorAll('.individual-date-box').forEach(el => el.classList.toggle('d-none', !isSplit));
        document.getElementById('globalDueContainer').classList.toggle('opacity-50', isSplit);
    }

    function updateAllDates() {
        const orderDate = document.getElementById('order_date').value;
        const dueDateInput = document.getElementById('due_date');
        
        if (orderDate) {
            let date = new Date(orderDate);
            date.setDate(date.getDate() + 3);
            let calculatedDate = date.toISOString().split('T')[0];
            
            if(!dueDateInput.value) dueDateInput.value = calculatedDate;
            
            document.querySelectorAll('.item-date-input').forEach(input => {
                if(!input.value) input.value = dueDateInput.value;
            });
        }
    }

    function renderStep2() {
        const container = document.getElementById('qtyListContainer');
        container.innerHTML = '';
        const isSplit = document.getElementById('splitDatesToggle').checked;
        
        for (let id in basket) {
            let item = basket[id];
            container.innerHTML += `
                <div class="glass-card p-3 mb-3">
                    <div class="d-flex align-items-center">
                        <img src="uploads/services/${item.img}" style="width: 50px; height: 50px; object-fit: contain;" class="me-3" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3003/3003984.png'">
                        <div class="flex-grow-1">
                            <div class="fw-bold text-uppercase small">${item.name}</div>
                            <div class="text-info small fw-bold">LKR ${item.price.toFixed(2)}</div>
                        </div>
                        <div class="ms-3 text-center">
                            <label style="font-size: 8px; opacity: 0.5;">QTY</label>
                            <input type="number" name="qty[${id}]" class="form-control form-control-sm qty-input-box text-center" style="width: 70px;" value="${item.qty}" min="1" onchange="basket[${id}].qty=this.value; updateTotal();">
                        </div>
                    </div>
                    <div class="individual-date-box mt-3 pt-2 border-top border-white border-opacity-10 ${isSplit ? '' : 'd-none'}">
                        <label style="font-size: 9px;" class="text-white-50">Delivery Date for this item:</label>
                        <input type="date" name="item_date[${id}]" class="form-control form-control-sm qty-input-box item-date-input" value="${item.date}">
                    </div>
                </div>`;
        }
        updateAllDates();
    }

    function moveStep(dir) {
        if (dir === 1 && currentStep === 1) renderStep2();
        if (dir === 1 && currentStep === 3) {
            document.getElementById('mainOrderForm').submit();
            return;
        }
        document.getElementById(`panel${currentStep}`).classList.remove('active');
        document.getElementById(`node${currentStep}`).classList.remove('active');
        if (dir === 1) document.getElementById(`node${currentStep}`).classList.add('done');
        
        currentStep += dir;
        
        document.getElementById(`panel${currentStep}`).classList.add('active');
        document.getElementById(`node${currentStep}`).classList.add('active');
        document.getElementById('btnBack').classList.toggle('d-none', currentStep === 1);
        document.getElementById('btnNext').innerText = currentStep === 3 ? 'PLACE ORDER' : 'Next Step';
    }

    function updateTotal() {
        let total = 0;
        for (let id in basket) total += basket[id].price * basket[id].qty;
        document.getElementById('grandTotal').innerText = total.toLocaleString('en-US', {minimumFractionDigits: 2});
    }

    document.getElementById('serviceSearch').addEventListener('input', (e) => {
        let val = e.target.value.toLowerCase();
        document.querySelectorAll('.product-card').forEach(card => card.parentElement.style.display = card.dataset.name.includes(val) ? 'block' : 'none');
    });

    window.onload = updateAllDates;
</script>
</body>
</html>