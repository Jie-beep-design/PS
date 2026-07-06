<?php
include 'config/db.php';
$res = mysqli_query($conn, 'SELECT id, username, no_telp FROM admin');
while($row = mysqli_fetch_assoc($res)) {
    echo "ID: " . $row['id'] . " | Username: " . $row['username'] . " | No_Telp: " . $row['no_telp'] . "\n";
}
?>
