<?php
/**
 * Administrative Dashboard
 * 
 * This page provides a high-level overview of the system for administrators.
 * It includes real-time statistics, quick-access links to report management,
 * and a directory for managing student records.
 */

session_start();
if (!isset($_SESSION['uid']) || !$_SESSION['isAdmin']) { header('Location: login.php'); exit; }
include 'connect.php';
$title = 'Admin Dashboard';

/**
 * Handle Claim Actions (Approve/Deny)
 * 
 * Process requests from students who have claimed an item.
 * Approving a claim marks the item as 'Returned' and logs the action.
 */
if (isset($_POST['action_claim'])) {
    $claimId = intval($_POST['claimId']);
    $action = $_POST['action_claim']; // 'Approve' or 'Deny'
    $adminId = $_SESSION['uid'];
    
    // Get claim and report details
    $cStmt = $connection->prepare("SELECT reportId FROM claim_request WHERE claimId = ?");
    $cStmt->bind_param("i", $claimId);
    $cStmt->execute();
    $cRes = $cStmt->get_result();
    if ($cRes->num_rows > 0) {
        $claim = $cRes->fetch_assoc();
        $reportId = $claim['reportId'];
        
        $connection->begin_transaction();
        try {
            if ($action === 'Approve') {
                // Update claim status
                $connection->query("UPDATE claim_request SET claimStatus='Approved', approveAdminId='$adminId' WHERE claimId=$claimId");
                
                // Get old status of report
                $oldStatusRes = $connection->query("SELECT currentStatus FROM item_report WHERE reportId=$reportId");
                $oldStatus = $oldStatusRes->fetch_assoc()['currentStatus'];
                
                // Update report status
                $connection->query("UPDATE item_report SET currentStatus='Returned' WHERE reportId=$reportId");
                
                // Insert Audit Log
                $connection->query("INSERT INTO audit_log (reportId, adminId, oldStatus, newStatus) VALUES ($reportId, '$adminId', '$oldStatus', 'Returned')");
                
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Claim Approved and Item marked as Returned.'];
            } elseif ($action === 'Deny') {
                $connection->query("UPDATE claim_request SET claimStatus='Denied', approveAdminId='$adminId' WHERE claimId=$claimId");
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Claim has been Denied.'];
            }
            $connection->commit();
        } catch (Exception $e) {
            $connection->rollback();
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Error processing claim: ' . $e->getMessage()];
        }
    }
    header("Location: admin_dashboard.php");
    exit;
}

/**
 * Statistics & Overview Data
 * 
 * Fetches real-time counts to populate the dashboard stats cards.
 */
$total_reports = mysqli_fetch_assoc(mysqli_query($connection,"SELECT COUNT(*) as c FROM item_report"))['c'] ?? 0;
$pending_claims = mysqli_fetch_assoc(mysqli_query($connection,"SELECT COUNT(*) as c FROM claim_request WHERE claimStatus='Pending'"))['c'] ?? 0;
$resolved_cases = mysqli_fetch_assoc(mysqli_query($connection,"SELECT COUNT(*) as c FROM item_report WHERE currentStatus IN ('Returned', 'Resolved')"))['c'] ?? 0;

$flash = isset($_SESSION['flash']) ? $_SESSION['flash'] : null;
unset($_SESSION['flash']);

require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <span class="breadcrumb-sep">/</span>
            <span>Admin Dashboard</span>
        </div>
        <h1>Lost & Found Administration</h1>
        <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['fullName']); ?></strong></p>
    </div>
</div>

<!-- Quick Actions Section -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;margin-bottom:40px;">
    <div class="card" style="display:flex; flex-direction:column; justify-content:space-between;">
        <div>
            <div class="feature-icon" style="background:var(--primary-soft); color:var(--primary);"><i class="fas fa-file-invoice"></i></div>
            <h3 style="margin:16px 0 8px;">Reports & Claims</h3>
            <p style="color:var(--text-muted); font-size:14px; margin-bottom:20px;">Process pending claims, update item statuses, and manage the lost and found inventory.</p>
        </div>
        <div style="display:flex; align-items:center; justify-content:space-between; margin-top:auto; padding-top:16px; border-top:1px solid var(--border-color);">
            <div style="font-size:14px; font-weight:600; color:var(--warning);"><i class="fas fa-clock"></i> <?php echo $pending_claims; ?> Pending Claims</div>
            <a href="manage_reports.php" class="btn btn-primary btn-sm">Manage Reports</a>
        </div>
    </div>

    <div class="card" style="display:flex; flex-direction:column; justify-content:space-between;">
        <div>
            <div class="feature-icon" style="background:rgba(34,197,94,0.1); color:var(--success);"><i class="fas fa-users"></i></div>
            <h3 style="margin:16px 0 8px;">Student Records</h3>
            <p style="color:var(--text-muted); font-size:14px; margin-bottom:20px;">Manage student profiles, program details, and contact information for the campus.</p>
        </div>
        <div style="display:flex; align-items:center; justify-content:space-between; margin-top:auto; padding-top:16px; border-top:1px solid var(--border-color);">
            <div style="font-size:14px; font-weight:600; color:var(--text-muted);">View all enrolled students</div>
            <a href="addrecord.php" class="btn btn-outline btn-sm">Add New Student</a>
        </div>
    </div>
</div>

<!-- Stats row -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:28px;">
    <div class="card card-sm" style="display:flex;align-items:center;gap:16px;">
        <div class="feature-icon" style="margin:0;flex-shrink:0;"><i class="fas fa-boxes-stacked"></i></div>
        <div><div style="font-size:26px;font-weight:800;"><?php echo $total_reports; ?></div><div style="font-size:12px;color:var(--text-muted)">Total Reports</div></div>
    </div>
    <div class="card card-sm" style="display:flex;align-items:center;gap:16px;">
        <div class="feature-icon" style="margin:0;flex-shrink:0;background:rgba(245,158,11,0.15);border-color:rgba(245,158,11,0.3);color:#FCD34D;"><i class="fas fa-clipboard-question"></i></div>
        <div><div style="font-size:26px;font-weight:800;"><?php echo $pending_claims; ?></div><div style="font-size:12px;color:var(--text-muted)">Pending Claims</div></div>
    </div>
    <div class="card card-sm" style="display:flex;align-items:center;gap:16px;">
        <div class="feature-icon" style="margin:0;flex-shrink:0;background:rgba(34,197,94,0.15);border-color:rgba(34,197,94,0.3);color:#4ADE80;"><i class="fas fa-check-circle"></i></div>
        <div><div style="font-size:26px;font-weight:800;"><?php echo $resolved_cases; ?></div><div style="font-size:12px;color:var(--text-muted)">Resolved/Returned</div></div>
    </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?php echo $flash['type']; ?>">
    <i class="fas fa-<?php echo $flash['type']==='success'?'circle-check':'circle-exclamation'; ?>"></i>
    <?php echo htmlspecialchars($flash['msg']); ?>
</div>
<?php endif; ?>

<!-- Manage Students Section -->
<div class="table-wrap" style="margin-top: 20px;">
    <div class="table-toolbar">
        <h3 style="font-size: 18px;"><i class="fas fa-users-gear"></i> Student Directory</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Course/Program</th>
                <th>Contact</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        /**
         * Fetch and display all registered students
         */
        $students_q = "SELECT u.*, s.course, s.contactNo FROM user u JOIN student s ON u.uId = s.studId WHERE u.isStudent = 1 ORDER BY u.fullName ASC";
        $students = mysqli_query($connection, $students_q);
        if ($students->num_rows === 0): 
        ?>
            <tr><td colspan="6" class="table-empty"><i class="fas fa-users-slash"></i>No students registered yet.</td></tr>
        <?php else: while ($row = $students->fetch_assoc()): ?>
            <tr>
                <td class="td-bold"><?php echo htmlspecialchars($row['universityId']); ?></td>
                <td><?php echo htmlspecialchars($row['fullName']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><span class="badge badge-program"><?php echo htmlspecialchars($row['course']); ?></span></td>
                <td><?php echo htmlspecialchars($row['contactNo']); ?></td>
                <td>
                    <div style="display: flex; gap: 8px;">
                        <a href="update.php?uid=<?php echo $row['uId']; ?>" class="btn btn-outline btn-sm"><i class="fas fa-edit"></i> Edit</a>
                        <a href="delete.php?uid=<?php echo $row['uId']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this student?')"><i class="fas fa-trash"></i> Delete</a>
                    </div>
                </td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
