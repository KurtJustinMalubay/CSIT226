<?php
session_start();
if (!isset($_SESSION['username'])) { header('Location: login.php'); exit; }
include 'connect.php';

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
$colUserId    = lf_student_pick_column($connection, $userTable, ["uid","id"]);
$colUserFname = lf_student_pick_column($connection, $userTable, ["fname","firstname","first_name"]);
$colUserLname = lf_student_pick_column($connection, $userTable, ["lname","lastname","last_name"]);

if (!$colStudentUserId || !$colUserId) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>'Cannot delete: missing key columns in schema.'];
    header('Location: dashboard.php');
    exit;
}

$id = isset($_GET['id']) ? trim($_GET['id']) : '';
if ($id) {
    $fn = '';
    $ln = '';
    $u = $connection->prepare("SELECT * FROM `$userTable` WHERE `$colUserId`=?");
    $u->bind_param("i",$id); $u->execute();
    $uRes = $u->get_result();
    if ($uRes->num_rows > 0) {
        $row = $uRes->fetch_assoc();
        $fn = $colUserFname ? ($row[$colUserFname] ?? '') : '';
        $ln = $colUserLname ? ($row[$colUserLname] ?? '') : '';
    }
    $u->close();

    $connection->begin_transaction();
    try {
        $d1 = $connection->prepare("DELETE FROM `$studentTable` WHERE `$colStudentUserId`=?");
        $d1->bind_param("i",$id);
        $d1->execute();
        $d1->close();

        $d2 = $connection->prepare("DELETE FROM `$userTable` WHERE `$colUserId`=?");
        $d2->bind_param("i",$id);
        $d2->execute();
        $d2->close();

        $connection->commit();
        $label = trim("$fn $ln");
        $_SESSION['flash'] = ['type'=>'success','msg'=> $label ? "Student $label deleted." : "Student deleted."];
    } catch (Throwable $e) {
        $connection->rollback();
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Failed to delete student record.'];
    }
}
header('Location: dashboard.php');
exit;
