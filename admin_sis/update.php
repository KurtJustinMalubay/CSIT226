if (isset($_POST['btnUpdateStatus'])) {
    $reportId = $_POST['txtreportId'];
    $adminId = $_SESSION['uid']; // Logged in admin
    $newStatus = $_POST['txtstatus'];
    $oldStatus = $_POST['txtoldstatus'];

    // 1. Update the Item Report
    $u1 = $connection->prepare("UPDATE Item_Report SET currentStatus = ? WHERE reportId = ?");
    $u1->bind_param("si", $newStatus, $reportId);
    $u1->execute();
    $u1->close();

    // 2. Create Audit Log (Special Admin Action)
    $log = $connection->prepare("INSERT INTO Audit_Log(reportId, adminId, oldStatus, newStatus) VALUES(?,?,?,?)");
    $log->bind_param("iiss", $reportId, $adminId, $oldStatus, $newStatus);
    $log->execute();
    $log->close();
}