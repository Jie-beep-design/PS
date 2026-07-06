<?php
session_start();
include 'config/db.php'; 

if(!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_cabang_id = $_SESSION['admin_id'];
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $console_id = $_POST['console_id'];
    $nama_pelanggan = mysqli_real_escape_string($conn, $_POST['nama_pelanggan']);
    $no_telp = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $tanggal = $_POST['tanggal'];
    $jam_mulai = $_POST['jam_mulai'];
    $durasi = (int)$_POST['durasi'];

    $start_time = strtotime("$tanggal $jam_mulai");
    $end_time = $start_time + ($durasi * 3600);
    
    $jam_mulai_baru = date('H:i:s', $start_time);
    $jam_selesai_baru = date('H:i:s', $end_time);

    // Cek bentrok jadwal
    $query_cek = "SELECT id FROM bookings 
                  WHERE console_id = '$console_id' 
                  AND tanggal = '$tanggal' 
                  AND status != 'selesai'
                  AND (jam_mulai < '$jam_selesai_baru') 
                  AND (jam_selesai > '$jam_mulai_baru')";
                  
    $result_cek = mysqli_query($conn, $query_cek);

    if ($result_cek && mysqli_num_rows($result_cek) > 0) {
        $message = "Jadwal tidak tersedia (Bentrok)";
        $message_type = "danger";
    } else {
        // Simpan langsung dengan status sudah bayar & aktif karena admin yang input (Walk-in)
        $user_id = 0; 
        $query_insert = "INSERT INTO bookings (user_id, nama_pelanggan, no_telp, console_id, tanggal, jam_mulai, jam_selesai, durasi_tersisa, status, status_pembayaran, bukti_pembayaran) 
                         VALUES ('$user_id', '$nama_pelanggan', '$no_telp', '$console_id', '$tanggal', '$jam_mulai_baru', '$jam_selesai_baru', '$durasi', 'aktif', 'sudah bayar', 'CASH')";
                         
        if (mysqli_query($conn, $query_insert)) {
            header("Location: dashboard_admin.php?msg=walkin_success");
            exit();
        } else {
            $message = "Error database: " . mysqli_error($conn);
            $message_type = "danger";
        }
    }
}

// Fetch available consoles for this admin
$consoles = [];
$res_console = mysqli_query($conn, "SELECT * FROM consoles WHERE admin_cabang_id = '$admin_cabang_id'");
if($res_console) {
    while($row = mysqli_fetch_assoc($res_console)) {
        $consoles[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Walk-in Booking - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assest/css/style.css?v=<?= time() ?>">
    <script>
      // Load Comic Mode from LocalStorage
      if (localStorage.getItem('theme') === 'comic') {
          document.documentElement.classList.add('comic-mode');
          document.addEventListener('DOMContentLoaded', () => {
              document.body.classList.add('comic-mode');
          });
      }
    </script>
    <style>
        .booking-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0f172a;
            position: relative;
            z-index: 1;
            padding: 2rem 0;
        }
        .booking-card {
            width: 100%;
            max-width: 600px;
            padding: 3rem 2.5rem;
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<nav class="navbar glass-navbar fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-white text-decoration-none" href="dashboard_admin.php">
            <i class="fa-solid fa-arrow-left me-2"></i>Kembali ke Dashboard
        </a>
    </div>
</nav>

<div class="booking-container">
    <div class="glass-card booking-card float-hover">
        <div class="text-center mb-4">
            <!-- Animated Icon Showcase -->
            <div class="ps-showcase-container ps-showcase-fade large-icon mx-auto mb-4" style="height: 120px; width: 120px;">
                <i class="fa-solid fa-gamepad ps-icon-showcase icon-1"></i>
                <i class="fa-solid fa-vr-cardboard ps-icon-showcase icon-2"></i>
                <i class="fa-solid fa-headset ps-icon-showcase icon-3"></i>
            </div>
            <h3 class="text-white fw-bold">Walk-in <span class="neon-text-blue">Booking</span></h3>
            <p class="text-muted">Booking langsung di tempat (Otomatis Sudah Bayar)</p>
        </div>
        
        <?php if($message): ?>
            <div class="alert alert-<?= $message_type ?> bg-<?= $message_type == 'success' ? 'success' : 'danger' ?> bg-opacity-25 text-white border-<?= $message_type ?> mb-4 border">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <form action="form_sewa_admin.php" method="POST">
            <!-- Pilih Console -->
            <div class="mb-4">
                <label class="form-label text-white fw-light"><i class="fa-solid fa-gamepad me-2 neon-text-green"></i>Pilih Console</label>
                <select name="console_id" required class="form-select form-control-glass text-white" style="background-color: #1e293b;">
                    <option value="">-- Pilih Console --</option>
                    <?php foreach($consoles as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nama_console']) ?> - Rp <?= number_format($c['harga_per_jam'], 0, ',', '.') ?>/jam</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Data Penyewa -->
            <div class="mb-4">
                <label class="form-label text-white fw-light"><i class="fa-solid fa-user me-2 neon-text-blue"></i>Nama Pelanggan</label>
                <input type="text" name="nama_pelanggan" required class="form-control form-control-glass text-white" placeholder="Nama Pelanggan">
            </div>
            <div class="mb-4">
                <label class="form-label text-white fw-light"><i class="fa-solid fa-phone me-2 neon-text-purple"></i>No. Telepon (Opsional)</label>
                <input type="text" name="no_telp" class="form-control form-control-glass text-white" placeholder="Contoh: 08123456789">
            </div>

            <!-- Tanggal & Jam Mulai -->
            <div class="row mb-4 g-3">
                <div class="col-6">
                    <label class="form-label text-white fw-light"><i class="fa-regular fa-calendar me-2 neon-text-blue"></i>Tanggal</label>
                    <input type="date" name="tanggal" required class="form-control form-control-glass text-white" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-6">
                    <label class="form-label text-white fw-light"><i class="fa-regular fa-clock me-2 neon-text-purple"></i>Jam Mulai</label>
                    <input type="time" name="jam_mulai" required class="form-control form-control-glass text-white" value="<?= date('H:i') ?>">
                </div>
            </div>
            
            <!-- Durasi -->
            <div class="mb-4">
                <label class="form-label text-white fw-light"><i class="fa-solid fa-hourglass-half me-2 neon-text-green"></i>Durasi Sewa (Jam)</label>
                <input type="number" name="durasi" class="form-control form-control-glass text-white" value="1" min="1" max="24" required>
            </div>
            
            <button type="submit" class="btn btn-neon w-100 py-3 fs-5 mt-2">
                <i class="fa-solid fa-check me-2"></i> Konfirmasi Booking
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
