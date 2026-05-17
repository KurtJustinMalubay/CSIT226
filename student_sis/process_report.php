<?php
/**
 * Report Submission Handler
 * 
 * This script processes 'Lost' and 'Found' item reports submitted by students.
 * It validates the input, saves the report to the database with a 'Pending Verification' 
 * status, and redirects back to the dashboard with a status message.
 */

session_start();

// Security check: Ensure the user is logged in
if (!isset($_SESSION['uid'])) { 
    header('Location: login.php'); 
    exit; 
}

include 'connect.php';

// Process only POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form data
    $reporterId  = $_SESSION['uid'];
    $itemName    = trim($_POST['itemName']);
    $location    = trim($_POST['location']);
    $description = trim($_POST['description']);
    $category    = $_POST['category'];
    $eventDate   = $_POST['eventDate'];
    $reportType  = $_POST['reportType']; // Determines if it's a 'Lost' or 'Found' item
    
    // All student-submitted reports start as 'Pending Verification' 
    // until an admin reviews them in the Report Management Center.
    $currentStatus = 'Pending Verification';

    // Basic server-side validation
    if (empty($itemName) || empty($location)) {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Item Name and Location are required.'];
        header("Location: dashboard.php");
        exit;
    }

    // Prepare and execute the insertion into item_report table
    $stmt = $connection->prepare("INSERT INTO item_report (reporterId, itemName, location, description, category, eventDate, reportType, currentStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $reporterId, $itemName, $location, $description, $category, $eventDate, $reportType, $currentStatus);

    if ($stmt->execute()) {
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Report submitted successfully! Please proceed to the SAO office to verify.'];
    } else {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Error submitting report: ' . $connection->error];
    }
    
    $stmt->close();
    header("Location: dashboard.php");
    exit;
}
?>
