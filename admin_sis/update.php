<?php
session_start();
// Check if user is logged in and is an admin
if (!isset($_SESSION['uid']) || !$_SESSION['isAdmin']) { 
    header('Location: ../student_sis/login.php'); 
    exit; 
}

include '../student_sis/connect.php';
$title = 'Update Admin/User';

$uid = isset($_GET['uid']) ? $_GET['uid'] : '';
if (!$uid) {
    header('Location: admin_dashboard.php');
    exit;
}

// Fetch user details
$stmt = $connection->prepare("SELECT * FROM user WHERE uId = ?");
$stmt->bind_param("s", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: admin_dashboard.php');
    exit;
}

if (isset($_POST['btnUpdate'])) {
    $fullName = trim($_POST['txtfullname']);
    $email = trim($_POST['txtemail']);
    $uniId = trim($_POST['txtuniversityId']);
    $isAdmin = isset($_POST['isAdmin']) ? 1 : 0;

    $stmt = $connection->prepare("UPDATE user SET fullName = ?, email = ?, universityId = ?, isAdmin = ? WHERE uId = ?");
    $stmt->bind_param("sssis", $fullName, $email, $uniId, $isAdmin, $uid);
    if ($stmt->execute()) {
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'User updated successfully.'];
        header('Location: admin_dashboard.php');
        exit;
    } else {
        $error = 'Failed to update user.';
    }
}

require_once '../student_sis/includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <div class="breadcrumb">
            <a href="admin_dashboard.php">Admin Dashboard</a>
            <span class="breadcrumb-sep">/</span>
            <span>Update User</span>
        </div>
        <h1>Update User Details</h1>
    </div>
</div>

<div class="form-card" style="max-width: 500px;">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="txtfullname" value="<?php echo htmlspecialchars($user['fullName']); ?>" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="txtemail" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        <div class="form-group">
            <label>University ID</label>
            <input type="text" name="txtuniversityId" value="<?php echo htmlspecialchars($user['universityId']); ?>" required>
        </div>
        <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
            <input type="checkbox" name="isAdmin" id="isAdmin" <?php echo $user['isAdmin'] ? 'checked' : ''; ?>>
            <label for="isAdmin">Grant Administrator Privileges</label>
        </div>
        <button type="submit" name="btnUpdate" class="btn btn-primary">Update User</button>
        <a href="admin_dashboard.php" class="btn btn-outline">Cancel</a>
    </form>
</div>

<?php require_once '../student_sis/includes/footer.php'; ?>