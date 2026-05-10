if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $connection->query("DELETE FROM Admin_Staff WHERE adId = $id");
    $connection->query("DELETE FROM User WHERE uid = $id");
    header('Location: index.php');
}