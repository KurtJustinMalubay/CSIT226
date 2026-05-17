<?php
session_start();
if (isset($_SESSION['username'])) { header('Location: dashboard.php'); exit; }
include 'connect.php';
$title = 'Login';
$error = '';

if (isset($_POST['btnLogin'])) {
    $uname = trim($_POST['txtusername']);
    $pwd   = $_POST['txtpassword'];

    if (empty($uname) || empty($pwd)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $connection->prepare("SELECT * FROM `user` WHERE username = ?");
        $stmt->bind_param("s", $uname);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = 'User not found. Please check and try again.';
        } else {
            $row = $result->fetch_assoc();
            if (!password_verify($pwd, $row['password'])) {
                $error = 'Incorrect password. Please try again.';
            } else {
                $_SESSION['uid'] = $row['uId'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['fullName'] = $row['fullName'];
                $_SESSION['isAdmin'] = $row['isAdmin'];
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
        <h1>Welcome Back</h1>
        <p>Sign in to your account to continue</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" id="loginForm" novalidate>
        <div class="form-group">
            <label for="txtusername"><i class="fas fa-user"></i> School ID</label>
            <div class="input-wrap">
                <i class="input-icon fas fa-user"></i>
                <input type="text" id="txtusername" name="txtusername" class="has-icon"
                       placeholder="Enter your student ID"
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
