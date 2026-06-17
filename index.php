<?php
session_start();

if (!isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] === 'manager') {
    header('Location: manager_home.php');
    exit;
}

if ($_SESSION['role'] === 'staff') {
    header('Location: staff_dashboard.php');
    exit;
}

if ($_SESSION['role'] === 'customer') {
    header('Location: customer_dashboard.php');
    exit;
}

header('Location: login.php');
exit;
?>
