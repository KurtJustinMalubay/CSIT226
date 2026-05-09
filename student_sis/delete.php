<?php
session_start();
if (!isset($_SESSION['username'])) { header('Location: login.php'); exit; }
include 'connect.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) {
    $s = $connection->prepare("SELECT firstname, lastname FROM tblstudent WHERE id=?");
    $s->bind_param("i",$id); $s->execute();
    $res = $s->get_result();
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $d = $connection->prepare("DELETE FROM tblstudent WHERE id=?");
        $d->bind_param("i",$id); $d->execute(); $d->close();
        $_SESSION['flash'] = ['type'=>'success','msg'=>"Student {$row['firstname']} {$row['lastname']} deleted."];
    }
    $s->close();
}
header('Location: dashboard.php');
exit;
