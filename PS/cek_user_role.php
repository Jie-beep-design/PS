<?php
// Include file koneksi database
include_once("config/db.php");

// Ambil data dari POST
$email = $_POST['email'] ?? '';

// Proteksi input
$email = mysqli_real_escape_string($conn, $email);

// Cek apakah email ada di tabel user/admin/developer
$sql = "SELECT role FROM users WHERE email = '$email' LIMIT 1";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo $row['role']; // Output: admin / user / developer
} else {
    echo "not_found";
}
?>
