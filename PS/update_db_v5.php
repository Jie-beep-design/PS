<?php
include 'config/db.php';
mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN bukti_pembayaran VARCHAR(255) DEFAULT NULL");
echo 'Done';
?>
