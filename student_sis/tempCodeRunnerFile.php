<?php
session_start();
include 'connect.php';
$title = 'Home';
require_once 'includes/header.php';

// Get student count for stats
$count_res = mysqli_query($connection, "SELECT COUNT(*) AS total FROM `student`");
if (!$count_res) {
    die('Database error: ' . mysqli_error($connection));
}
$total_students = mysqli_fetch_assoc($count_res)['total'] ?? 0;
$prog_res = mysqli_query($connection, "SELECT COUNT(DISTINCT `program`) AS total FROM `user`");
if (!$prog_res) {
    die('Database error: ' . mysqli_error($connection));
}
$total_programs = mysqli_fetch_assoc($prog_res)['total'] ?? 0;
?>

<section class="hero">
    <div class="hero-badge"><i class="fas fa-star"></i> Student Information System</div>
    <h1>Manage Student Records<br>Efficiently &amp; Securely</h1>
    <p>A modern platform to register, track, and manage all your student information in one place.</p>
    <div class="hero-actions">
        <?php if (isset($_SESSION['username'])): ?>
            <a href="dashboard.php" class="btn btn-primary btn-lg"><i class="fas fa-table-columns"></i> Go to Dashboard</a>
            <a href="addrecord.php" class="btn btn-outline btn-lg"><i class="fas fa-user-plus"></i> Add Student</a>
        <?php else: ?>
            <a href="register.php" class="btn btn-primary btn-lg"><i class="fas fa-user-plus"></i> Get Started</a>
            <a href="login.php" class="btn btn-outline btn-lg"><i class="fas fa-right-to-bracket"></i> Login</a>
        <?php endif; ?>
    </div>
    <div class="hero-stats">
        <div class="stat">
            <div class="stat-number"><?php echo number_format($total_students); ?></div>
            <div class="stat-label">Total Students</div>
        </div>
        <div class="stat">
            <div class="stat-number"><?php echo $total_programs ?: '—'; ?></div>
            <div class="stat-label">Programs</div>
        </div>
        <div class="stat">
            <div class="stat-number">24/7</div>
            <div class="stat-label">Access</div>
        </div>
    </div>
</section>

<div class="features">
    <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-users"></i></div>
        <h3>Student Records</h3>
        <p>Store and manage comprehensive student profiles including personal info, program, and contact details.</p>
    </div>
    <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-shield-halved"></i></div>
        <h3>Secure Access</h3>
        <p>Role-based login system with hashed passwords ensures only authorized users access the system.</p>
    </div>
    <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-magnifying-glass"></i></div>
        <h3>Quick Search</h3>
        <p>Find any student record in seconds with the built-in live search and filter functionality.</p>
    </div>
    <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-pen-to-square"></i></div>
        <h3>Full CRUD</h3>
        <p>Create, read, update, and delete student records with a clean and intuitive interface.</p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>