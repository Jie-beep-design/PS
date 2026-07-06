<?php
session_start();
include 'config/db.php'; 

// session pengelola 
if(!isset($_SESSION['admin_name'])){
    header("Location: admin_login.php");
    exit();
}
// Uncomment line below if DB is ready 

// Fitur Auto Start
global $conn;
$action_msg = '';
$action_type = '';

if ($conn) {
    $auto_start_query = "UPDATE bookings 
                         SET status = 'aktif' 
                         WHERE status = 'pending' 
                         AND status_pembayaran = 'sudah bayar'
                         AND NOW() >= CONCAT(tanggal, ' ', jam_mulai)";
    mysqli_query($conn, $auto_start_query);
    
    // Fitur Auto Stop
    $auto_stop_query = "UPDATE bookings 
                        SET status = 'selesai' 
                        WHERE status = 'aktif' 
                        AND NOW() >= CONCAT(tanggal, ' ', jam_selesai)";
    mysqli_query($conn, $auto_stop_query);
    
    // Auto Cleanup Bukti Pembayaran (dalam 30 Hari)
    // Dijalankan secara acak (1 dari 10 request) untuk menghemat resource
    if (rand(1, 10) == 1) {
        $cleanup_query = "SELECT id, bukti_pembayaran FROM bookings WHERE tanggal < DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND bukti_pembayaran IS NOT NULL";
        $cleanup_res = mysqli_query($conn, $cleanup_query);
        if ($cleanup_res) {
            while($row = mysqli_fetch_assoc($cleanup_res)) {
                $file_path = $row['bukti_pembayaran'];
                if (file_exists($file_path)) {
                    @unlink($file_path);
                }
                mysqli_query($conn, "UPDATE bookings SET bukti_pembayaran = NULL WHERE id = " . $row['id']);
            }
        }
    }
}

// Fitur Extend Waktu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['extend_booking_id'])) {
    if (isset($conn) && $conn) {
        $booking_id = mysqli_real_escape_string($conn, $_POST['extend_booking_id']);
        $tambahan_jam = 1; // extend 1 jam
        
        $query_current = mysqli_query($conn, "SELECT console_id, tanggal, jam_selesai FROM bookings WHERE id = '$booking_id'");
        if ($row = mysqli_fetch_assoc($query_current)) {
            $console_id = $row['console_id'];
            $tanggal = $row['tanggal'];
            $jam_selesai_sekarang = $row['jam_selesai'];
            
            $jam_selesai_baru = date('H:i:s', strtotime("$jam_selesai_sekarang + $tambahan_jam hours"));
            
            // Cek bentrok dengan jadwal setelahnya
            $query_cek = "SELECT id FROM bookings 
                          WHERE console_id = '$console_id' 
                          AND tanggal = '$tanggal' 
                          AND status != 'selesai'
                          AND status_pembayaran = 'sudah bayar'
                          AND id != '$booking_id'
                          AND (jam_mulai < '$jam_selesai_baru') 
                          AND (jam_selesai > '$jam_selesai_sekarang')";
                          
            $result_cek = mysqli_query($conn, $query_cek);
            
            if (mysqli_num_rows($result_cek) > 0) {
                $action_msg = "Tidak bisa extend, jadwal berikutnya sudah ada.";
                $action_type = "danger";
            } else {
                $query_update = "UPDATE bookings 
                                 SET jam_selesai = '$jam_selesai_baru', durasi = durasi + $tambahan_jam 
                                 WHERE id = '$booking_id'";
                if(mysqli_query($conn, $query_update)) {
                    $action_msg = "Waktu berhasil di-extend $tambahan_jam jam.";
                    $action_type = "success";
                }
            }
        }
    } else {
        $action_msg = "Simulasi: Fitur Extend berhasil dieksekusi (DB Off).";
        $action_type = "success";
    }
}

// Ambil data monitoring console berdasarkan admin_cabang_id
$consoles = [];
$admin_cabang_id = $_SESSION['admin_id'];

$status_toko = 'Buka';
$keterangan_tutup = '';

if (isset($conn) && $conn) {
    $q_status = mysqli_query($conn, "SELECT status_toko, keterangan_tutup FROM admin_cabang WHERE id = $admin_cabang_id");
    if ($q_status && $row_status = mysqli_fetch_assoc($q_status)) {
        $status_toko = $row_status['status_toko'] ?? 'Buka';
        $keterangan_tutup = $row_status['keterangan_tutup'] ?? '';
    }
    
    $query_monitor = "
        SELECT 
            p.id AS console_id, 
            p.nama_console AS console_nama, 
            p.harga_per_jam AS harga, 
            p.foto AS img,
            b.id AS booking_id,
            b.status AS booking_status,
            b.jam_mulai,
            b.jam_selesai,
            b.durasi_tersisa,
            u.nama AS user_name
        FROM consoles p
        LEFT JOIN bookings b 
            ON p.id = b.console_id 
            AND b.status IN ('aktif', 'pending', 'pause') 
            AND b.status_pembayaran = 'sudah bayar'
            AND b.tanggal = CURDATE()
        LEFT JOIN users u 
            ON b.user_id = u.id
        WHERE p.admin_cabang_id = '$admin_cabang_id'
        ORDER BY p.id ASC
    ";

    $result = mysqli_query($conn, $query_monitor);
    if($result) {
        while($row = mysqli_fetch_assoc($result)) {
            $status = $row['booking_status'] ?? 'tersedia';
            if($status == 'pending') $status = 'tersedia'; 
            if($status == 'aktif') $status = 'digunakan';
            
            // Fallback image
            $img = !empty($row['img']) ? $row['img'] : 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=500&q=80';
            
            $timer = "00:00:00";
            $end_timestamp = 0;
            
            if($status == 'digunakan') {
                $end_timestamp = strtotime(date('Y-m-d') . ' ' . $row['jam_selesai']);
                $sisa = max(0, $end_timestamp - time());
                $timer = gmdate("H:i:s", $sisa);
            } elseif ($status == 'pause') {
                $sisa = (int)$row['durasi_tersisa'];
                $timer = gmdate("H:i:s", $sisa);
                $end_timestamp = time() + $sisa; // for resume calculation later if needed
            }
            
            $consoles[] = [
                "id" => "PS-" . str_pad($row['console_id'], 2, '0', STR_PAD_LEFT),
                "db_id" => $row['console_id'],
                "booking_id" => $row['booking_id'],
                "nama" => $row['console_nama'],
                "harga" => $row['harga'],
                "status" => $status,
                "img" => $img,
                "time" => $timer,
                "end_timestamp" => $end_timestamp,
                "user" => $row['user_name'] ?? '-',
                "jam_mulai" => $row['jam_mulai'] ?? '-',
                "jam_selesai" => $row['jam_selesai'] ?? '-'
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengelola Dashboard - RumahPS</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assest/css/style.css?v=<?= time() ?>">
    <style>
        .sidebar-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; position: fixed; height: 100vh; overflow-y: auto; padding: 1.5rem; z-index: 1000; }
        .main-content { margin-left: 280px; flex-grow: 1; display: flex; flex-direction: column; }
        .topbar { padding: 1rem 2rem; }
        .card-img-top-sm { height: 160px; object-fit: cover; border-top-left-radius: 16px; border-top-right-radius: 16px; }
        .status-glow-tersedia { box-shadow: inset 0 0 20px rgba(0, 255, 135, 0.1); }
        .status-glow-digunakan { box-shadow: inset 0 0 20px rgba(255, 0, 85, 0.1); border-color: rgba(255,0,85,0.4); }
        .status-glow-pause { box-shadow: inset 0 0 20px rgba(255, 234, 0, 0.1); border-color: rgba(255,234,0,0.4); }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; width: 100%; }
        }
    </style>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="sidebar-layout">
    <!-- Sidebar -->
    <aside class="glass-sidebar sidebar flex-column d-flex">
        <div class="text-center mb-5">
            <h3 class="fw-bold neon-text-blue m-0">Pengelola<span class="neon-text-purple">Panel</span></h3>
            <small class="text-muted">Rental Control System</small>
        </div>
        
        
        <div class="text-center mt-2 mb-3">
            <button id="theme-toggle" class="btn btn-sm btn-outline-light" style="border-radius: 20px; font-weight: bold; border-width: 2px;">
                <i id="theme-icon" class="fa-solid fa-moon"></i> <span id="theme-text">Dark Mode</span>
            </button>
        </div>
        <nav class="nav flex-column mb-auto">
            <a href="dashboard_admin.php" class="sidebar-link active" data-preview-title="Dashboard" data-preview-icon="fa-gauge" data-preview-desc="Ringkasan live monitor, status console, dan performa rental hari ini."><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="data_console.php" class="sidebar-link" data-preview-title="Data Console" data-preview-icon="fa-desktop" data-preview-desc="Manajemen daftar console PS, tambah, edit, atau hapus perangkat."><i class="fa-solid fa-desktop"></i> Data Console</a>
            <a href="penyewaan.php" class="sidebar-link" data-preview-title="Penyewaan" data-preview-icon="fa-file-invoice-dollar" data-preview-desc="Pantau seluruh transaksi aktif, riwayat booking, dan approval pembayaran."><i class="fa-solid fa-file-invoice-dollar"></i> Penyewaan</a>
            <a href="laporan.php" class="sidebar-link" data-preview-title="Laporan" data-preview-icon="fa-chart-line" data-preview-desc="Laporan pendapatan finansial detail harian dan bulanan cabang."><i class="fa-solid fa-chart-line"></i> Laporan</a>
            <a href="pengaturan_pembayaran.php" class="sidebar-link" data-preview-title="Metode Pembayaran" data-preview-icon="fa-qrcode" data-preview-desc="Atur informasi rekening dan e-wallet QRIS untuk menerima pembayaran."><i class="fa-solid fa-qrcode"></i> Metode Pembayaran</a>
            <a href="pengaturan_toko.php" class="sidebar-link" data-preview-title="Pengaturan Toko" data-preview-icon="fa-store" data-preview-desc="Kelola status buka/tutup toko, jam operasional, dan keterangan cabang."><i class="fa-solid fa-store"></i> Pengaturan Toko</a>
        </nav>
        
        <div class="mt-auto">
            <hr class="border-secondary">
            <a href="admin_logout.php" class="sidebar-link text-danger"><i class="fa-solid fa-power-off"></i> System Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Topbar -->
        <header class="glass-navbar topbar d-flex justify-content-between align-items-center sticky-top">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-light d-md-none me-3"><i class="fa-solid fa-bars"></i></button>
                <h4 class="m-0 text-white"><i class="fa-solid fa-satellite-dish me-2 neon-text-blue"></i> Live Monitoring Consol</h4>
            </div>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-sm-block">
                    <p class="m-0 text-white fw-bold"><?= htmlspecialchars($_SESSION['admin_name']) ?></p>
                    <small class="neon-text-green"><i class="fa-solid fa-circle text-success" style="font-size: 8px;"></i> Online</small>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['admin_name']) ?>&background=00f3ff&color=000&rounded=true" alt="Admin" width="40" height="40" class="rounded-circle border border-info">
            </div>
        </header>

        <!-- Content Body -->
        <div class="p-4 p-md-5">
            <?php if($action_msg): ?>
                <div class="alert alert-animated alert-<?= $action_type ?> bg-<?= $action_type == 'success' ? 'success' : 'danger' ?> bg-opacity-25 text-white border-<?= $action_type ?> mb-4" style="backdrop-filter: blur(5px);">
                    <?= htmlspecialchars($action_msg) ?>
                </div>
            <?php endif; ?>
            

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="text-white">Console Status</h5>
                <a href="form_sewa_admin.php" class="btn btn-sm btn-neon"><i class="fa-solid fa-plus me-1"></i> Booking Baru</a>
            </div>

            <div class="row g-4">
                <?php foreach($consoles as $c): 
                    $glowClass = 'status-glow-'.$c['status'];
                    $badgeClass = '';
                    $iconClass = '';
                    $statusText = $c['status'];
                    if($status_toko == 'Tutup') {
                        $badgeClass = 'bg-danger';
                        $iconClass = 'fa-store-slash';
                        $statusText = 'Tutup';
                        $glowClass = 'status-glow-pause';
                    }
                    elseif($c['status'] == 'tersedia') { $badgeClass = 'badge-neon-green'; $iconClass = 'fa-check'; }
                    elseif($c['status'] == 'digunakan') { $badgeClass = 'badge-neon-red'; $iconClass = 'fa-gamepad'; }
                    elseif($c['status'] == 'pause') { 
                        $badgeClass = 'badge-neon-yellow'; 
                        $iconClass = 'fa-clock'; 
                        $statusText = 'pending'; 
                    }
                ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="glass-card h-100 float-hover <?= $glowClass ?>">
                        <div class="position-relative">
                            <img src="<?= $c['img'] ?>" class="w-100 card-img-top-sm" alt="<?= $c['nama'] ?>">
                            <div class="position-absolute top-0 start-0 w-100 p-2 d-flex justify-content-between">
                                <span class="badge bg-dark text-white border border-secondary"><?= $c['id'] ?></span>
                                <span class="badge <?= $badgeClass ?> text-uppercase"><i class="fa-solid <?= $iconClass ?> me-1"></i> <?= $statusText ?></span>
                            </div>
                        </div>
                        
                        <div class="p-3">
                            <h5 class="text-white mb-1"><?= htmlspecialchars($c['nama']) ?></h5>
                            <p class="text-muted small mb-2">Rate: Rp <?= number_format((int)$c['harga'], 0, ',', '.') ?>/jam</p>
                            
                            <div class="mb-3" style="font-size: 0.85rem;">
                                <div class="text-gray-400 d-flex justify-content-between mb-1">
                                    <span><i class="fa-solid fa-user text-info me-2"></i><?= htmlspecialchars($c['user']) ?></span>
                                </div>
                                <div class="text-gray-400 d-flex justify-content-between">
                                    <span><i class="fa-regular fa-clock text-warning me-2"></i><?= substr($c['jam_mulai'],0,5) ?> - <?= substr($c['jam_selesai'],0,5) ?></span>
                                </div>
                            </div>
                            
                            <!-- Timer Display -->
                            <div class="text-center mb-3 p-2 rounded" style="background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.05);">
                                <?php if($c['status'] == 'tersedia'): ?>
                                    <h3 class="m-0 text-muted" style="font-family: monospace;">--:--</h3>
                                <?php elseif($c['status'] == 'digunakan'): ?>
                                    <h3 class="m-0 neon-text-red timer-aktif" style="font-family: monospace;" data-endtime="<?= $c['end_timestamp'] ?>"><?= $c['time'] ?></h3>
                                <?php elseif($c['status'] == 'pause'): ?>
                                    <h3 class="m-0 neon-text-yellow blink-text" style="font-family: monospace;"><?= $c['time'] ?></h3>
                                <?php endif; ?>
                            </div>

                            <!-- Control Buttons -->
                            <div class="row g-2">
                                <?php if($c['status'] == 'tersedia'): ?>
                                    <div class="col-12"><button class="btn btn-neon w-100 btn-action-start" data-bookingid="<?= htmlspecialchars($c['booking_id'] ?? '') ?>" data-consoleid="<?= $c['db_id'] ?>"><i class="fa-solid fa-play me-1"></i> Start Consol</button></div>
                                <?php elseif($c['status'] == 'digunakan'): ?>
                                    <div class="col-4"><button class="btn btn-neon-yellow w-100 px-0 btn-action-pause" title="Pause" data-bookingid="<?= htmlspecialchars($c['booking_id'] ?? '') ?>"><i class="fa-solid fa-pause"></i></button></div>
                                    <div class="col-4"><button class="btn btn-neon-purple w-100 px-0 btn-action-extend" title="Extend" data-bookingid="<?= htmlspecialchars($c['booking_id'] ?? '') ?>"><i class="fa-solid fa-clock-rotate-left"></i></button></div>
                                    <div class="col-4"><button class="btn btn-neon-red w-100 px-0 btn-action-stop" title="Stop" data-bookingid="<?= htmlspecialchars($c['booking_id'] ?? '') ?>"><i class="fa-solid fa-stop"></i></button></div>
                                <?php elseif($c['status'] == 'pause'): ?>
                                    <div class="col-6"><button class="btn btn-neon w-100 btn-action-resume" data-bookingid="<?= htmlspecialchars($c['booking_id'] ?? '') ?>"><i class="fa-solid fa-play me-1"></i> Resume</button></div>
                                    <div class="col-6"><button class="btn btn-neon-red w-100 btn-action-stop" data-bookingid="<?= htmlspecialchars($c['booking_id'] ?? '') ?>"><i class="fa-solid fa-stop me-1"></i> Stop</button></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>

<!-- Toast Container -->
<div id="toast-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1060; margin-top: 60px;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Optimized Timer Loop
    setInterval(function() {
        let timers = document.querySelectorAll('.timer-aktif');
        let now = Math.floor(Date.now() / 1000);
        timers.forEach(function(el) {
            let endTimestamp = parseInt(el.getAttribute('data-endtime'));
            if(endTimestamp > 0) {
                let sisa = endTimestamp - now;
                if(sisa > 0) {
                    let h = Math.floor(sisa / 3600);
                    let m = Math.floor((sisa % 3600) / 60);
                    let s = sisa % 60;
                    el.innerText = (h > 0 ? String(h).padStart(2, '0') + ':' : '') + 
                                   String(m).padStart(2, '0') + ':' + 
                                   String(s).padStart(2, '0');
                } else {
                    el.innerText = "00:00:00";
                    el.classList.remove('neon-text-red', 'timer-aktif');
                    el.classList.add('text-muted');
                }
            }
        });
    }, 1000);

    // Toast Notification Logic
    function showToast(message, type = 'success') {
        let container = document.getElementById('toast-container');
        let toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show shadow-lg mb-2`;
        toast.style.minWidth = '250px';
        toast.style.background = 'rgba(0, 0, 0, 0.85)';
        
        let color = '#00f3ff';
        if (type === 'warning') color = '#ffea00';
        if (type === 'danger') color = '#ff0055';
        
        toast.style.border = `1px solid ${color}`;
        toast.style.color = '#fff';
        toast.style.backdropFilter = 'blur(10px)';
        
        toast.innerHTML = `
            <i class="fa-solid fa-bell me-2" style="color: ${color}"></i> 
            ${message}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 150);
        }, 3000);
    }

    // Server Action Helper
    async function doBookingAction(action, bookingId, extraData = {}) {
        if (!bookingId) return false;
        const formData = new FormData();
        formData.append('action', action);
        formData.append('booking_id', bookingId);
        for (const key in extraData) formData.append(key, extraData[key]);

        try {
            const response = await fetch('api_booking_action.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.status === 'success') {
                showToast(result.message, "success");
                setTimeout(() => location.reload(), 800); // Reload to ensure accurate state
            } else {
                showToast(result.message, "danger");
            }
        } catch (error) {
            showToast("Terjadi kesalahan jaringan", "danger");
        }
    }

    // Unified Event Delegation
    document.addEventListener('click', function(e){
        let btnStart = e.target.closest('.btn-action-start');
        if(btnStart) {
            e.preventDefault();
            let bId = btnStart.getAttribute('data-bookingid');
            if (!bId) {
                let hours = prompt("Console ini kosong. Masukkan durasi sewa (jam):", "1");
                if(hours && !isNaN(hours) && parseInt(hours) > 0) {
                    doBookingAction('start_new', '0', {
                        console_id: btnStart.getAttribute('data-consoleid'),
                        hours: hours
                    });
                }
            } else {
                doBookingAction('start', bId);
            }
            return;
        }

        let btnPause = e.target.closest('.btn-action-pause');
        if(btnPause) {
            e.preventDefault();
            // Optional visual switch immediately for snappy feel
            btnPause.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            doBookingAction('pause', btnPause.getAttribute('data-bookingid'));
            return;
        }

        let btnResume = e.target.closest('.btn-action-resume');
        if(btnResume) {
            e.preventDefault();
            btnResume.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            doBookingAction('resume', btnResume.getAttribute('data-bookingid'));
            return;
        }

        let btnStop = e.target.closest('.btn-action-stop');
        if(btnStop) {
            e.preventDefault();
            if(confirm("Yakin ingin menghentikan session ini?")) {
                doBookingAction('stop', btnStop.getAttribute('data-bookingid'));
            }
            return;
        }

        let btnExtend = e.target.closest('.btn-action-extend');
        if(btnExtend) {
            e.preventDefault();
            let hours = prompt("Masukkan durasi tambahan (jam):", "1");
            if(hours && !isNaN(hours) && parseInt(hours) > 0) {
                doBookingAction('extend', btnExtend.getAttribute('data-bookingid'), {hours: hours});
            } else if (hours !== null) {
                showToast("Durasi tidak valid", "warning");
            }
            return;
        }
    });
});
</script>







<!-- Dynamic Hover Preview Container & Script -->
<div id="menu-preview" class="menu-preview-card">
    <h6><i id="preview-icon" class="fa-solid fa-info-circle"></i> <span id="preview-title">Title</span></h6>
    <p id="preview-desc">Description</p>
</div>
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
        themeToggleBtn.addEventListener('click', () => {
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

    // Mobile Sidebar Toggle
    const toggleBtn = document.querySelector('.btn-outline-light.d-md-none');
    const sidebar = document.querySelector('.sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            sidebar.classList.toggle('show');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target) && sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }

    const previewCard = document.getElementById('menu-preview');
    const previewTitle = document.getElementById('preview-title');
    const previewDesc = document.getElementById('preview-desc');
    const previewIconEl = document.getElementById('preview-icon');
    
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.addEventListener('mouseenter', (e) => {
            if(window.innerWidth < 768) return; // Disable on mobile
            const title = link.getAttribute('data-preview-title');
            if(title) {
                previewTitle.innerText = title;
                previewDesc.innerText = link.getAttribute('data-preview-desc');
                previewIconEl.className = `fa-solid ${link.getAttribute('data-preview-icon')}`;
                previewCard.classList.add('show');
            }
        });
        
        link.addEventListener('mousemove', (e) => {
            if(window.innerWidth < 768) return;
            // offset so cursor doesn't block the card
            previewCard.style.left = (e.clientX + 20) + 'px';
            previewCard.style.top = (e.clientY + 20) + 'px';
        });
        
        link.addEventListener('mouseleave', () => {
            previewCard.classList.remove('show');
        });
    });
});
</script>

</body>
</html>
