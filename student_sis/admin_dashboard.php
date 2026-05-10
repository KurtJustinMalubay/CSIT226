<?php
session_start();
if (!isset($_SESSION['uid']) || !$_SESSION['isAdmin']) { header('Location: login.php'); exit; }
include 'connect.php';
$title = 'Admin Dashboard';

// Handle Claim Actions (Approve/Deny)
if (isset($_POST['action_claim'])) {
    $claimId = intval($_POST['claimId']);
    $action = $_POST['action_claim']; // 'Approve' or 'Deny'
    $adminId = $_SESSION['uid'];
    
    // Get claim and report details
    $cStmt = $connection->prepare("SELECT reportId FROM Claim_Request WHERE claimId = ?");
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
                $connection->query("UPDATE Claim_Request SET claimStatus='Approved', approveAdminId='$adminId' WHERE claimId=$claimId");
                
                // Get old status of report
                $oldStatusRes = $connection->query("SELECT currentStatus FROM Item_Report WHERE reportId=$reportId");
                $oldStatus = $oldStatusRes->fetch_assoc()['currentStatus'];
                
                // Update report status
                $connection->query("UPDATE Item_Report SET currentStatus='Returned' WHERE reportId=$reportId");
                
                // Insert Audit Log
                $connection->query("INSERT INTO Audit_Log (reportId, adminId, oldStatus, newStatus) VALUES ($reportId, '$adminId', '$oldStatus', 'Returned')");
                
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Claim Approved and Item marked as Returned.'];
            } elseif ($action === 'Deny') {
                $connection->query("UPDATE Claim_Request SET claimStatus='Denied', approveAdminId='$adminId' WHERE claimId=$claimId");
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

// Stats
$total_reports = mysqli_fetch_assoc(mysqli_query($connection,"SELECT COUNT(*) as c FROM Item_Report"))['c'] ?? 0;
$pending_claims = mysqli_fetch_assoc(mysqli_query($connection,"SELECT COUNT(*) as c FROM Claim_Request WHERE claimStatus='Pending'"))['c'] ?? 0;
$resolved_cases = mysqli_fetch_assoc(mysqli_query($connection,"SELECT COUNT(*) as c FROM Item_Report WHERE currentStatus IN ('Returned', 'Resolved')"))['c'] ?? 0;

// Fetch Pending Claims
$claims_q = "SELECT c.*, i.itemName, u.fullName as claimantName 
             FROM Claim_Request c 
             JOIN Item_Report i ON c.reportId = i.reportId 
             JOIN User u ON c.claimantId = u.uId 
             WHERE c.claimStatus = 'Pending' 
             ORDER BY c.claimDate ASC";
$claims = mysqli_query($connection, $claims_q);

// Fetch All Items
$items_q = "SELECT i.*, u.fullName as reporterName 
            FROM Item_Report i 
            JOIN User u ON i.reporterId = u.uId 
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
            <span>Admin Dashboard</span>
        </div>
        <h1>Lost & Found Administration</h1>
        <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['fullName']); ?></strong></p>
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

<!-- Pending Claims Section -->
<div class="table-wrap" style="margin-bottom: 40px;">
    <div class="table-toolbar">
        <h3 style="font-size: 18px; color: var(--warning);"><i class="fas fa-exclamation-triangle"></i> Action Required: Pending Claims</h3>
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
            <tr><td colspan="6" class="table-empty"><i class="fas fa-check-double" style="color: var(--success);"></i>All claims are processed.</td></tr>
        <?php else: while ($row = $claims->fetch_assoc()): ?>
            <tr>
                <td style="color:var(--text-muted);"><?php echo date('M d, g:i A', strtotime($row['claimDate'])); ?></td>
                <td class="td-bold">RPT-<?php echo str_pad($row['reportId'], 5, '0', STR_PAD_LEFT); ?></td>
                <td class="td-bold"><?php echo htmlspecialchars($row['itemName']); ?></td>
                <td><?php echo htmlspecialchars($row['claimantName']); ?></td>
                <td style="color:var(--text-soft); font-size: 12px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($row['proofOfOwnership']); ?>">
                    <?php echo htmlspecialchars($row['proofOfOwnership']); ?>
                </td>
                <td>
                    <form method="post" style="display: flex; gap: 8px;">
                        <input type="hidden" name="claimId" value="<?php echo $row['claimId']; ?>">
                        <button type="submit" name="action_claim" value="Approve" class="btn btn-success btn-sm" onclick="return confirm('Approve this claim and mark item as Returned?')">Approve</button>
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
            </tr>
        </thead>
        <tbody>
        <?php if ($items->num_rows === 0): ?>
            <tr><td colspan="7" class="table-empty"><i class="fas fa-boxes-slash"></i>No items reported yet.</td></tr>
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
                    <span class="badge badge-program">
                        <?php echo htmlspecialchars($row['currentStatus']); ?>
                    </span>
                </td>
                <td style="color:var(--text-muted);"><?php echo htmlspecialchars($row['reporterName']); ?></td>
                <td style="color:var(--text-muted);"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
