<?php
session_start();
if (!isset($_SESSION['username'])) { header('Location: login.php'); exit; }
include 'connect.php';
$title = 'Edit Student';
$error = '';
$programs = ['BSIT','BSCS','BSIS','BSN','BSED','BEED','BSBA','BSACCOUNTANCY','BSCRIM','BSA'];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: dashboard.php'); exit; }

// Load existing record
$s = $connection->prepare("SELECT * FROM tblstudent WHERE id=?");
$s->bind_param("i",$id); $s->execute();
$res = $s->get_result();
if ($res->num_rows===0) { header('Location: dashboard.php'); exit; }
$student = $res->fetch_assoc();
$s->close();

if (isset($_POST['btnUpdate'])) {
    $idnum   = trim($_POST['txtidnumber']);
    $fname   = trim($_POST['txtfirstname']);
    $lname   = trim($_POST['txtlastname']);
    $gender  = $_POST['txtgender'];
    $program = $_POST['txtprogram'];
    $contact = trim($_POST['txtcontact']);
    $dob     = $_POST['txtdob'];

    if (empty($idnum)||empty($fname)||empty($lname)||empty($program)) {
        $error = 'ID Number, First Name, Last Name and Program are required.';
    } else {
        $u = $connection->prepare("UPDATE tblstudent SET idnumber=?,firstname=?,lastname=?,gender=?,program=?,contactno=?,dob=? WHERE id=?");
        $u->bind_param("sssssssi",$idnum,$fname,$lname,$gender,$program,$contact,$dob,$id);
        if ($u->execute()) {
            $_SESSION['flash'] = ['type'=>'success','msg'=>"Student record updated successfully."];
            header('Location: dashboard.php'); exit;
        } else {
            $error = 'Failed to update record. Please try again.';
        }
        $u->close();
    }
    // re-populate with POST data
    $student = array_merge($student, ['idnumber'=>$idnum,'firstname'=>$fname,'lastname'=>$lname,
        'gender'=>$gender,'program'=>$program,'contactno'=>$contact,'dob'=>$dob]);
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
        <p>Updating record for <strong><?php echo htmlspecialchars($student['firstname'].' '.$student['lastname']); ?></strong></p>
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
                <label for="txtidnumber">ID Number *</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-id-card"></i>
                    <input type="text" id="txtidnumber" name="txtidnumber" class="has-icon"
                           value="<?php echo htmlspecialchars($student['idnumber']??''); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="txtgender">Gender</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-venus-mars"></i>
                    <select id="txtgender" name="txtgender" class="has-icon">
                        <option value="">-- Select --</option>
                        <option value="Male"   <?php echo ($student['gender']==='Male')?'selected':''; ?>>Male</option>
                        <option value="Female" <?php echo ($student['gender']==='Female')?'selected':''; ?>>Female</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="txtfirstname">First Name *</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-user"></i>
                    <input type="text" id="txtfirstname" name="txtfirstname" class="has-icon"
                           value="<?php echo htmlspecialchars($student['firstname']); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="txtlastname">Last Name *</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-user"></i>
                    <input type="text" id="txtlastname" name="txtlastname" class="has-icon"
                           value="<?php echo htmlspecialchars($student['lastname']); ?>" required>
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="txtprogram">Program *</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-graduation-cap"></i>
                    <select id="txtprogram" name="txtprogram" class="has-icon" required>
                        <option value="">-- Select Program --</option>
                        <?php foreach($programs as $p): ?>
                        <option value="<?php echo $p; ?>" <?php echo ($student['program']===$p)?'selected':''; ?>><?php echo $p; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="txtdob">Date of Birth</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-calendar"></i>
                    <input type="date" id="txtdob" name="txtdob" class="has-icon"
                           value="<?php echo htmlspecialchars($student['dob']??''); ?>">
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="txtcontact">Contact Number</label>
            <div class="input-wrap">
                <i class="input-icon fas fa-phone"></i>
                <input type="text" id="txtcontact" name="txtcontact" class="has-icon"
                       value="<?php echo htmlspecialchars($student['contactno']??''); ?>">
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
