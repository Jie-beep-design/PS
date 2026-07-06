<?php
include 'config/db.php';
$q = "SELECT a.nama_rental, COUNT(b.id) as total_booking,
       SUM(CEIL(TIME_TO_SEC(TIMEDIFF(b.jam_selesai, b.jam_mulai)) / 3600) * c.harga_per_jam) as total_pendapatan
FROM admin_cabang a
LEFT JOIN consoles c ON a.id = c.admin_cabang_id
LEFT JOIN bookings b ON c.id = b.console_id
WHERE a.id = 1
GROUP BY a.id";
$res = mysqli_query($conn, $q);
print_r(mysqli_fetch_assoc($res));
?>
