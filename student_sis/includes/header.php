<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Student Information System - Manage student records efficiently">
    <title>SIS &mdash; <?php echo isset($title) ? htmlspecialchars($title) : 'Student Information System'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/site.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-brand">
            <div class="nav-logo">
                <i class="fas fa-search"></i>
            </div>
            <span class="nav-title">CIT-U<span class="nav-subtitle">Lost & Found</span></span>
        </a>
        <div class="nav-links">
            <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                Home
            </a>
            <?php if (isset($_SESSION['uid'])): ?>
            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                Dashboard
            </a>
            <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']): ?>
            <a href="admin_dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                Admin Panel
            </a>
            <?php endif; ?>
            <a href="dashboard.php#recent" class="nav-link">
                Recent Items
            </a>
            <div class="nav-user">
                <i class="fas fa-circle-user"></i>
                <span>Hello, <?php echo htmlspecialchars($_SESSION['fullName'] ?? $_SESSION['username'] ?? 'Student'); ?>!</span>
            </div>
            <a href="logout.php" class="nav-btn nav-btn-outline">
                <i class="fas fa-right-from-bracket"></i> Log Out
            </a>
            <?php else: ?>
            <a href="login.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>">
                Login
            </a>
            <a href="register.php" class="nav-btn">
                Sign Up
            </a>
            <?php endif; ?>
        </div>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>
    </div>
    <div class="nav-mobile" id="navMobile">
        <a href="index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
        <?php if (isset($_SESSION['uid'])): ?>
        <a href="dashboard.php" class="nav-link"><i class="fas fa-table-columns"></i> Dashboard</a>
        <?php if ($_SESSION['isAdmin']): ?>
        <a href="admin_dashboard.php" class="nav-link"><i class="fas fa-user-shield"></i> Admin Panel</a>
        <?php endif; ?>
        <a href="addrecord.php" class="nav-link"><i class="fas fa-user-plus"></i> Add Student</a>
        <a href="logout.php" class="nav-link"><i class="fas fa-right-from-bracket"></i> Logout</a>
        <?php else: ?>
        <a href="login.php" class="nav-link"><i class="fas fa-right-to-bracket"></i> Login</a>
        <a href="register.php" class="nav-link"><i class="fas fa-user-plus"></i> Register</a>
        <?php endif; ?>
    </div>
</nav>

<main class="main-content">
