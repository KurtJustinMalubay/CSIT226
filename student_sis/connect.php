<?php
session_start();

$connection = new mysqli('localhost', 'root', '', 'dbstudentinfosys');

if ($connection->connect_error) {
    die('Connection failed: ' . $connection->connect_error);
}
?>
