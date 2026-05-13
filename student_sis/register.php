<?php
session_start();
if (isset($_SESSION['username'])) { header('Location: dashboard.php'); exit; }
include 'connect.php';
$title = 'Admin Portal';
$error = '';
$success = '';

if (isset($_POST['btnRegister'])) {
    $fname    = trim($_POST['txtfirstname']);
    $lname    = trim($_POST['txtlastname']);
    $email    = trim($_POST['txtemail']);
    $uniId    = trim($_POST['txtusername']);
    $contact  = trim($_POST['txtcontactNum']);
    $password = $_POST['txtpassword'];
    $cpword   = $_POST['txtconfirmpassword'];

    if (empty($fname) || empty($lname) || empty($email) || empty($uniId) || empty($contact) || empty($password)) {
        $error = 'All fields are required.';
    } elseif ($password !== $cpword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $chk = $connection->prepare("SELECT uid FROM `user` WHERE universityId = ?");
        $chk->bind_param("s", $uniId);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $error = 'University ID already exists. Please choose another.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $isAdmin = 1;
            $isStudent = 0;
            $role = 'Super Admin';

            $connection->begin_transaction();
            $s = $connection->prepare("INSERT INTO `user` (fname, lname, email, contactNum, universityId, isAdmin, isStudent, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $s->bind_param("sssssiis", $fname, $lname, $email, $contact, $uniId, $isAdmin, $isStudent, $hashed);

            if ($s->execute()) {
                $new_id = $connection->insert_id;
                $s->close();

                if ($new_id > 0) {
                    $s2 = $connection->prepare("INSERT INTO admin_staff (adId, adminRole) VALUES (?, ?) ON DUPLICATE KEY UPDATE adminRole = VALUES(adminRole)");
                    $s2->bind_param("is", $new_id, $role);

                    if ($s2->execute()) {
                        $connection->commit();
                        $success = 'Admin account created successfully! You can now log in.';
                    } else {
                        $connection->rollback();
                        $error = 'Admin staff registration failed: ' . $connection->error;
                    }
                    $s2->close();
                } else {
                    $connection->rollback();
                    $error = 'Unable to determine the new admin user ID.';
                }
            } else {
                $connection->rollback();
                $error = 'Registration failed: ' . $connection->error;
                $s->close();
            }
        }

        $chk->close();
    }
}
require_once 'includes/header.php';
?>

<div class="form-card form-card-wide">
    <div class="form-header">
        <div class="form-icon"><i class="fas fa-user-plus"></i></div>
        <h1>Create Admin Account</h1>
        <p>Fill in the details to register a new admin user</p>
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
                <label for="txtusername">University ID</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-id-badge"></i>
                    <input type="text" id="txtusername" name="txtusername" class="has-icon"
                           placeholder="Enter your university ID" value="<?php echo isset($_POST['txtusername']) ? htmlspecialchars($_POST['txtusername']) : ''; ?>" required>
                </div>
                <div class="form-error" id="err_uname"></div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="txtcontactNum">Contact Number</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-phone"></i>
                    <input type="text" id="txtcontactNum" name="txtcontactNum" class="has-icon"
                           placeholder="09XXXXXXXXX" value="<?php echo isset($_POST['txtcontactNum']) ? htmlspecialchars($_POST['txtcontactNum']) : ''; ?>" required>
                </div>
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
