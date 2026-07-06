<?php
date_default_timezone_set('Asia/Jakarta');

$host = getenv('DB_HOST') ?: "localhost";
$user = getenv('DB_USER') ?: "root";
$pass = getenv('DB_PASS') ?: "";
$db = getenv('DB_NAME') ?: "rental_games";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
  die("Koneksi gagal: " . mysqli_connect_error());
}

// Set timezone for MySQL
mysqli_query($conn, "SET time_zone = '+07:00'");
?>
