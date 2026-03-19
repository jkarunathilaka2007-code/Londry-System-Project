<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['emp_name'];
    $nic = $_POST['emp_nic'];
    $email = $_POST['emp_email'];
    $phone = $_POST['emp_phone'];
    $emergency = $_POST['emp_emergency'];
    $role = $_POST['emp_role'];
    $pw = password_hash($_POST['emp_pw'], PASSWORD_DEFAULT);
    
    // Image Upload
    $p_img = "default_user.png";
    if (isset($_FILES['emp_img']) && $_FILES['emp_img']['error'] == 0) {
        $p_img = "EMP_" . time() . "_" . $_FILES['emp_img']['name'];
        move_uploaded_file($_FILES['emp_img']['tmp_name'], 'uploads/profiles/' . $p_img);
    }

    try {
        $sql = "INSERT INTO employees (full_name, nic_number, email, phone, emergency_contact, designation, profile_image, password) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $nic, $email, $phone, $emergency, $role, $p_img, $pw]);

        echo "<script>alert('Employee Registered Successfully!'); window.location='employee_register.php';</script>";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>