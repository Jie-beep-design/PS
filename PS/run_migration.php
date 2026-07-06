<?php
include 'config/db.php';

// Add columns to bookings
$sqls = [
    "ALTER TABLE bookings ADD COLUMN nama_pelanggan VARCHAR(255) NULL AFTER user_id",
    "ALTER TABLE bookings ADD COLUMN no_telp VARCHAR(50) NULL AFTER nama_pelanggan",
    "ALTER TABLE bookings MODIFY COLUMN user_id INT(11) NULL DEFAULT 0",
    "ALTER TABLE admin_cabang ADD COLUMN qris_image VARCHAR(255) NULL AFTER password",
    "ALTER TABLE admin_cabang ADD COLUMN dana_image VARCHAR(255) NULL AFTER qris_image"
];

foreach ($sqls as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "Success: $sql\n";
    } else {
        echo "Error: " . mysqli_error($conn) . " on $sql\n";
    }
}
echo "Migration finished.\n";
?>
