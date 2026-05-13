<?php
session_start();
if (!isset($_SESSION['username'])) { header('Location: login.php'); exit; }
include 'connect.php';
$title = 'Add Student';
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

function lf_has_column(mysqli $connection, string $table, string $column): bool {
    return lf_student_pick_column($connection, $table, [$column]) !== null;
}

if (isset($_POST['btnAdd'])) {
    $idnum   = trim($_POST['txtidnumber']);
    $fname   = trim($_POST['txtfirstname']);
    $lname   = trim($_POST['txtlastname']);
    $contact = trim($_POST['txtcontact']);
    $course  = trim($_POST['txtcourse'] ?? '');
    $yearlvl = trim($_POST['txtyearlvl'] ?? '');

    if (empty($idnum)||empty($fname)||empty($lname)||empty($course)||empty($yearlvl)) {
        $error = 'ID Number, First Name, Last Name, Course and Year Level are required.';
    } else {
        $studentTable = "student";
        $userTable = "user";

        $userIdCol      = lf_student_pick_column($connection, $userTable, ["uid","id"]);
        $userFnameCol   = lf_student_pick_column($connection, $userTable, ["fname","firstname","first_name"]);
        $userLnameCol   = lf_student_pick_column($connection, $userTable, ["lname","lastname","last_name"]);
        $userContactCol = lf_student_pick_column($connection, $userTable, ["contactNum","contactno","contact"]);
        $userUniCol     = lf_student_pick_column($connection, $userTable, ["universityId","studId","idnumber"]);
        $userEmailCol   = lf_student_pick_column($connection, $userTable, ["email","emailadd"]);
        $userPassCol    = lf_student_pick_column($connection, $userTable, ["password"]);
        $userAdminCol   = lf_student_pick_column($connection, $userTable, ["isAdmin"]);
        $userStudentCol = lf_student_pick_column($connection, $userTable, ["isStudent"]);

        $studUserLinkCol = lf_student_pick_column($connection, $studentTable, ["studId","studentId","uid","userId"]);
        $studCourseCol   = lf_student_pick_column($connection, $studentTable, ["course","program"]);
        $studYearCol     = lf_student_pick_column($connection, $studentTable, ["yearLevel","yearlvl","year_level","year"]);

        if (!$userIdCol || !$userFnameCol || !$userLnameCol || !$studUserLinkCol || !$studCourseCol || !$studYearCol) {
            $error = "Missing required columns in `user`/`student` tables. Please verify schema.";
        } else {
            $connection->begin_transaction();
            try {
                // 1) Create parent user record (student identity fields live here)
                $uCols = [$userFnameCol, $userLnameCol];
                $uVals = [$fname, $lname];
                $uTypes = "ss";

                if ($userContactCol) { $uCols[] = $userContactCol; $uVals[] = $contact; $uTypes .= "s"; }
                if ($userUniCol)     { $uCols[] = $userUniCol;     $uVals[] = $idnum;   $uTypes .= "s"; }
                if ($userEmailCol)   { $uCols[] = $userEmailCol;   $uVals[] = $idnum.'@student.local'; $uTypes .= "s"; }
                if ($userPassCol)    { $uCols[] = $userPassCol;    $uVals[] = password_hash('student123', PASSWORD_DEFAULT); $uTypes .= "s"; }
                if ($userAdminCol)   { $uCols[] = $userAdminCol;   $uVals[] = 0; $uTypes .= "i"; }
                if ($userStudentCol) { $uCols[] = $userStudentCol; $uVals[] = 1; $uTypes .= "i"; }

                $uColList = implode(",", array_map(fn($c) => "`$c`", $uCols));
                $uPlaceholders = rtrim(str_repeat("?,", count($uCols)), ",");
                $uStmt = $connection->prepare("INSERT INTO `$userTable`($uColList) VALUES($uPlaceholders)");
                $uStmt->bind_param($uTypes, ...$uVals);
                $uStmt->execute();
                $newUserId = $connection->insert_id;
                $uStmt->close();

                // 2) Create student subtable row (student-only fields)
                $sCols = [$studUserLinkCol, $studCourseCol, $studYearCol];
                $sVals = [$newUserId, $course, $yearlvl];
                $sTypes = "iss";

                $sColList = implode(",", array_map(fn($c) => "`$c`", $sCols));
                $sPlaceholders = rtrim(str_repeat("?,", count($sCols)), ",");
                $sStmt = $connection->prepare("INSERT INTO `$studentTable`($sColList) VALUES($sPlaceholders)");
                $sStmt->bind_param($sTypes, ...$sVals);
                $sStmt->execute();
                $sStmt->close();

                $connection->commit();
                $_SESSION['flash'] = ['type'=>'success','msg'=>"Student $fname $lname added successfully."];
                header('Location: dashboard.php'); exit;
            } catch (Throwable $e) {
                $connection->rollback();
                $error = 'Failed to save record. ' . $e->getMessage();
            }
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
                <label for="txtcontact">Contact Number</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-phone"></i>
                    <input type="text" id="txtcontact" name="txtcontact" class="has-icon"
                           placeholder="09XXXXXXXXX"
                           value="<?php echo isset($_POST['txtcontact'])?htmlspecialchars($_POST['txtcontact']):''; ?>">
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="txtcourse">Course *</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-graduation-cap"></i>
                    <input type="text" id="txtcourse" name="txtcourse" class="has-icon"
                           placeholder="e.g. BSIT"
                           value="<?php echo isset($_POST['txtcourse'])?htmlspecialchars($_POST['txtcourse']):''; ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="txtyearlvl">Year Level *</label>
                <div class="input-wrap">
                    <i class="input-icon fas fa-layer-group"></i>
                    <select id="txtyearlvl" name="txtyearlvl" class="has-icon" required>
                        <?php $yl = $_POST['txtyearlvl'] ?? ''; ?>
                        <option value="">-- Select Year Level --</option>
                        <option value="1" <?php echo ($yl==='1')?'selected':''; ?>>1</option>
                        <option value="2" <?php echo ($yl==='2')?'selected':''; ?>>2</option>
                        <option value="3" <?php echo ($yl==='3')?'selected':''; ?>>3</option>
                        <option value="4" <?php echo ($yl==='4')?'selected':''; ?>>4</option>
                        <option value="5" <?php echo ($yl==='5')?'selected':''; ?>>5</option>
                    </select>
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
