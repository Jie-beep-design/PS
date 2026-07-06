<?php
include 'config/db.php';
$res = mysqli_query($conn, 'SHOW TABLES');
while($r = mysqli_fetch_row($res)) {
    echo $r[0]."\n";
    $res2 = mysqli_query($conn, 'DESCRIBE '.$r[0]);
    while($r2 = mysqli_fetch_row($res2)) {
        echo '  '.$r2[0].' '.$r2[1]."\n";
    }
}
?>
