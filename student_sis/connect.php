<?php
//session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connection = new mysqli('localhost', 'root', '', 'lost_and_found_db');

if ($connection->connect_error) {
    die('Connection failed: ' . $connection->connect_error);
}
?>
