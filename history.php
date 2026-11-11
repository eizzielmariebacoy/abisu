<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "history";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
  die("Failed to connect DB: " . $conn->connect_error);
}
?>