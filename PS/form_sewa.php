<?php
session_start();
include 'config/db.php'; 

// Login check removed for guest booking
$message = '';
$message_type = '';
$console_id_get = isset($_GET['console_id']) ? (int)$_GET['console_id'] : 0;
$console_data = null;
$harga_per_jam = 0;

if ($console_id_get > 0) {
    $res_console = mysqli_query($conn, "SELECT c.*, a.nama_rental, a.jam_buka, a.jam_tutup, a.status_toko FROM consoles c JOIN admin_cabang a ON c.admin_cabang_id = a.id WHERE c.id = $console_id_get");
    if ($res_console && mysqli_num_rows($res_console) > 0) {
        $console_data = mysqli_fetch_assoc($res_console);
        $harga_per_jam = $console_data['harga_per_jam'];
        
        if (($console_data['status_toko'] ?? 'Buka') == 'Tutup') {
            $message = "Mohon maaf, rental sedang tutup/cuti.";
            $message_type = "warning";
        }
    }
}

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

    $admin_cabang_id_q = mysqli_query($conn, "SELECT admin_cabang_id FROM consoles WHERE id = '$console_id'");
    $admin_cabang_id_res = mysqli_fetch_assoc($admin_cabang_id_q)['admin_cabang_id'];
    $admin_q = mysqli_query($conn, "SELECT jam_buka, jam_tutup, status_toko FROM admin_cabang WHERE id = '$admin_cabang_id_res'");
    $admin_res = mysqli_fetch_assoc($admin_q);

    $is_valid_time = true;
    if (($admin_res['status_toko'] ?? 'Buka') == 'Tutup') {
        $message = "Cabang sedang tutup (cuti).";
        $message_type = "warning";
        $is_valid_time = false;
    } else if (!empty($admin_res['jam_buka']) && !empty($admin_res['jam_tutup'])) {
        $jb = strtotime("$tanggal " . $admin_res['jam_buka']);
        $jt = strtotime("$tanggal " . $admin_res['jam_tutup']);
        if ($jt < $jb) $jt += 86400; // Handle if closing is next day
        
        if ($start_time < $jb || $end_time > $jt) {
            $message = "Waktu sewa di luar jam operasional (" . substr($admin_res['jam_buka'],0,5) . " - " . substr($admin_res['jam_tutup'],0,5) . ").";
            $message_type = "danger";
            $is_valid_time = false;
        }
    }

    if ($is_valid_time) {
        // Cek bentrok jadwal
        $query_cek = "SELECT id FROM bookings 
                      WHERE console_id = '$console_id' 
                      AND tanggal = '$tanggal' 
                      AND status != 'selesai'
                      AND status_pembayaran = 'sudah bayar'
                      AND (jam_mulai < '$jam_selesai_baru') 
                      AND (jam_selesai > '$jam_mulai_baru')";
                      
        $result_cek = mysqli_query($conn, $query_cek);

    if ($result_cek && mysqli_num_rows($result_cek) > 0) {
        $message = "Jadwal tidak tersedia (Bentrok)";
        $message_type = "danger";
    } else {
        // Simpan
        $user_id = 0; // Guest booking
        $query_insert = "INSERT INTO bookings (user_id, nama_pelanggan, no_telp, console_id, tanggal, jam_mulai, jam_selesai, durasi_tersisa, status, status_pembayaran) 
                         VALUES ('$user_id', '$nama_pelanggan', '$no_telp', '$console_id', '$tanggal', '$jam_mulai_baru', '$jam_selesai_baru', '$durasi', 'pending', 'belum bayar')";
                         
        if (mysqli_query($conn, $query_insert)) {
            $booking_id = mysqli_insert_id($conn);
            // Simpan ke session untuk tracking
            $_SESSION['my_bookings'][] = $booking_id;
            
            // Redirect ke pembayaran
            header("Location: pembayaran.php?booking_id=" . $booking_id);
            exit();
        } else {
            $message = "Error database: " . mysqli_error($conn);
            $message_type = "danger";
        }
    }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Console - RumahPS</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assest/css/style.css?v=<?= time() ?>">
    <style>
        .booking-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('https://images.unsplash.com/photo-1542751371-adc38448a05e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            z-index: 1;
            padding: 2rem 0;
        }
        .booking-container::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: var(--bg-gradient);
            opacity: 0.9;
            z-index: -1;
        }
        .booking-card {
            width: 100%;
            max-width: 600px;
            padding: 3rem 2.5rem;
        }
    </style>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<!-- Navbar Minimal -->
<nav class="navbar glass-navbar fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-white text-decoration-none" href="dashboard.php<?= $console_data ? '?branch_id='.$console_data['admin_cabang_id'] : '' ?>">
            <i class="fa-solid fa-arrow-left me-2"></i>Kembali
        </a>
    </div>
</nav>

<div class="booking-container">
    <div class="glass-card booking-card float-hover mx-auto">
        <div class="text-center mb-4">
            <!-- Animated Icon Showcase -->
            <div class="ps-showcase-container ps-showcase-fade large-icon mx-auto mb-4" style="height: 120px; width: 120px;">
                <i class="fa-solid fa-gamepad ps-icon-showcase icon-1"></i>
                <i class="fa-solid fa-vr-cardboard ps-icon-showcase icon-2"></i>
                <i class="fa-solid fa-headset ps-icon-showcase icon-3"></i>
            </div>
                        <h3 class="text-white fw-bold">Booking <span class="neon-text-blue">Console</span></h3>
                        <p class="text-muted">Konfigurasi sesi permainan Anda</p>
                    </div>
        
        <?php if($message): ?>
            <div class="alert alert-<?= $message_type ?> bg-<?= $message_type == 'success' ? 'success' : 'danger' ?> bg-opacity-25 text-white border-<?= $message_type ?> mb-4 border" style="backdrop-filter: blur(5px);">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php if($message_type == 'success'): ?>
                <a href="status_sewa.php" class="btn btn-neon-purple w-100 mb-4">Lihat Status Sewa</a>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if($console_data): ?>
        <form action="form_sewa.php?console_id=<?= $console_id_get ?>" method="POST" id="bookingForm">
            <input type="hidden" name="console_id" value="<?= $console_data['id'] ?>">
            <input type="hidden" id="hargaPerJam" value="<?= $console_data['harga_per_jam'] ?>">

            <!-- Info Console -->
            <div class="mb-4 p-3 rounded info-box-sewa" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                <div class="d-flex align-items-center">
                    <img src="<?= htmlspecialchars($console_data['foto']) ?>" width="80" height="60" class="rounded me-3" style="object-fit: cover;">
                    <div>
                        <h5 class="text-white mb-1"><?= htmlspecialchars($console_data['nama_console']) ?> <span class="badge bg-secondary ms-1"><?= htmlspecialchars($console_data['tipe']) ?></span></h5>
                        <p class="text-muted m-0 small"><i class="fa-solid fa-store me-1"></i> <?= htmlspecialchars($console_data['nama_rental']) ?></p>
                        <?php if(!empty($console_data['jam_buka']) && !empty($console_data['jam_tutup'])): ?>
                            <p class="text-muted m-0 small"><i class="fa-regular fa-clock me-1"></i> Operasional: <?= substr($console_data['jam_buka'],0,5) ?> - <?= substr($console_data['jam_tutup'],0,5) ?></p>
                        <?php endif; ?>
                        <p class="neon-text-blue m-0 fw-bold">Rp <?= number_format($console_data['harga_per_jam'], 0, ',', '.') ?> / jam</p>
                    </div>
                </div>
            </div>

            <!-- Data Penyewa -->
            <div class="mb-4">
                <label class="form-label text-white fw-light"><i class="fa-solid fa-user me-2 neon-text-blue"></i>Nama Lengkap</label>
                <input type="text" name="nama_pelanggan" required class="form-control form-control-glass text-white" placeholder="Masukkan nama Anda">
            </div>
            <div class="mb-4">
                <label class="form-label text-white fw-light"><i class="fa-solid fa-phone me-2 neon-text-purple"></i>No. WhatsApp / Telepon</label>
                <input type="text" name="no_telp" required class="form-control form-control-glass text-white" placeholder="Contoh: 08123456789">
            </div>

            <!-- Tanggal & Jam Mulai -->
            <div class="row mb-4 g-3">
                <div class="col-6">
                    <label class="form-label text-white fw-light"><i class="fa-regular fa-calendar me-2 neon-text-blue"></i>Tanggal</label>
                    <input type="date" name="tanggal" required class="form-control form-control-glass text-white" value="<?= isset($_GET['tanggal']) ? htmlspecialchars($_GET['tanggal']) : date('Y-m-d') ?>">
                </div>
                <div class="col-6">
                    <label class="form-label text-white fw-light"><i class="fa-regular fa-clock me-2 neon-text-purple"></i>Jam Mulai</label>
                    <input type="time" name="jam_mulai" required class="form-control form-control-glass text-white" value="<?= isset($_GET['jam_mulai']) ? htmlspecialchars($_GET['jam_mulai']) : date('H:i') ?>">
                </div>
            </div>
            
            <!-- Durasi -->
            <div class="mb-4">
                <label class="form-label text-white fw-light"><i class="fa-solid fa-hourglass-half me-2 neon-text-green"></i>Durasi Sewa (Jam)</label>
                <div class="input-group">
                    <button class="btn btn-outline-secondary text-white" type="button" onclick="updateDurasi(-1)">-</button>
                    <input type="number" id="durasiInput" name="durasi" class="form-control form-control-glass text-center fs-5 text-white" value="1" min="1" max="24" readonly required>
                    <button class="btn btn-outline-secondary text-white" type="button" onclick="updateDurasi(1)">+</button>
                </div>
            </div>

            <!-- Total Estimasi -->
            <div class="p-3 mb-4 rounded info-box-sewa" style="background: rgba(0,0,0,0.3); border: 1px dashed rgba(255,255,255,0.2);">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">Total Estimasi</span>
                    <span class="fs-4 fw-bold neon-text-blue" id="totalEstimasi">Rp <?= number_format($console_data['harga_per_jam'], 0, ',', '.') ?></span>
                </div>
            </div>
            
            <?php if (($console_data['status_toko'] ?? 'Buka') == 'Tutup'): ?>
                <button type="button" class="btn btn-outline-warning w-100 py-3 fs-5 mt-2 disabled" style="background: rgba(255,193,7,0.1);">
                    <i class="fa-solid fa-lock me-2"></i> Toko Sedang Tutup
                </button>
            <?php else: ?>
                <button type="submit" class="btn btn-neon w-100 py-3 fs-5 mt-2">
                    <i class="fa-solid fa-bolt me-2"></i> Confirm Booking
                </button>
            <?php endif; ?>
        </form>
        <?php else: ?>
            <div class="text-center text-white py-5">
                <h5>Console tidak ditemukan!</h5>
                <a href="dashboard.php" class="btn btn-outline-light mt-3">Kembali ke Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function updateDurasi(val) {
        let input = document.getElementById('durasiInput');
        let currentVal = parseInt(input.value);
        let newVal = currentVal + val;
        
        if (newVal >= 1 && newVal <= 24) {
            input.value = newVal;
            calculateTotal();
        }
    }

    function calculateTotal() {
        let durasi = parseInt(document.getElementById('durasiInput').value);
        let harga = parseInt(document.getElementById('hargaPerJam').value);
        let total = durasi * harga;
        
        // Format to Rupiah
        document.getElementById('totalEstimasi').innerText = 'Rp ' + total.toLocaleString('id-ID');
    }
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Theme Toggle Logic
    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const themeText = document.getElementById('theme-text');
    
    // Check local storage for theme
    if (localStorage.getItem('theme') === 'comic') {
        document.body.classList.add('comic-mode');
        if(themeIcon) themeIcon.className = 'fa-solid fa-sun';
        if(themeText) themeText.innerText = 'Comic Mode';
    }

    if(themeToggleBtn) {
        themeToggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            document.body.classList.toggle('comic-mode');
            if (document.body.classList.contains('comic-mode')) {
                localStorage.setItem('theme', 'comic');
                if(themeIcon) themeIcon.className = 'fa-solid fa-sun';
                if(themeText) themeText.innerText = 'Comic Mode';
            } else {
                localStorage.setItem('theme', 'dark');
                if(themeIcon) themeIcon.className = 'fa-solid fa-moon';
                if(themeText) themeText.innerText = 'Dark Mode';
            }
        });
    }
});
</script>
</body>
</html>
