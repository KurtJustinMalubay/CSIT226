<?php
session_start();
if (!isset($_SESSION['uid'])) { header('Location: login.php'); exit; }
include 'connect.php';
$title = 'Edit Student';
$error = '';
$programs = ['BSIT','BSCS','BSIS','BSN','BSED','BEED','BSBA','BSACCOUNTANCY','BSCRIM','BSA'];

$uid = isset($_GET['uid']) ? $_GET['uid'] : '';
if (!$uid) { header('Location: dashboard.php'); exit; }

// Load existing record
$s = $connection->prepare("SELECT u.*, s.course, s.contactNo, s.dob FROM user u LEFT JOIN student s ON u.uId = s.studId WHERE u.uId=?");
$s->bind_param("s",$uid); $s->execute();
$res = $s->get_result();
if ($res->num_rows===0) { header('Location: dashboard.php'); exit; }
$student = $res->fetch_assoc();
$s->close();

if (isset($_POST['btnUpdate'])) {
    $idnum   = trim($_POST['txtidnumber']);
    $fname   = trim($_POST['txtfirstname']);
    $lname   = trim($_POST['txtlastname']);
    $program = $_POST['txtprogram'];
    $contact = trim($_POST['txtcontact']);
    $dob     = $_POST['txtdob'];

    if (empty($idnum)||empty($fname)||empty($lname)||empty($program)) {
        $error = 'ID Number, First Name, Last Name and Program are required.';
    } else {
        $connection->begin_transaction();
        try {
            $fullName = $fname . ' ' . $lname;
            $u1 = $connection->prepare("UPDATE user SET fullName=?, universityId=? WHERE uId=?");
            $u1->bind_param("sss", $fullName, $idnum, $uid);
            $u1->execute();

            $u2 = $connection->prepare("UPDATE student SET course=?, contactNo=?, dob=? WHERE studId=?");
            $u2->bind_param("ssss", $program, $contact, $dob, $uid);
            $u2->execute();

            $connection->commit();
            $_SESSION['flash'] = ['type'=>'success','msg'=>"Student record updated successfully."];
            header('Location: dashboard.php'); exit;
        } catch (Exception $e) {
            $connection->rollback();
            $error = 'Failed to update record: ' . $e->getMessage();
        }
    }
    // re-populate with POST data for preview if error
    $student = array_merge($student, ['universityId'=>$idnum,'fullName'=>$fullName,'course'=>$program,'contactNo'=>$contact,'dob'=>$dob]);
}
require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <div class="breadcrumb">
            <a href="index.php">Home</a><span class="breadcrumb-sep">/</span>
            <a href="dashboard.php">Dashboard</a><span class="breadcrumb-sep">/</span>
            <span>Edit Student</span>
        </div>
        <h1>Edit Student</h1>
        <p>Updating record for <strong><?php echo htmlspecialchars($student['fullName']); ?></strong></p>
    </div>
    <a href="dashboard.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="form-card form-card-wide" style="max-width:700px;margin:0;">
    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
        <div class="form-row">
            <div class="form-group">
                <label for="txtidnumber">University ID *</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-id-card"></i>
                    <input type="text" id="txtidnumber" name="txtidnumber" class="has-icon"
                           value="<?php echo htmlspecialchars($student['universityId']??''); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="txtprogram">Program *</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-graduation-cap"></i>
                    <select id="txtprogram" name="txtprogram" class="has-icon" required>
                        <option value="">-- Select Program --</option>
                        <?php foreach($programs as $p): ?>
                        <option value="<?php echo $p; ?>" <?php echo (isset($student['course']) && $student['course']===$p)?'selected':''; ?>><?php echo $p; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <?php 
        $names = explode(' ', $student['fullName'], 2);
        $fname = $names[0];
        $lname = isset($names[1]) ? $names[1] : '';
        ?>
        <div class="form-row">
            <div class="form-group">
                <label for="txtfirstname">First Name *</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-user"></i>
                    <input type="text" id="txtfirstname" name="txtfirstname" class="has-icon"
                           value="<?php echo htmlspecialchars($fname); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="txtlastname">Last Name *</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-user"></i>
                    <input type="text" id="txtlastname" name="txtlastname" class="has-icon"
                           value="<?php echo htmlspecialchars($lname); ?>" required>
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="txtdob">Date of Birth</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-calendar"></i>
                    <input type="date" id="txtdob" name="txtdob" class="has-icon"
                           value="<?php echo htmlspecialchars($student['dob']??''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="txtcontact">Contact Number</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-phone"></i>
                    <input type="text" id="txtcontact" name="txtcontact" class="has-icon"
                           value="<?php echo htmlspecialchars($student['contactNo']??''); ?>">
                </div>
            </div>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" name="btnUpdate" class="btn btn-accent" style="flex:1;">
                <i class="fas fa-floppy-disk"></i> Save Changes
            </button>
            <a href="dashboard.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
