<?php
session_start();
if (isset($_SESSION['username'])) { header('Location: dashboard.php'); exit; }
include 'connect.php';
$title = 'Register';
$error = '';
$success = '';

if (isset($_POST['btnRegister'])) {
    $fname  = trim($_POST['txtfirstname']);
    $lname  = trim($_POST['txtlastname']);
    $email  = trim($_POST['txtemail']);
    $uname  = trim($_POST['txtusername']);
    $pword  = $_POST['txtpassword'];
    $cpword = $_POST['txtconfirmpassword'];

    if (empty($fname)||empty($lname)||empty($email)||empty($uname)||empty($pword)) {
        $error = 'All fields are required.';
    } elseif ($pword !== $cpword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($pword) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check username uniqueness
        $chk = $connection->prepare("SELECT uid FROM `user` WHERE username = ?");
        $chk->bind_param("s", $uname);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $error = 'Student ID already exists. Please choose another.';
        } else {
            $hashed = password_hash($pword, PASSWORD_DEFAULT);
            $fullName = trim($fname . ' ' . $lname);
            $isAdmin = 0;
            $isStudent = 1;
            $isFaculty = 0;

            $s = $connection->prepare("INSERT INTO `user` (fullName, email, universityId, username, password, isAdmin, isStudent, isFaculty) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $s->bind_param("sssssiii", $fullName, $email, $uname, $uname, $hashed, $isAdmin, $isStudent, $isFaculty);
            if ($s->execute()) {
                $success = 'Account created successfully! You can now log in.';
            } else {
                $error = 'Registration failed: ' . $connection->error;
            }
            $s->close();
        }
        $chk->close();
    }
}
require_once 'includes/header.php';
?>

<div class="form-card form-card-wide">
    <div class="form-header">
        <div class="form-icon"><i class="fas fa-user-plus"></i></div>
        <h1>Create Account</h1>
        <p>Fill in your details to register a new account</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-circle-check"></i><?php echo htmlspecialchars($success); ?>
        <a href="login.php" style="margin-left:8px;color:inherit;font-weight:600;">Login &rarr;</a>
    </div>
    <?php endif; ?>

    <form method="post" id="registerForm" novalidate>
        <div class="form-row">
            <div class="form-group">
                <label for="txtfirstname">First Name</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-user"></i>
                    <input type="text" id="txtfirstname" name="txtfirstname" class="has-icon"
                           placeholder="Juan" value="<?php echo isset($_POST['txtfirstname']) ? htmlspecialchars($_POST['txtfirstname']) : ''; ?>" required>
                </div>
                <div class="form-error" id="err_fname"></div>
            </div>
            <div class="form-group">
                <label for="txtlastname">Last Name</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-user"></i>
                    <input type="text" id="txtlastname" name="txtlastname" class="has-icon"
                           placeholder="Dela Cruz" value="<?php echo isset($_POST['txtlastname']) ? htmlspecialchars($_POST['txtlastname']) : ''; ?>" required>
                </div>
                <div class="form-error" id="err_lname"></div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="txtemail">Email Address</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-envelope"></i>
                    <input type="email" id="txtemail" name="txtemail" class="has-icon"
                           placeholder="you@email.com" value="<?php echo isset($_POST['txtemail']) ? htmlspecialchars($_POST['txtemail']) : ''; ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="txtusername">Student ID</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-at"></i>
                    <input type="text" id="txtusername" name="txtusername" class="has-icon"
                           placeholder="Enter your student ID" value="<?php echo isset($_POST['txtusername']) ? htmlspecialchars($_POST['txtusername']) : ''; ?>" required>
                </div>
                <div class="form-error" id="err_uname"></div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="txtpassword">Password</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-lock"></i>
                    <input type="password" id="txtpassword" name="txtpassword" class="has-icon"
                           placeholder="Min. 6 characters" required>
                </div>
                <div class="form-error" id="err_pwd"></div>
            </div>
            <div class="form-group">
                <label for="txtconfirmpassword">Confirm Password</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-lock"></i>
                    <input type="password" id="txtconfirmpassword" name="txtconfirmpassword" class="has-icon"
                           placeholder="Repeat password" required>
                </div>
                <div class="form-error" id="err_cpwd"></div>
            </div>
        </div>

        <button type="submit" name="btnRegister" class="btn btn-primary" id="btnRegister">
            <i class="fas fa-user-plus"></i> Create Account
        </button>
    </form>

    <div class="form-footer">
        Already have an account? <a href="login.php">Sign in here</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
