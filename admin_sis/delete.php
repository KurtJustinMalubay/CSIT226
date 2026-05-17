<?php
session_start();
// Check if user is logged in and is an admin
if (!isset($_SESSION['uid']) || !$_SESSION['isAdmin']) { 
    header('Location: ../student_sis/login.php'); 
    exit; 
}

include '../student_sis/connect.php';

$uid = isset($_GET['uid']) ? $_GET['uid'] : '';
if ($uid) {
    // Delete from user table (cascades to student/admin_staff tables)
    $stmt = $connection->prepare("DELETE FROM user WHERE uId = ?");
    $stmt->bind_param("s", $uid);
    if ($stmt->execute()) {
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'User deleted successfully.'];
    } else {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Failed to delete user.'];
    }
    $stmt->close();
}

header('Location: admin_dashboard.php');
exit;