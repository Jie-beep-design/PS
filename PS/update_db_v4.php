<?php
include 'config/db.php';

$sql = "ALTER TABLE admin_cabang ADD COLUMN reset_token VARCHAR(255) NULL DEFAULT NULL AFTER no_telp";
if (mysqli_query($conn, $sql)) {
    echo "Kolom reset_token berhasil ditambahkan ke tabel admin.\n";
} else {
    echo "Gagal atau kolom sudah ada: " . mysqli_error($conn) . "\n";
}
?>
