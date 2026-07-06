<?php
include 'config/db.php';
$q = "SELECT SUM(CEIL(TIME_TO_SEC(TIMEDIFF(b.jam_selesai, b.jam_mulai)) / 3600) * c.harga_per_jam) as total_income FROM bookings b JOIN consoles c ON b.console_id = c.id WHERE c.admin_cabang_id = 1 AND MONTH(b.tanggal) = '07' AND YEAR(b.tanggal) = '2026' AND b.status_pembayaran = 'sudah bayar'";
$res = mysqli_query($conn, $q);
print_r(mysqli_fetch_assoc($res));
?>
