<?php
include "config/db.php";

$username = "jiji";
$password = "123";
$hash = password_hash($password, PASSWORD_DEFAULT);
$nama_rental = "Super Rental";
$lokasi = "Pusat";

// Cek apakah sudah ada username yang sama
$cek = mysqli_query($conn, "SELECT * FROM admin WHERE username = '$username'");
if (mysqli_num_rows($cek) > 0) {
  echo "❌ Username sudah terdaftar.";
} else {
  $simpan = mysqli_query($conn, "INSERT INTO admin (username, password)
    VALUES ('$username', '$hash')");

  if ($simpan) {
    echo "✅ Akun super admin berhasil dibuat!<br>Username: <b>$username</b><br>Password: <b>$password</b>";
  } else {
    echo "❌ Gagal menyimpan akun super admin.";
  }
}
?>
