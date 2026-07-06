<?php
include 'config/db.php';
$res = mysqli_query($conn, 'DESCRIBE admin_cabang');
while($row=mysqli_fetch_assoc($res)) print_r($row);
?>
