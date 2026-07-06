<?php
include 'config/db.php';
$bulan_ini = date('m');
$tahun_ini = date('Y');
$admin_cabang_id = 1; // Assuming admin 1

$q_detail = "
    SELECT 
        b.tanggal,
        COALESCE(b.nama_pelanggan, u.nama, 'Guest') AS nama_user,
        b.no_telp,
        c.nama_console,
        c.harga_per_jam,
        b.jam_mulai,
        b.jam_selesai,
        CEIL(TIME_TO_SEC(TIMEDIFF(b.jam_selesai, b.jam_mulai)) / 3600) as jam_main,
        b.status_pembayaran
    FROM bookings b
    JOIN consoles c ON b.console_id = c.id
    LEFT JOIN users u ON b.user_id = u.id
    WHERE c.admin_cabang_id = '$admin_cabang_id'
    AND MONTH(b.tanggal) = '$bulan_ini' 
    AND YEAR(b.tanggal) = '$tahun_ini'
    ORDER BY b.tanggal DESC
";
$res_detail = mysqli_query($conn, $q_detail);
while($row = mysqli_fetch_assoc($res_detail)){
    print_r($row);
}
?>
