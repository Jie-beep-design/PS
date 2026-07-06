<?php
require 'config/db.php';

echo "Memulai migrasi database...\n";

// 1. Rename admin to admin_cabang
$sql1 = "RENAME TABLE admin TO admin_cabang";
if (mysqli_query($conn, $sql1)) {
    echo "1. Berhasil mengubah nama tabel admin menjadi admin_cabang.\n";
} else {
    echo "Error 1: " . mysqli_error($conn) . "\n";
}

// 2. Buat tabel admin baru untuk superadmin
$sql2 = "CREATE TABLE IF NOT EXISTS admin (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NULL
)";
if (mysqli_query($conn, $sql2)) {
    echo "2. Berhasil membuat tabel admin baru.\n";
} else {
    echo "Error 2: " . mysqli_error($conn) . "\n";
}

// 3. Pindahkan data superadmin ke tabel admin baru
$sql3 = "INSERT INTO admin (username, password, email) SELECT username, password, email FROM admin_cabang WHERE role = 'superadmin'";
if (mysqli_query($conn, $sql3)) {
    echo "3. Berhasil memindahkan data superadmin.\n";
} else {
    echo "Error 3: " . mysqli_error($conn) . "\n";
}

// 4. Hapus superadmin dari admin_cabang
$sql4 = "DELETE FROM admin_cabang WHERE role = 'superadmin'";
if (mysqli_query($conn, $sql4)) {
    echo "4. Berhasil menghapus superadmin dari admin_cabang.\n";
} else {
    echo "Error 4: " . mysqli_error($conn) . "\n";
}

// 5. Hapus kolom role dari admin_cabang
$sql5 = "ALTER TABLE admin_cabang DROP COLUMN role";
if (mysqli_query($conn, $sql5)) {
    echo "5. Berhasil menghapus kolom role dari admin_cabang.\n";
} else {
    echo "Error 5: " . mysqli_error($conn) . "\n";
}

// 6. Rename admin_id ke admin_cabang_id di tabel consoles
$sql6 = "ALTER TABLE consoles CHANGE admin_id admin_cabang_id INT(11)";
if (mysqli_query($conn, $sql6)) {
    echo "6. Berhasil mengubah admin_id menjadi admin_cabang_id pada tabel consoles.\n";
} else {
    echo "Error 6: " . mysqli_error($conn) . "\n";
}

// 7. Rename admin_id ke admin_cabang_id di tabel laporan_bug
$sql7 = "ALTER TABLE laporan_bug CHANGE admin_id admin_cabang_id INT(11)";
if (mysqli_query($conn, $sql7)) {
    echo "7. Berhasil mengubah admin_id menjadi admin_cabang_id pada tabel laporan_bug.\n";
} else {
    echo "Error 7: " . mysqli_error($conn) . "\n";
}

echo "Migrasi selesai.\n";
?>
