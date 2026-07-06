<?php
include 'config/db.php';
$res = mysqli_query($conn, "SELECT id, nama_pelanggan, tanggal, jam_mulai, jam_selesai, status, status_pembayaran, bukti_pembayaran FROM bookings ORDER BY id DESC LIMIT 5");
while ($r = mysqli_fetch_assoc($res)) {
    print_r($r);
}
?>
