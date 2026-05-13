<?php
session_start();
if (!isset($_SESSION['uid'])) { header('Location: login.php'); exit; }
include 'connect.php';

$reportId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($reportId <= 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Invalid Report ID.'];
    header('Location: dashboard.php');
    exit;
}

// Fetch item details
$stmt = $connection->prepare("SELECT * FROM item_report WHERE reportId = ?");
$stmt->bind_param("i", $reportId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Item not found.'];
    header('Location: dashboard.php');
    exit;
}
$item = $result->fetch_assoc();

if ($item['currentStatus'] !== 'Pending' && $item['currentStatus'] !== 'Found') {
    $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'This item is no longer available for claiming.'];
    header('Location: dashboard.php');
    exit;
}

// Process claim
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submitClaim'])) {
    $proof = trim($_POST['proof']);
    // In a real scenario, we'd handle file upload here. For now, we'll just store the text proof.
    if (empty($proof)) {
        $error = 'Please provide a detailed description or serial number.';
    } else {
        // Insert into claim_request
        $claimantId = $_SESSION['uid'];
        $insertStmt = $connection->prepare("INSERT INTO claim_request (reportId, claimantId, proofOfOwnership, claimStatus) VALUES (?, ?, ?, 'Pending')");
        $insertStmt->bind_param("iss", $reportId, $claimantId, $proof);
        
        if ($insertStmt->execute()) {
            $success = 'Your claim request has been submitted successfully. Please wait for an administrator to review it.';
        } else {
            $error = 'Failed to submit claim. Please try again.';
        }
    }
}

$title = 'File a Claim';
require_once 'includes/header.php';
?>

<div class="form-card" style="max-width: 560px; margin-top: 40px;">
    <div class="form-header" style="text-align: left; margin-bottom: 24px;">
        <h1 style="font-size: 24px; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-clipboard-check" style="color: var(--accent);"></i> File a Claim
        </h1>
        <p style="margin-top: 8px; font-size: 14px; line-height: 1.6;">
            To claim an item, you must provide valid proof of ownership (Rule #6). False claims may result in disciplinary action.
        </p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-circle-check"></i>
        <div>
            <strong>Success!</strong><br>
            <?php echo htmlspecialchars($success); ?><br><br>
            <a href="dashboard.php" class="btn btn-primary btn-sm" style="display: inline-flex;">Return to Dashboard</a>
        </div>
    </div>
    <?php else: ?>

    <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: var(--radius); padding: 16px; display: flex; gap: 12px; margin-bottom: 24px;">
        <i class="fas fa-circle-info" style="color: #60A5FA; margin-top: 2px;"></i>
        <div>
            <div style="font-size: 14px; color: var(--text-soft);">You are claiming: <strong style="color: var(--text);"><?php echo htmlspecialchars($item['itemName']); ?></strong></div>
            <div style="font-size: 12px; color: #60A5FA; margin-top: 2px;">Report ID: RPT-<?php echo str_pad($item['reportId'], 5, '0', STR_PAD_LEFT); ?></div>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="proof">Detailed Description or Serial Number</label>
            <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 8px;">Describe distinct features, contents, or provide a serial/IMEI number.</p>
            <textarea id="proof" name="proof" rows="4" placeholder="e.g. The wallet contains my CIT-U Student ID and a 500 peso bill..." required><?php echo isset($_POST['proof']) ? htmlspecialchars($_POST['proof']) : ''; ?></textarea>
        </div>

        <div class="form-group">
            <label>Upload Photographic Evidence (Optional)</label>
            <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 8px;">Upload a previous photo of you with the item or a receipt of purchase.</p>
            <div style="border: 2px dashed var(--border); border-radius: var(--radius); padding: 32px; text-align: center; background: var(--bg3); cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor='var(--primary-light)'" onmouseout="this.style.borderColor='var(--border)'">
                <i class="fas fa-cloud-arrow-up" style="font-size: 32px; color: var(--primary-light); margin-bottom: 12px;"></i>
                <div style="font-size: 14px;"><strong>Upload a file</strong> or drag and drop</div>
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">PNG, JPG up to 5MB</div>
                <input type="file" name="photo" style="display: none;">
            </div>
        </div>

        <button type="submit" name="submitClaim" class="btn btn-primary" style="margin-top: 16px; padding: 14px;">
            Submit Claim Request <i class="fas fa-paper-plane"></i>
        </button>
        
        <div style="text-align: center; margin-top: 16px; font-size: 12px; color: var(--text-muted);">
            Claims are subject to Administrator review (Rule #5).
        </div>
    </form>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
