<?php
session_start();
if (!isset($_SESSION['uid'])) { header('Location: login.php'); exit; }
include 'connect.php';
$title = 'Dashboard';

// Stats for Campus Overview
$total_lost = mysqli_fetch_assoc(mysqli_query($connection,"SELECT COUNT(*) as c FROM item_report WHERE reportType='Lost'"))['c'] ?? 0;
$total_found = mysqli_fetch_assoc(mysqli_query($connection,"SELECT COUNT(*) as c FROM item_report WHERE reportType='Found'"))['c'] ?? 0;
$successful_claims = mysqli_fetch_assoc(mysqli_query($connection,"SELECT COUNT(*) as c FROM claim_request WHERE claimStatus='Approved'"))['c'] ?? 0;
$pending_actions = mysqli_fetch_assoc(mysqli_query($connection,"SELECT COUNT(*) as c FROM claim_request WHERE claimStatus='Pending'"))['c'] ?? 0;

// Recent Items
$recent_items = mysqli_query($connection, "SELECT * FROM item_report ORDER BY reportId DESC LIMIT 6");

require_once 'includes/header.php';
?>

<div class="hero" style="background: var(--primary); color: white; border-radius: 0 0 20px 20px; padding: 60px 20px; margin-top: -40px;">
    <h1 style="font-size: 3rem; margin-bottom: 20px; color: white;">Lost it? <span style="color: var(--accent);">Found it?</span> Return it.</h1>
    <p style="color: rgba(255,255,255,0.8); max-width: 600px; margin: 0 auto 30px; font-size: 16px;">
        The official centralized platform for reporting and claiming lost items on the CIT-University campus. Secure, fast, and exclusive to Wildcats.
    </p>
    <div class="hero-actions" style="display: flex; gap: 16px; justify-content: center;">
        <button onclick="openModal('reportModal')" class="btn btn-accent btn-lg" style="color: #111;">
            <i class="fas fa-circle-exclamation"></i> Report Lost Item
        </button>
        <button onclick="openModal('foundModal')" class="btn btn-light btn-lg" style="background: white; color: var(--primary-dark); border: none; padding: 14px 32px; border-radius: 12px; font-weight: 600; cursor: pointer;">
            <i class="fas fa-hand-holding-heart"></i> I Found an Item
        </button>
    </div>
</div>

<div class="main-content">
    <div class="text-center" style="margin-top: 20px;">
        <h2 style="color: var(--primary-light); font-weight: 800; font-size: 24px;">Campus Overview</h2>
        <p style="color: var(--text-muted); font-size: 14px;">Real-time statistics of our lost and found records.</p>
    </div>

    <!-- Stats row -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-top:30px;">
        <div class="card" style="text-align: center; padding: 30px;">
            <div style="font-size: 32px; margin-bottom: 10px;">📦❓</div>
            <div style="font-size:32px;font-weight:800;"><?php echo $total_lost; ?></div>
            <div style="font-size:12px;color:var(--text-muted);font-weight:700;text-transform:uppercase;">Total Lost</div>
        </div>
        <div class="card" style="text-align: center; padding: 30px;">
            <div style="font-size: 32px; margin-bottom: 10px; color: var(--success);"><i class="fas fa-search"></i></div>
            <div style="font-size:32px;font-weight:800;"><?php echo $total_found; ?></div>
            <div style="font-size:12px;color:var(--text-muted);font-weight:700;text-transform:uppercase;">Total Found</div>
        </div>
        <div class="card" style="text-align: center; padding: 30px;">
            <div style="font-size: 32px; margin-bottom: 10px; color: var(--primary-light);">124</div>
            <div style="font-size:32px;font-weight:800;"><?php echo $successful_claims; ?></div>
            <div style="font-size:12px;color:var(--text-muted);font-weight:700;text-transform:uppercase;">Successful Claims</div>
        </div>
        <div class="card" style="text-align: center; padding: 30px;">
            <div style="font-size: 32px; margin-bottom: 10px; color: var(--text-muted);"><i class="far fa-clock"></i></div>
            <div style="font-size:32px;font-weight:800;"><?php echo $pending_actions; ?></div>
            <div style="font-size:12px;color:var(--text-muted);font-weight:700;text-transform:uppercase;">Pending Actions</div>
        </div>
    </div>

    <div class="section-header" id="recent">
        <h2>Recently Reported</h2>
        <a href="#">View All</a>
    </div>

    <div class="item-grid">
        <?php while($item = mysqli_fetch_assoc($recent_items)): ?>
        <div class="item-card">
            <div class="item-img">
                <i class="far fa-image"></i>
            </div>
            <div class="item-content">
                <div class="item-title"><?php echo htmlspecialchars($item['itemName']); ?></div>
                <div class="item-meta">
                    <span><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($item['location']); ?></span>
                    <span><i class="far fa-calendar"></i> Reported <?php echo date('M d, Y', strtotime($item['created_at'])); ?></span>
                </div>
                <div class="mt-auto">
                    <?php if ($item['currentStatus'] === 'Pending' || $item['currentStatus'] === 'Found'): ?>
                    <a href="claim.php?id=<?php echo $item['reportId']; ?>" class="btn btn-outline" style="width: 100%; border-color: var(--primary-light); color: var(--primary-light);">Claim Item</a>
                    <?php else: ?>
                    <button class="btn btn-outline" style="width: 100%;" disabled><?php echo htmlspecialchars($item['currentStatus']); ?></button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Modals -->
<div id="reportModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('reportModal')">&times;</span>
        <div class="modal-header">
            <i class="fas fa-exclamation-circle" style="color: var(--warning);"></i>
            <div class="modal-title">Report a Lost Item</div>
        </div>
        <div class="modal-body">
            <form id="lostForm" onsubmit="event.preventDefault(); submitForm('lostForm');">
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" placeholder="What did you lose?" required>
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" placeholder="Where did you last see it?" required>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Proceed</button>
            </form>
            <div id="lostFormSuccess" style="display:none; text-align: center; padding: 20px 0;">
                <i class="fas fa-check-circle" style="font-size: 48px; color: var(--success); margin-bottom: 16px;"></i>
                <h3 style="margin-bottom: 8px;">Form Submitted</h3>
                <p>Please proceed to the <strong>Student Affairs Office (SAO)</strong> to formalize your report and verify your identity.</p>
            </div>
        </div>
    </div>
</div>

<div id="foundModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('foundModal')">&times;</span>
        <div class="modal-header">
            <i class="fas fa-hand-holding-heart" style="color: var(--success);"></i>
            <div class="modal-title">Turn in a Found Item</div>
        </div>
        <div class="modal-body">
            <form id="foundForm" onsubmit="event.preventDefault(); submitForm('foundForm');">
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" placeholder="What did you find?" required>
                </div>
                <div class="form-group">
                    <label>Location Found</label>
                    <input type="text" placeholder="Where did you find it?" required>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Proceed</button>
            </form>
            <div id="foundFormSuccess" style="display:none; text-align: center; padding: 20px 0;">
                <i class="fas fa-check-circle" style="font-size: 48px; color: var(--success); margin-bottom: 16px;"></i>
                <h3 style="margin-bottom: 8px;">Thank You!</h3>
                <p>Please bring the found item to the <strong>Student Affairs Office (SAO)</strong> for safe keeping. Our admins will log it into the system.</p>
            </div>
        </div>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('show');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
    // reset form
    const form = document.getElementById(id.replace('Modal', 'Form'));
    const success = document.getElementById(id.replace('Modal', 'FormSuccess'));
    if(form) form.style.display = 'block';
    if(success) success.style.display = 'none';
    if(form) form.reset();
}
function submitForm(formId) {
    document.getElementById(formId).style.display = 'none';
    document.getElementById(formId + 'Success').style.display = 'block';
}
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        closeModal(event.target.id);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
