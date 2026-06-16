<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
    header('Location: login.php');
    exit;
}

require_once 'db_config.php';

$id = isset($_GET['id']) ? $_GET['id'] : '';

if (!empty($id)) {
    // Escaping the ID for security
    $id_esc = $conn->real_escape_string($id);
    
    // Deleting from Feedback_forms will automatically delete from Feedback_details,
    // Rate_staff, and Rate_dish due to ON DELETE CASCADE constraints.
    $delete_sql = "DELETE FROM Feedback_forms WHERE Feedback_ID = '$id_esc'";

    if ($conn->query($delete_sql) === TRUE) {
        header('Location: feedback_list.php');
        exit;
    } else {
        echo "<h2 align='center'><font color='red'>刪除失敗!!</font></h2>";
        echo "<p align='center'>" . htmlspecialchars($conn->error) . "</p>";
        echo "<p align='center'><a href='feedback_list.php'>返回回饋列表</a></p>";
    }
} else {
    echo "資料不完全";
}
?>