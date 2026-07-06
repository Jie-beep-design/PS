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

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $qris_path = '';
    $dana_path = '';

    if (isset($_FILES['qris_image']) && $_FILES['qris_image']['error'] == 0) {
        $ext = pathinfo($_FILES['qris_image']['name'], PATHINFO_EXTENSION);
        $filename = 'qris_' . $admin_cabang_id . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['qris_image']['tmp_name'], $upload_dir . $filename)) {
            $qris_path = $upload_dir . $filename;
        }
    }

    $update_query = "UPDATE admin_cabang SET ";
    $updates = [];
    if ($qris_path) $updates[] = "qris_image = '$qris_path'";

    if (count($updates) > 0) {
        $update_query .= implode(', ', $updates) . " WHERE id = $admin_cabang_id";
        if (mysqli_query($conn, $update_query)) {
            $message = "Metode pembayaran berhasil diperbarui!";
            $message_type = "success";
        } else {
            $message = "Gagal memperbarui database.";
            $message_type = "danger";
        }
    }
}

// Get current data
$query = mysqli_query($conn, "SELECT qris_image FROM admin_cabang WHERE id = $admin_cabang_id");
$admin_data = mysqli_fetch_assoc($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metode Pembayaran - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assest/css/style.css?v=<?= time() ?>">
    <style>
        .sidebar-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; position: fixed; height: 100vh; overflow-y: auto; padding: 1.5rem; z-index: 1000; }
        .main-content { margin-left: 280px; flex-grow: 1; display: flex; flex-direction: column; }
        .topbar { padding: 1rem 2rem; }
        .qr-preview { max-width: 200px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); margin-top: 10px; }
        
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
            <a href="pengaturan_pembayaran.php" class="sidebar-link active" data-preview-title="Metode Pembayaran" data-preview-icon="fa-qrcode" data-preview-desc="Atur informasi rekening dan e-wallet QRIS untuk menerima pembayaran.">><i class="fa-solid fa-qrcode"></i> Metode Pembayaran</a>
            <a href="pengaturan_toko.php" class="sidebar-link" data-preview-title="Pengaturan Toko" data-preview-icon="fa-store" data-preview-desc="Kelola status buka/tutup toko, jam operasional, dan keterangan cabang.">><i class="fa-solid fa-store"></i> Pengaturan Toko</a>
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
                <h4 class="m-0 text-white"><i class="fa-solid fa-qrcode me-2 neon-text-blue"></i> Pengaturan Pembayaran</h4>
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
                <h5 class="text-white mb-4">Upload QR Code Pembayaran</h5>
                <form action="pengaturan_pembayaran.php" method="POST" enctype="multipart/form-data">
                    <div class="row g-4">
                        <div class="col-md-12">
                            <div class="p-3 rounded" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                                <h6 class="text-white mb-3">QRIS Image (Wajib)</h6>
                                <input type="file" name="qris_image" class="form-control form-control-glass text-white mb-3" accept="image/*">
                                <?php if($admin_data['qris_image']): ?>
                                    <p class="text-muted small mb-1">Preview Saat Ini:</p>
                                    <img src="<?= htmlspecialchars($admin_data['qris_image']) ?>" class="qr-preview">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-neon px-5"><i class="fa-solid fa-save me-2"></i> Simpan Pengaturan</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>







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
