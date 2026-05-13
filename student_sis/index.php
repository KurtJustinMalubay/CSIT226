<?php
session_start();
include 'connect.php';
$title = 'Home';
require_once 'includes/header.php';

// Get admin count for stats
$count_res = mysqli_query($connection, "SELECT COUNT(*) as total FROM `user` WHERE isAdmin = 1");
$total_admins = $count_res ? mysqli_fetch_assoc($count_res)['total'] : 0;
?>

<section class="hero">
    <div class="hero-badge"><i class="fas fa-star"></i> Admin Management Portal</div>
    <h1>Manage Administrators<br>Efficiently &amp; Securely</h1>
    <p>A modern platform to register, manage, and oversee all your system administrators in one place.</p>
    <div class="hero-actions">
        <?php if (isset($_SESSION['username'])): ?>
            <a href="dashboard.php" class="btn btn-primary btn-lg"><i class="fas fa-table-columns"></i> Go to Dashboard</a>
            <a href="addrecord.php" class="btn btn-outline btn-lg"><i class="fas fa-user-plus"></i> Add Admin</a>
        <?php else: ?>
            <a href="register.php" class="btn btn-primary btn-lg"><i class="fas fa-user-plus"></i> Register Admin</a>
            <a href="login.php" class="btn btn-outline btn-lg"><i class="fas fa-right-to-bracket"></i> Login</a>
        <?php endif; ?>
    </div>
    <div class="hero-stats">
        <div class="stat">
            <div class="stat-number"><?php echo number_format($total_admins); ?></div>
            <div class="stat-label">Total Admins</div>
        </div>
        <div class="stat">
            <div class="stat-number">24/7</div>
            <div class="stat-label">Access</div>
        </div>
    </div>
</section>

<div class="features">
    <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-users-gear"></i></div>
        <h3>Admin Management</h3>
        <p>Register, manage, and maintain comprehensive admin profiles with roles, permissions, and contact information.</p>
    </div>
    <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-shield-halved"></i></div>
        <h3>Secure Access</h3>
        <p>Role-based login system with hashed passwords ensures only authorized admins access the system.</p>
    </div>
    <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-magnifying-glass"></i></div>
        <h3>Quick Search</h3>
        <p>Find any admin record in seconds with the built-in live search and role-based filter functionality.</p>
    </div>
    <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-pen-to-square"></i></div>
        <h3>Full CRUD</h3>
        <p>Create, read, update, and delete admin records with a clean and intuitive interface.</p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
