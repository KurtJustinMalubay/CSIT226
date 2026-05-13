<?php
session_start();
if (isset($_SESSION['username'])) { header('Location: dashboard.php'); exit; }
include 'connect.php';
$title = 'Admin Login';
$error = '';

if (isset($_POST['btnLogin'])) {
    $uniId = trim($_POST['txtusername']);
    $pwd   = $_POST['txtpassword'];

    if (empty($uniId) || empty($pwd)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $connection->prepare("SELECT * FROM `user` WHERE universityId = ? AND isAdmin = 1");
        $stmt->bind_param("s", $uniId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = 'University ID not found or not an admin. Please check and try again.';
        } else {
            $row = $result->fetch_assoc();
            if (!password_verify($pwd, $row['password'])) {
                $error = 'Incorrect password. Please try again.';
            } else {
                $_SESSION['username'] = $row['universityId'];
                header('Location: dashboard.php');
                exit;
            }
        }
        $stmt->close();
    }
}
require_once 'includes/header.php';
?>

<div class="form-card">
    <div class="form-header">
        <div class="form-icon"><i class="fas fa-right-to-bracket"></i></div>
        <h1>Admin Login</h1>
        <p>Sign in to your admin account to access the portal</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" id="loginForm" novalidate>
        <div class="form-group">
            <label for="txtusername"><i class="fas fa-id-badge"></i> University ID</label>
            <div class="input-wrap">
                <i class="input-icon fas fa-id-badge"></i>
                <input type="text" id="txtusername" name="txtusername" class="has-icon"
                       placeholder="Enter your university ID"
                       value="<?php echo isset($_POST['txtusername']) ? htmlspecialchars($_POST['txtusername']) : ''; ?>"
                       required autocomplete="username">
            </div>
            <div class="form-error" id="err_username"></div>
        </div>

        <div class="form-group">
            <label for="txtpassword"><i class="fas fa-lock"></i> Password</label>
            <div class="input-wrap">
                <i class="input-icon fas fa-lock"></i>
                <input type="password" id="txtpassword" name="txtpassword" class="has-icon"
                       placeholder="Enter your password" required autocomplete="current-password">
            </div>
            <div class="form-error" id="err_password"></div>
        </div>

        <button type="submit" name="btnLogin" class="btn btn-primary" id="btnLogin">
            <i class="fas fa-right-to-bracket"></i> Sign In
        </button>
    </form>

    <div class="form-footer">
        Don't have an account? <a href="register.php">Register here</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
