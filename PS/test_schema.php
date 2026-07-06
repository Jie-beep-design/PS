<?php
include 'config/db.php';
$r = mysqli_query($conn, 'SHOW CREATE TABLE bookings');
$row = mysqli_fetch_row($r);
echo $row[1];
?>
