<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['admin_name'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_cabang_id = $_SESSION['admin_id'];
$message = '';
$message_type = '';

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_jam'])) {
        $jam_buka = $_POST['jam_buka'];
        $jam_tutup = $_POST['jam_tutup'];
        
        if (!empty($jam_buka) && !empty($jam_tutup)) {
            $jam_buka = mysqli_real_escape_string($conn, $jam_buka);
            $jam_tutup = mysqli_real_escape_string($conn, $jam_tutup);
            
            $update_query = "UPDATE admin_cabang SET jam_buka = '$jam_buka', jam_tutup = '$jam_tutup' WHERE id = $admin_cabang_id";
            if (mysqli_query($conn, $update_query)) {
                $message = "Jam operasional berhasil diperbarui!";
                $message_type = "success";
            } else {
                $message = "Gagal memperbarui database: " . mysqli_error($conn);
                $message_type = "danger";
            }
        } else {
            $message = "Jam buka dan jam tutup wajib diisi.";
            $message_type = "warning";
        }
    } elseif (isset($_POST['update_status'])) {
        $status_toko = isset($_POST['status_toko']) ? mysqli_real_escape_string($conn, $_POST['status_toko']) : 'Buka';
        $keterangan_tutup = isset($_POST['keterangan_tutup']) ? mysqli_real_escape_string($conn, $_POST['keterangan_tutup']) : '';
        
        $update_query = "UPDATE admin_cabang SET status_toko = '$status_toko', keterangan_tutup = '$keterangan_tutup' WHERE id = $admin_cabang_id";
        if (mysqli_query($conn, $update_query)) {
            $message = "Status toko berhasil diperbarui!";
            $message_type = "success";
        } else {
            $message = "Gagal memperbarui database: " . mysqli_error($conn);
            $message_type = "danger";
        }
    }
}

// Get current data
$query = mysqli_query($conn, "SELECT jam_buka, jam_tutup, status_toko, keterangan_tutup FROM admin_cabang WHERE id = $admin_cabang_id");
$admin_data = mysqli_fetch_assoc($query);

// Format times for input type time (HH:MM)
$current_buka = $admin_data['jam_buka'] ? substr($admin_data['jam_buka'], 0, 5) : '08:00';
$current_tutup = $admin_data['jam_tutup'] ? substr($admin_data['jam_tutup'], 0, 5) : '22:00';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Toko - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assest/css/style.css?v=<?= time() ?>">
    <style>
        .sidebar-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; position: fixed; height: 100vh; overflow-y: auto; padding: 1.5rem; z-index: 1000; }
        .main-content { margin-left: 280px; flex-grow: 1; display: flex; flex-direction: column; }
        .topbar { padding: 1rem 2rem; }
        
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
            <h3 class="fw-bold neon-text-blue m-0">Admin<span class="neon-text-purple">Panel</span></h3>
            <small class="text-muted">Rental Control System</small>
        </div>
        
        
        <div class="text-center mt-2 mb-3">
            <button id="theme-toggle" class="btn btn-sm btn-outline-light" style="border-radius: 20px; font-weight: bold; border-width: 2px;">
                <i id="theme-icon" class="fa-solid fa-moon"></i> <span id="theme-text">Dark Mode</span>
            </button>
        </div>
        <nav class="nav flex-column mb-auto">
            <a href="dashboard_admin.php" class="sidebar-link" data-preview-title="Dashboard" data-preview-icon="fa-gauge" data-preview-desc="Ringkasan live monitor, status console, dan performa rental hari ini.">><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="data_console.php" class="sidebar-link" data-preview-title="Data Console" data-preview-icon="fa-desktop" data-preview-desc="Manajemen daftar console PS, tambah, edit, atau hapus perangkat.">><i class="fa-solid fa-desktop"></i> Data Console</a>
            <a href="penyewaan.php" class="sidebar-link" data-preview-title="Penyewaan" data-preview-icon="fa-file-invoice-dollar" data-preview-desc="Pantau seluruh transaksi aktif, riwayat booking, dan approval pembayaran.">><i class="fa-solid fa-file-invoice-dollar"></i> Penyewaan</a>
            <a href="laporan.php" class="sidebar-link" data-preview-title="Laporan" data-preview-icon="fa-chart-line" data-preview-desc="Laporan pendapatan finansial detail harian dan bulanan cabang.">><i class="fa-solid fa-chart-line"></i> Laporan</a>
            <a href="pengaturan_pembayaran.php" class="sidebar-link" data-preview-title="Metode Pembayaran" data-preview-icon="fa-qrcode" data-preview-desc="Atur informasi rekening dan e-wallet QRIS untuk menerima pembayaran.">><i class="fa-solid fa-qrcode"></i> Metode Pembayaran</a>
            <a href="pengaturan_toko.php" class="sidebar-link active" data-preview-title="Pengaturan Toko" data-preview-icon="fa-store" data-preview-desc="Kelola status buka/tutup toko, jam operasional, dan keterangan cabang.">><i class="fa-solid fa-store"></i> Pengaturan Toko</a>
            <a href="lapor_masalah.php" class="sidebar-link"><i class="fa-solid fa-headset"></i> Lapor Masalah</a>
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
                <h4 class="m-0 text-white"><i class="fa-solid fa-store me-2 neon-text-blue"></i> Pengaturan Toko</h4>
            </div>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-sm-block">
                    <p class="m-0 text-white fw-bold"><?= htmlspecialchars($_SESSION['admin_name']) ?></p>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="p-4 p-md-5">
            <?php if($message): ?>
                <div class="alert alert-<?= $message_type ?> bg-<?= $message_type == 'success' ? 'success' : 'danger' ?> bg-opacity-25 text-white border-<?= $message_type ?> mb-4">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="glass-card p-4 p-md-5">
                <h5 class="text-white mb-4">Jam Operasional Rental</h5>
                <p class="text-muted mb-4">Atur jam operasional toko Anda. Jadwal console kosong akan otomatis terblokir di luar jam ini.</p>
                
                <form action="pengaturan_toko.php" method="POST" class="mb-5">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="p-3 rounded" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                                <h6 class="text-white mb-3"><i class="fa-regular fa-clock me-2 neon-text-green"></i>Jam Buka</h6>
                                <input type="time" name="jam_buka" class="form-control form-control-glass text-white mb-2" value="<?= $current_buka ?>" required>
                                <small class="text-muted">Contoh: 08:00</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                                <h6 class="text-white mb-3"><i class="fa-solid fa-door-closed me-2 neon-text-red"></i>Jam Tutup</h6>
                                <input type="time" name="jam_tutup" class="form-control form-control-glass text-white mb-2" value="<?= $current_tutup ?>" required>
                                <small class="text-muted">Contoh: 22:00</small>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 text-end">
                        <button type="submit" name="update_jam" class="btn btn-neon px-5"><i class="fa-solid fa-clock me-2"></i> Simpan Jam Operasional</button>
                    </div>
                </form>
                
                <hr class="border-secondary my-5">
                
                <h5 class="text-white mb-4">Pengaturan Status Toko</h5>
                <p class="text-muted mb-4">Atur status buka atau cuti/tutup untuk toko Anda. Perubahan ini akan terlihat oleh penyewa.</p>
                
                <form action="pengaturan_toko.php" method="POST">
                    <div class="row g-4">
                        <div class="col-md-12">
                            <div class="p-3 rounded" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                                <h6 class="text-white mb-3"><i class="fa-solid fa-door-open me-2 text-info"></i>Status Toko</h6>
                                <select name="status_toko" class="form-select form-control-glass text-white mb-2" required>
                                    <option class="text-dark" value="Buka" <?= ($admin_data['status_toko'] ?? 'Buka') == 'Buka' ? 'selected' : '' ?>>Buka (Aktif)</option>
                                    <option class="text-dark" value="Tutup" <?= ($admin_data['status_toko'] ?? '') == 'Tutup' ? 'selected' : '' ?>>Cuti / Tutup</option>
                                </select>
                                <div class="mt-3" id="keteranganTutupWrapper" style="<?= ($admin_data['status_toko'] ?? 'Buka') == 'Tutup' ? '' : 'display: none;' ?>">
                                    <label class="form-label text-light">Keterangan Tutup (Opsional)</label>
                                    <input type="text" name="keterangan_tutup" class="form-control form-control-glass text-white mb-2" placeholder="Contoh: Buka kembali tanggal 16 Mei pukul 09:00" value="<?= htmlspecialchars($admin_data['keterangan_tutup'] ?? '') ?>">
                                </div>
                                <small class="text-warning d-block mt-2"><i class="fa-solid fa-circle-exclamation me-1"></i>Jika sedang cuti atau tutup, <strong>wajib menghubungi penyewa</strong> yang sudah melakukan pemesanan (melalui nomor telepon yang tertera di menu Penyewaan) untuk konfirmasi pembatalan atau penundaan jadwal bermain mereka.</small>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 text-end">
                        <button type="submit" name="update_status" class="btn btn-outline-info px-5"><i class="fa-solid fa-save me-2"></i> Simpan Status Toko</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelector('select[name="status_toko"]').addEventListener('change', function() {
        if(this.value === 'Tutup') {
            document.getElementById('keteranganTutupWrapper').style.display = 'block';
        } else {
            document.getElementById('keteranganTutupWrapper').style.display = 'none';
        }
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
