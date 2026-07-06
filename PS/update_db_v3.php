<?php
include 'config/db.php';

// Add columns to admin
$q1 = "ALTER TABLE admin_cabang 
       ADD COLUMN foto_rental VARCHAR(255) NULL, 
       ADD COLUMN no_telp VARCHAR(50) NULL, 
       ADD COLUMN status_toko ENUM('Buka', 'Tutup') DEFAULT 'Buka', 
       ADD COLUMN keterangan_tutup VARCHAR(255) NULL";
if(mysqli_query($conn, $q1)) {
    echo "Kolom berhasil ditambahkan ke tabel admin.\n";
} else {
    echo "Gagal/kolom sudah ada: " . mysqli_error($conn) . "\n";
}

// Create laporan_bug table
$q2 = "CREATE TABLE IF NOT EXISTS laporan_bug (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_cabang_id INT,
    kategori_masalah VARCHAR(100),
    deskripsi TEXT,
    status ENUM('Menunggu', 'Diproses', 'Selesai') DEFAULT 'Menunggu',
    tanggal_laporan DATETIME DEFAULT CURRENT_TIMESTAMP
)";
if(mysqli_query($conn, $q2)) {
    echo "Tabel laporan_bug berhasil dibuat.\n";
} else {
    echo "Gagal buat tabel: " . mysqli_error($conn) . "\n";
}

echo "Update DB selesai.\n";
?>
