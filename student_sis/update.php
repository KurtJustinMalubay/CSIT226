<?php
session_start();
if (!isset($_SESSION['username'])) { header('Location: login.php'); exit; }
include 'connect.php';
$title = 'Edit Student';
$error = '';

function lf_student_pick_column(mysqli $connection, string $table, array $candidates): ?string {
    $schema = mysqli_fetch_assoc(mysqli_query($connection, "SELECT DATABASE() AS db"))['db'] ?? null;
    if (!$schema) return null;

    $stmt = $connection->prepare(
        "SELECT COUNT(*) AS c
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );

    foreach ($candidates as $col) {
        $stmt->bind_param("sss", $schema, $table, $col);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        if (($row['c'] ?? 0) > 0) { $stmt->close(); return $col; }
    }
    $stmt->close();
    return null;
}

$studentTable = "student";
$userTable = "user";
$colStudentUserId = lf_student_pick_column($connection, $studentTable, ["studId","studentId","uid","userId"]);
$colCourse  = lf_student_pick_column($connection, $studentTable, ["course","program"]);
$colYear    = lf_student_pick_column($connection, $studentTable, ["yearLevel","yearlvl","year_level","year"]);

$colUserId      = lf_student_pick_column($connection, $userTable, ["uid","id"]);
$colUserFname   = lf_student_pick_column($connection, $userTable, ["fname","firstname","first_name"]);
$colUserLname   = lf_student_pick_column($connection, $userTable, ["lname","lastname","last_name"]);
$colUserContact = lf_student_pick_column($connection, $userTable, ["contactNum","contactno","contact"]);
$colUserUniId   = lf_student_pick_column($connection, $userTable, ["universityId","studId","idnumber"]);

if (!$colStudentUserId || !$colCourse || !$colYear || !$colUserId || !$colUserFname || !$colUserLname) {
    $error = 'Missing required columns in `user`/`student` tables.';
    require_once 'includes/header.php';
    ?>
    <div class="form-card form-card-wide" style="max-width:700px;margin:0;">
        <div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i><?php echo htmlspecialchars($error); ?></div>
    </div>
    <?php
    require_once 'includes/footer.php';
    exit;
}

$id = isset($_GET['id']) ? trim($_GET['id']) : '';
if ($id === '') { header('Location: dashboard.php'); exit; }

// Load existing record
$s = $connection->prepare("SELECT * FROM `$studentTable` WHERE `$colStudentUserId`=?");
$s->bind_param("i",$id); $s->execute();
$res = $s->get_result();
if ($res->num_rows===0) { header('Location: dashboard.php'); exit; }
$student = $res->fetch_assoc();
$s->close();

$u = $connection->prepare("SELECT * FROM `$userTable` WHERE `$colUserId`=?");
$u->bind_param("i",$id); $u->execute();
$uRes = $u->get_result();
if ($uRes->num_rows===0) { header('Location: dashboard.php'); exit; }
$user = $uRes->fetch_assoc();
$u->close();

if (isset($_POST['btnUpdate'])) {
    $idnum   = trim($_POST['txtidnumber']);
    $fname   = trim($_POST['txtfirstname']);
    $lname   = trim($_POST['txtlastname']);
    $contact = trim($_POST['txtcontact']);
    $course  = trim($_POST['txtcourse'] ?? '');
    $yearlvl = trim($_POST['txtyearlvl'] ?? '');

    if (empty($idnum)||empty($fname)||empty($lname)||empty($course)||empty($yearlvl)) {
        $error = 'ID Number, First Name, Last Name, Course and Year Level are required.';
    } else {
        $connection->begin_transaction();
        try {
            $userSet = ["`$colUserFname`=?", "`$colUserLname`=?"];
            $userTypes = "ss";
            $userVals = [$fname, $lname];
            if ($colUserContact) { $userSet[] = "`$colUserContact`=?"; $userTypes .= "s"; $userVals[] = $contact; }
            if ($colUserUniId)   { $userSet[] = "`$colUserUniId`=?";   $userTypes .= "s"; $userVals[] = $idnum; }
            $userTypes .= "i";
            $userVals[] = (int)$id;
            $userSql = implode(", ", $userSet);
            $uq = $connection->prepare("UPDATE `$userTable` SET $userSql WHERE `$colUserId`=?");
            $uq->bind_param($userTypes, ...$userVals);
            $uq->execute();
            $uq->close();

            $sq = $connection->prepare("UPDATE `$studentTable` SET `$colCourse`=?, `$colYear`=? WHERE `$colStudentUserId`=?");
            $sq->bind_param("ssi", $course, $yearlvl, $id);
            $sq->execute();
            $sq->close();

            $connection->commit();
            $_SESSION['flash'] = ['type'=>'success','msg'=>"Student record updated successfully."];
            header('Location: dashboard.php'); exit;
        } catch (Throwable $e) {
            $connection->rollback();
            $error = 'Failed to update record. ' . $e->getMessage();
        }
    }
    $patched = [$colUserFname=>$fname,$colUserLname=>$lname];
    if ($colUserUniId) $patched[$colUserUniId] = $idnum;
    if ($colUserContact) $patched[$colUserContact] = $contact;
    $user = array_merge($user, $patched);
    $student = array_merge($student, [$colCourse=>$course,$colYear=>$yearlvl]);
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
        <p>Updating record for <strong><?php echo htmlspecialchars(($user[$colUserFname] ?? '').' '.($user[$colUserLname] ?? '')); ?></strong></p>
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
                           value="<?php echo htmlspecialchars($colUserUniId ? ($user[$colUserUniId] ?? '') : ''); ?>" required>
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="txtfirstname">First Name *</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-user"></i>
                    <input type="text" id="txtfirstname" name="txtfirstname" class="has-icon"
                           value="<?php echo htmlspecialchars($user[$colUserFname] ?? ''); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="txtlastname">Last Name *</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-user"></i>
                    <input type="text" id="txtlastname" name="txtlastname" class="has-icon"
                           value="<?php echo htmlspecialchars($user[$colUserLname] ?? ''); ?>" required>
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="txtcourse">Course *</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-graduation-cap"></i>
                    <input type="text" id="txtcourse" name="txtcourse" class="has-icon"
                           value="<?php echo htmlspecialchars($student[$colCourse] ?? ''); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="txtyearlvl">Year Level *</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-layer-group"></i>
                    <?php $yl = $student[$colYear] ?? ''; ?>
                    <select id="txtyearlvl" name="txtyearlvl" class="has-icon" required>
                        <option value="">-- Select Year Level --</option>
                        <option value="1" <?php echo ((string)$yl==='1')?'selected':''; ?>>1</option>
                        <option value="2" <?php echo ((string)$yl==='2')?'selected':''; ?>>2</option>
                        <option value="3" <?php echo ((string)$yl==='3')?'selected':''; ?>>3</option>
                        <option value="4" <?php echo ((string)$yl==='4')?'selected':''; ?>>4</option>
                        <option value="5" <?php echo ((string)$yl==='5')?'selected':''; ?>>5</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="txtcontact">Contact Number</label>
            <div class="input-wrap">
                <i class="input-icon fas fa-phone"></i>
                <input type="text" id="txtcontact" name="txtcontact" class="has-icon"
                       value="<?php echo htmlspecialchars($colUserContact ? ($user[$colUserContact] ?? '') : ''); ?>">
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
