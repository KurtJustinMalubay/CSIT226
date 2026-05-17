session_start();
if (!isset($_SESSION['uid'])) { header('Location: login.php'); exit; }
include 'connect.php';

$uid = isset($_GET['uid']) ? $_GET['uid'] : '';
if ($uid) {
    $s = $connection->prepare("SELECT fullName FROM user WHERE uId=?");
    $s->bind_param("s",$uid); $s->execute();
    $res = $s->get_result();
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $d = $connection->prepare("DELETE FROM user WHERE uId=?");
        $d->bind_param("s",$uid); $d->execute(); $d->close();
        $_SESSION['flash'] = ['type'=>'success','msg'=>"Student {$row['fullName']} deleted."];
    }
    $s->close();
}
header('Location: dashboard.php');
exit;
