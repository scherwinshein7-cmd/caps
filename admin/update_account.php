<?php
session_start();
include '../includes/db_connection.php';

// Only allow admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = mysqli_real_escape_string($conn, $_POST['employee_id']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
    $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
    $middlename = mysqli_real_escape_string($conn, $_POST['middlename']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $phonenumber = mysqli_real_escape_string($conn, $_POST['phonenumber']);

    $sql = "UPDATE account 
            SET password='$password', role='$role', department='$department', lastname='$lastname', 
                firstname='$firstname', middlename='$middlename', gender='$gender', 
                address='$address', phonenumber='$phonenumber'
            WHERE employee_id='$employee_id'";

    $redirect = $_SERVER['HTTP_REFERER'] ?? 'admin_dashboard.php';

    if (mysqli_query($conn, $sql)) {
        header("Location: $redirect?success=Account updated successfully");
    } else {
        header("Location: $redirect?error=Failed to update account");
    }
    exit();
}
?>
