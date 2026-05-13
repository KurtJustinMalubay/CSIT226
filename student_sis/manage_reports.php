<?php
/**
 * Report Management Center (Admin Only)
 * 
 * This page allows administrators to:
 * 1. Verify student submissions (moving them from 'Pending Verification' to public lists).
 * 2. Process claim requests (Approve/Deny) from students.
 * 3. Update status of any reported item (Found -> Returned, etc.).
 * 4. Delete/Reject invalid reports.
 */

session_start();
if (!isset($_SESSION['uid']) || !$_SESSION['isAdmin']) { 
    header('Location: login.php'); 
    exit; 
}
include 'connect.php';
$title = 'Manage Reports';

/**
 * Handle Claim Actions (Approve/Deny)
 * 
 * When an admin approves a claim:
 * - Claim status becomes 'Approved'
 * - Item report status becomes 'Returned'
 * - Action is recorded in audit_log
 */
if (isset($_POST['action_claim'])) {
    $claimId = intval($_POST['claimId']);
    $action = $_POST['action_claim']; // 'Approve' or 'Deny'
    $adminId = $_SESSION['uid'];
    
    // Fetch related report ID
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
                // Update the specific claim status
                $connection->query("UPDATE claim_request SET claimStatus='Approved', approveAdminId='$adminId' WHERE claimId=$claimId");
                
                // Get current status for the audit log before changing it
                $oldStatusRes = $connection->query("SELECT currentStatus FROM item_report WHERE reportId=$reportId");
                $oldStatus = $oldStatusRes->fetch_assoc()['currentStatus'];
                
                // Mark the main item report as Returned
                $connection->query("UPDATE item_report SET currentStatus='Returned' WHERE reportId=$reportId");
                
                // Record the status transition in the audit log
                $connection->query("INSERT INTO audit_log (reportId, adminId, oldStatus, newStatus) VALUES ($reportId, '$adminId', '$oldStatus', 'Returned')");
                
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Claim Approved and Item marked as Returned.'];
            } elseif ($action === 'Deny') {
                // Simply mark the claim as denied without affecting the item status
                $connection->query("UPDATE claim_request SET claimStatus='Denied', approveAdminId='$adminId' WHERE claimId=$claimId");
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Claim has been Denied.'];
            }
            $connection->commit();
        } catch (Exception $e) {
            $connection->rollback();
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Error processing claim: ' . $e->getMessage()];
        }
    }
    header("Location: manage_reports.php");
    exit;
}

/**
 * Handle Report Actions (Status Update / Delete / Verify)
 * 
 * - UpdateStatus: Manually change item status (e.g., Found -> Archived).
 * - Verify: Move student submission to active list and add admin details (Storage/Notes).
 * - Delete: Permanently remove report and related claims.
 */
if (isset($_POST['action_report'])) {
    $reportId = intval($_POST['reportId']);
    $action = $_POST['action_report'];
    $adminId = $_SESSION['uid'];

    if ($action === 'Delete') {
        $connection->begin_transaction();
        try {
            // Delete related audit logs and claims first to satisfy constraints
            $connection->query("DELETE FROM claim_request WHERE reportId=$reportId");
            $connection->query("DELETE FROM audit_log WHERE reportId=$reportId");
            $connection->query("DELETE FROM item_report WHERE reportId=$reportId");
            $connection->commit();
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Report deleted successfully.'];
        } catch (Exception $e) {
            $connection->rollback();
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Error deleting report: ' . $e->getMessage()];
        }
    } elseif ($action === 'UpdateStatus' || $action === 'Verify') {
        $newStatus = $_POST['newStatus'];
        $category = $_POST['category'] ?? '';
        $storage = $_POST['storage_location'] ?? '';
        $remarks = $_POST['admin_remarks'] ?? '';
        
        $oldStatusRes = $connection->query("SELECT currentStatus FROM item_report WHERE reportId=$reportId");
        $oldStatus = $oldStatusRes->fetch_assoc()['currentStatus'];

        $connection->begin_transaction();
        try {
            // Update report with the new status and administrative details
            $stmt = $connection->prepare("UPDATE item_report SET currentStatus=?, category=?, storage_location=?, admin_remarks=? WHERE reportId=?");
            $stmt->bind_param("ssssi", $newStatus, $category, $storage, $remarks, $reportId);
            $stmt->execute();
            
            // Log status change if status actually changed
            if ($oldStatus !== $newStatus) {
                $connection->query("INSERT INTO audit_log (reportId, adminId, oldStatus, newStatus) VALUES ($reportId, '$adminId', '$oldStatus', '$newStatus')");
            }
            
            $connection->commit();
            $msg = ($action === 'Verify') ? "Report verified and moved to $newStatus." : "Report updated successfully.";
            $_SESSION['flash'] = ['type' => 'success', 'msg' => $msg];
        } catch (Exception $e) {
            $connection->rollback();
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Error processing report: ' . $e->getMessage()];
        }
    }
    header("Location: manage_reports.php");
    exit;
}

/**
 * Data Fetching Section
 */

// 1. Fetch reports waiting for admin verification
$pending_v_q = "SELECT i.*, u.fullName as reporterName 
                FROM item_report i 
                JOIN user u ON i.reporterId = u.uId 
                WHERE i.currentStatus = 'Pending Verification' 
                ORDER BY i.created_at ASC";
$pending_v = mysqli_query($connection, $pending_v_q);

// 2. Fetch claims awaiting approval
$claims_q = "SELECT c.*, i.itemName, u.fullName as claimantName 
             FROM claim_request c 
             JOIN item_report i ON c.reportId = i.reportId 
             JOIN user u ON c.claimantId = u.uId 
             WHERE c.claimStatus = 'Pending' 
             ORDER BY c.claimDate ASC";
$claims = mysqli_query($connection, $claims_q);

// 3. Fetch all active items with optional type filter (Lost/Found)
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$where_clause = "";
if ($filter_type) {
    $where_clause = "WHERE i.reportType = '" . mysqli_real_escape_string($connection, $filter_type) . "'";
}

$items_q = "SELECT i.*, u.fullName as reporterName 
            FROM item_report i 
            JOIN user u ON i.reporterId = u.uId 
            $where_clause
            ORDER BY i.created_at DESC";
$items = mysqli_query($connection, $items_q);

$flash = isset($_SESSION['flash']) ? $_SESSION['flash'] : null;
unset($_SESSION['flash']);

require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <span class="breadcrumb-sep">/</span>
            <a href="admin_dashboard.php">Admin Panel</a>
            <span class="breadcrumb-sep">/</span>
            <span>Manage Reports</span>
        </div>
        <h1>Report Management Center</h1>
        <p>Review, process, and track all lost and found reports.</p>
    </div>
    <a href="admin_dashboard.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Panel</a>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?php echo $flash['type']; ?>">
    <i class="fas fa-<?php echo $flash['type']==='success'?'circle-check':'circle-exclamation'; ?>"></i>
    <?php echo htmlspecialchars($flash['msg']); ?>
</div>
<?php endif; ?>

<!-- Verification Queue Section -->
<div class="table-wrap" style="margin-bottom: 40px; border-left: 4px solid var(--primary-light);">
    <div class="table-toolbar">
        <h3 style="font-size: 18px; color: var(--primary-light);"><i class="fas fa-shield-check"></i> Verification Queue (Student Submissions)</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>Date Submitted</th>
                <th>Reporter</th>
                <th>Item & Type</th>
                <th>Category</th>
                <th>Storage / Remarks</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($pending_v->num_rows === 0): ?>
            <tr><td colspan="6" class="table-empty"><i class="fas fa-check-circle"></i>No reports waiting for verification.</td></tr>
        <?php else: while ($row = $pending_v->fetch_assoc()): ?>
            <tr style="vertical-align: top;">
                <td style="font-size: 12px;"><?php echo date('M d, g:i A', strtotime($row['created_at'])); ?></td>
                <td><strong><?php echo htmlspecialchars($row['reporterName']); ?></strong></td>
                <td>
                    <div class="td-bold"><?php echo htmlspecialchars($row['itemName']); ?></div>
                    <span class="badge <?php echo $row['reportType'] === 'Lost' ? 'badge-male' : 'badge-female'; ?>" style="font-size: 10px;">
                        <?php echo $row['reportType']; ?>
                    </span>
                    <div style="font-size: 11px; color: var(--text-muted); max-width: 150px; margin-top: 4px;"><?php echo htmlspecialchars($row['description']); ?></div>
                </td>
                <form method="post">
                    <input type="hidden" name="reportId" value="<?php echo $row['reportId']; ?>">
                    <input type="hidden" name="action_report" value="Verify">
                    <td>
                        <select name="category" style="padding: 4px; border-radius: 4px; font-size: 12px; width: 100%;">
                            <option value="General" <?php echo $row['category']==='General'?'selected':''; ?>>General</option>
                            <option value="Electronics" <?php echo $row['category']==='Electronics'?'selected':''; ?>>Electronics</option>
                            <option value="Documents" <?php echo $row['category']==='Documents'?'selected':''; ?>>Documents</option>
                            <option value="Personal Items" <?php echo $row['category']==='Personal Items'?'selected':''; ?>>Personal Items</option>
                            <option value="Keys/Wallets" <?php echo $row['category']==='Keys/Wallets'?'selected':''; ?>>Keys/Wallets</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="storage_location" placeholder="Storage (e.g. Bin B)" style="padding: 4px; border-radius: 4px; font-size: 11px; width: 100%; margin-bottom: 4px;">
                        <textarea name="admin_remarks" placeholder="Admin notes..." style="padding: 4px; border-radius: 4px; font-size: 11px; width: 100%; height: 40px;"></textarea>
                    </td>
                    <td>
                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <button type="submit" name="newStatus" value="<?php echo $row['reportType']==='Lost'?'Pending':'Found'; ?>" class="btn btn-success btn-sm">Verify & Publish</button>
                            <button type="submit" name="action_report" value="Delete" class="btn btn-danger btn-sm" onclick="return confirm('Reject and delete this report?')">Reject</button>
                        </div>
                    </td>
                </form>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

<!-- Pending Claims Section -->
<div class="table-wrap" style="margin-bottom: 40px;">
    <div class="table-toolbar">
        <h3 style="font-size: 18px; color: var(--warning);"><i class="fas fa-exclamation-triangle"></i> Pending Claims Verification</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Item ID</th>
                <th>Item Name</th>
                <th>Claimant</th>
                <th>Proof / Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($claims->num_rows === 0): ?>
            <tr><td colspan="6" class="table-empty"><i class="fas fa-check-double" style="color: var(--success);"></i>No pending claims to verify.</td></tr>
        <?php else: while ($row = $claims->fetch_assoc()): ?>
            <tr>
                <td style="color:var(--text-muted);"><?php echo date('M d, g:i A', strtotime($row['claimDate'])); ?></td>
                <td class="td-bold">RPT-<?php echo str_pad($row['reportId'], 5, '0', STR_PAD_LEFT); ?></td>
                <td class="td-bold"><?php echo htmlspecialchars($row['itemName']); ?></td>
                <td><?php echo htmlspecialchars($row['claimantName']); ?></td>
                <td style="color:var(--text-soft); font-size: 12px; max-width: 200px; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($row['proofOfOwnership']); ?>">
                    <?php echo htmlspecialchars($row['proofOfOwnership']); ?>
                </td>
                <td>
                    <form method="post" style="display: flex; gap: 8px;">
                        <input type="hidden" name="claimId" value="<?php echo $row['claimId']; ?>">
                        <button type="submit" name="action_claim" value="Approve" class="btn btn-success btn-sm" onclick="return confirm('Approve this claim?')">Approve</button>
                        <button type="submit" name="action_claim" value="Deny" class="btn btn-danger btn-sm" onclick="return confirm('Deny this claim?')">Deny</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

<!-- All Items Section -->
<div class="table-wrap">
    <div class="table-toolbar">
        <h3 style="font-size: 18px;"><i class="fas fa-list"></i> All Reported Items</h3>
        <div class="toolbar-actions" style="display:flex; gap:10px;">
            <a href="manage_reports.php" class="btn <?php echo !$filter_type ? 'btn-primary' : 'btn-outline'; ?> btn-sm">All</a>
            <a href="manage_reports.php?type=Lost" class="btn <?php echo $filter_type === 'Lost' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Lost</a>
            <a href="manage_reports.php?type=Found" class="btn <?php echo $filter_type === 'Found' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Found</a>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Item ID</th>
                <th>Type</th>
                <th>Item Name</th>
                <th>Location</th>
                <th>Status</th>
                <th>Reporter</th>
                <th>Date Logged</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($items->num_rows === 0): ?>
            <tr><td colspan="8" class="table-empty"><i class="fas fa-boxes-slash"></i>No items matching the filter.</td></tr>
        <?php else: while ($row = $items->fetch_assoc()): ?>
            <tr>
                <td class="td-bold">RPT-<?php echo str_pad($row['reportId'], 5, '0', STR_PAD_LEFT); ?></td>
                <td>
                    <span class="badge <?php echo $row['reportType'] === 'Lost' ? 'badge-male' : 'badge-female'; ?>">
                        <?php echo htmlspecialchars($row['reportType']); ?>
                    </span>
                </td>
                <td class="td-bold"><?php echo htmlspecialchars($row['itemName']); ?></td>
                <td><?php echo htmlspecialchars($row['location']); ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="reportId" value="<?php echo $row['reportId']; ?>">
                        <input type="hidden" name="action_report" value="UpdateStatus">
                        <select name="newStatus" onchange="this.form.submit()" style="padding: 4px 8px; border-radius: 6px; font-size: 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main);">
                            <option value="Pending" <?php echo $row['currentStatus']==='Pending'?'selected':''; ?>>Pending</option>
                            <option value="Found" <?php echo $row['currentStatus']==='Found'?'selected':''; ?>>Found</option>
                            <option value="Returned" <?php echo $row['currentStatus']==='Returned'?'selected':''; ?>>Returned</option>
                            <option value="Resolved" <?php echo $row['currentStatus']==='Resolved'?'selected':''; ?>>Resolved</option>
                            <option value="Archived" <?php echo $row['currentStatus']==='Archived'?'selected':''; ?>>Archived</option>
                        </select>
                    </form>
                </td>
                <td style="color:var(--text-muted);"><?php echo htmlspecialchars($row['reporterName']); ?></td>
                <td style="color:var(--text-muted); font-size: 12px;"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('Are you sure you want to delete this report? This will also delete any related claims.')">
                        <input type="hidden" name="reportId" value="<?php echo $row['reportId']; ?>">
                        <button type="submit" name="action_report" value="Delete" class="btn btn-danger btn-sm" title="Delete Report">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
