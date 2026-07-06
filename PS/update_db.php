<?php
include 'config/db.php';

// 1. Alter bookings status
$sql1 = "ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','aktif','pause','selesai') NOT NULL DEFAULT 'pending'";
if (mysqli_query($conn, $sql1)) { echo "1. bookings.status altered successfully\n"; } else { echo "Error 1: " . mysqli_error($conn) . "\n"; }

// 2. Add durasi_tersisa to bookings
$sql2 = "ALTER TABLE bookings ADD COLUMN durasi_tersisa INT DEFAULT 0 AFTER jam_selesai";
if (mysqli_query($conn, $sql2)) { echo "2. bookings.durasi_tersisa added successfully\n"; } else { echo "Error 2: " . mysqli_error($conn) . "\n"; }

// 3. Add status_pembayaran to bookings
$sql3 = "ALTER TABLE bookings ADD COLUMN status_pembayaran ENUM('belum bayar','sudah bayar') DEFAULT 'belum bayar' AFTER status";
if (mysqli_query($conn, $sql3)) { echo "3. bookings.status_pembayaran added successfully\n"; } else { echo "Error 3: " . mysqli_error($conn) . "\n"; }

// 4. Add admin_cabang_id to consoles
$sql4 = "ALTER TABLE consoles ADD COLUMN admin_cabang_id INT AFTER id";
if (mysqli_query($conn, $sql4)) { echo "4. consoles.admin_cabang_id added successfully\n"; } else { echo "Error 4: " . mysqli_error($conn) . "\n"; }

?>
