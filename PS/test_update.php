<?php
include 'config/db.php';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['edit_console'] = '1';
$_POST['id_console'] = '1';
$_POST['nama_console'] = 'Test Changed';
$_POST['harga_per_jam'] = '20000';
$_POST['tipe'] = 'PS4';
$admin_cabang_id = 1;

$id_edit = mysqli_real_escape_string($conn, $_POST['id_console']);
$nama = mysqli_real_escape_string($conn, $_POST['nama_console']);
$harga = (int) $_POST['harga_per_jam'];
$tipe = mysqli_real_escape_string($conn, $_POST['tipe']);

$update_query = "UPDATE consoles SET nama_console='$nama', harga_per_jam='$harga', tipe='$tipe' WHERE id='$id_edit' AND admin_cabang_id='$admin_cabang_id'";
if (mysqli_query($conn, $update_query)) {
    echo "Success";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>
