<?php
/**
 * Student Registration Handler (Admin Only)
 * 
 * This script allows administrators to manually register a new student.
 * It performs a dual-table insertion (User and Student) within a transaction 
 * to ensure data integrity.
 */

session_start();

// Security check: Only logged-in users (admins) should access this
if (!isset($_SESSION['username'])) { 
    header('Location: login.php'); 
    exit; 
}

include 'connect.php';
$title = 'Add Student';
$error = '';

/**
 * Handle Form Submission
 */
if (isset($_POST['btnAdd'])) {
    // Collect and sanitize input
    $idnum   = trim($_POST['txtidnumber']);
    $fname   = trim($_POST['txtfirstname']);
    $lname   = trim($_POST['txtlastname']);
    $contact = trim($_POST['txtcontact']);
    $dob     = $_POST['txtdob'];

    // Basic validation: ensure required fields are present
    if (empty($idnum) || empty($fname) || empty($lname)) {
        $error = 'ID Number, First Name, and Last Name are required.';
    } else {
        /**
         * Dual-Table Insertion Logic
         * 
         * Every student record must have a corresponding entry in the 'user' table 
         * for authentication purposes.
         */
        $uId = 'user_' . uniqid(); // Generate a unique identifier for the user
        $fullName = $fname . ' ' . $lname;
        $username = strtolower($fname . $idnum); // Default username format: first name + ID
        $password = password_hash('password123', PASSWORD_DEFAULT); // Default secure password
        
        $connection->begin_transaction();
        try {
            // 1. Create the User account
            $stmt1 = $connection->prepare("INSERT INTO user (uId, username, fullName, email, password, universityId, isStudent) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $email = strtolower($fname . '.' . $lname . '@example.com');
            $stmt1->bind_param("ssssss", $uId, $username, $fullName, $email, $password, $idnum);
            $stmt1->execute();

            // 2. Create the specific Student profile
            $stmt2 = $connection->prepare("INSERT INTO student (studId, course, contactNo, dob) VALUES (?, 'N/A', ?, ?)");
            $stmt2->bind_param("sss", $uId, $idnum, $contact, $dob); 
            $stmt2->execute();

            $connection->commit();
            $_SESSION['flash'] = ['type'=>'success','msg'=>"Student $fullName added successfully."];
            header('Location: dashboard.php'); 
            exit;
        } catch (Exception $e) {
            // Rollback changes if any part of the transaction fails
            $connection->rollback();
            $error = 'Failed to save record: ' . $e->getMessage();
        }
    }
}
require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <div class="breadcrumb">
            <a href="index.php">Home</a><span class="breadcrumb-sep">/</span>
            <a href="dashboard.php">Dashboard</a><span class="breadcrumb-sep">/</span>
            <span>Add Student</span>
        </div>
        <h1>Add New Student</h1>
        <p>Fill in the form below to register a new student record.</p>
    </div>
    <a href="dashboard.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="form-card form-card-wide" style="max-width:700px;margin:0;">
    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" id="addForm" novalidate>
        <div class="form-row">
            <div class="form-group">
                <label for="txtfirstname">First Name *</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-user"></i>
                    <input type="text" id="txtfirstname" name="txtfirstname" class="has-icon"
                           placeholder="Juan"
                           value="<?php echo isset($_POST['txtfirstname'])?htmlspecialchars($_POST['txtfirstname']):''; ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="txtlastname">Last Name *</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-user"></i>
                    <input type="text" id="txtlastname" name="txtlastname" class="has-icon"
                           placeholder="Dela Cruz"
                           value="<?php echo isset($_POST['txtlastname'])?htmlspecialchars($_POST['txtlastname']):''; ?>" required>
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="txtidnumber">ID Number *</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-id-card"></i>
                    <input type="text" id="txtidnumber" name="txtidnumber" class="has-icon"
                           placeholder="e.g. 2024-00001"
                           value="<?php echo isset($_POST['txtidnumber'])?htmlspecialchars($_POST['txtidnumber']):''; ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="txtdob">Date of Birth</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-calendar"></i>
                    <input type="date" id="txtdob" name="txtdob" class="has-icon"
                           value="<?php echo isset($_POST['txtdob'])?htmlspecialchars($_POST['txtdob']):''; ?>">
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="txtcontact">Contact Number</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-phone"></i>
                    <input type="text" id="txtcontact" name="txtcontact" class="has-icon"
                           placeholder="09XXXXXXXXX"
                           value="<?php echo isset($_POST['txtcontact'])?htmlspecialchars($_POST['txtcontact']):''; ?>">
                </div>
            </div>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" name="btnAdd" class="btn btn-primary" style="flex:1;">
                <i class="fas fa-user-plus"></i> Save Student
            </button>
            <a href="dashboard.php" class="btn btn-outline" style="flex:0 0 auto;">Cancel</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
