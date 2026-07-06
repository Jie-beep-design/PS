<?php
include 'config/db.php';
$bulan_ini = '07';
$tahun_ini = '2026';
$q_detail = "
    SELECT 
        b.id,
        b.tanggal,
        c.admin_cabang_id,
        COALESCE(b.nama_pelanggan, u.nama, 'Guest') AS nama_user,
        c.nama_console,
        b.status,
        b.status_pembayaran
    FROM bookings b
    JOIN consoles c ON b.console_id = c.id
    LEFT JOIN users u ON b.user_id = u.id
    WHERE MONTH(b.tanggal) = '$bulan_ini' 
    AND YEAR(b.tanggal) = '$tahun_ini'
";
$res = mysqli_query($conn, $q_detail);
while($row = mysqli_fetch_assoc($res)){
    print_r($row);
}
?>
