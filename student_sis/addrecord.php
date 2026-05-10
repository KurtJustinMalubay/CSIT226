<?php
session_start();
if (!isset($_SESSION['username'])) { header('Location: login.php'); exit; }
include 'connect.php';
$title = 'Add Student';
$error = '';

if (isset($_POST['btnAdd'])) {
    $idnum   = trim($_POST['txtidnumber']);
    $fname   = trim($_POST['txtfirstname']);
    $lname   = trim($_POST['txtlastname']);
    $contact = trim($_POST['txtcontact']);
    $dob     = $_POST['txtdob'];

    if (empty($idnum)||empty($fname)||empty($lname)) {
        $error = 'ID Number, First Name, and Last Name are required.';
    } else {
        $stmt = $connection->prepare("INSERT INTO tblstudent(idnumber,firstname,lastname,contactno,dob) VALUES(?,?,?,?,?)");
        $stmt->bind_param("sssss", $idnum,$fname,$lname,$contact,$dob);
        if ($stmt->execute()) {
            $_SESSION['flash'] = ['type'=>'success','msg'=>"Student $fname $lname added successfully."];
            header('Location: dashboard.php'); exit;
        } else {
            $error = 'Failed to save record. Please try again.';
        }
        $stmt->close();
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
