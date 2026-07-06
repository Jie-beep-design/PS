<?php
session_start();
if(!isset($_SESSION['superadmin_name'])) {
    header("Location: login_superadmin.php");
    exit();
}

include 'config/db.php';

$branch_id = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;

$branches = [];
$consoles = [];
$branch_info = null;

if ($branch_id > 0) {
    $res_branch = mysqli_query($conn, "SELECT id, username, nama_rental, lokasi, no_telp, status_toko, keterangan_tutup FROM admin_cabang WHERE id = $branch_id");
    if ($res_branch && mysqli_num_rows($res_branch) > 0) {
        $branch_info = mysqli_fetch_assoc($res_branch);
        
        $res_consoles = mysqli_query($conn, "SELECT * FROM consoles WHERE admin_cabang_id = $branch_id");
        if ($res_consoles) {
            while ($row = mysqli_fetch_assoc($res_consoles)) {
                $consoles[] = $row;
            }
        }
    } else {
        header("Location: data_cabang.php");
        exit();
    }
} else {
    $sql_branches = "SELECT id, username, nama_rental, lokasi, no_telp, status_toko, keterangan_tutup FROM admin_cabang";
    $res_branches = mysqli_query($conn, $sql_branches);
    if ($res_branches) {
        while ($row = mysqli_fetch_assoc($res_branches)) {
            $c_res = mysqli_query($conn, "SELECT COUNT(id) as total FROM consoles WHERE admin_cabang_id = " . $row['id']);
            $row['total_console'] = mysqli_fetch_assoc($c_res)['total'] ?? 0;
            $branches[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Cabang - Super Admin</title>
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
        .ps-card-img { height: 180px; object-fit: cover; border-top-left-radius: 16px; border-top-right-radius: 16px; }
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
    <aside class="glass-sidebar sidebar flex-column d-flex border-end border-secondary">
        <div class="text-center mb-5">
            <h3 class="fw-bold text-white m-0"><i class="fa-solid fa-chess-king me-2 neon-text-yellow"></i>HQ<span class="neon-text-blue">Core</span></h3>
            <small class="text-muted">Super Admin Level</small>
        </div>
        
        
        <div class="text-center mt-2 mb-3">
            <button id="theme-toggle" class="btn btn-sm btn-outline-light" style="border-radius: 20px; font-weight: bold; border-width: 2px;">
                <i id="theme-icon" class="fa-solid fa-moon"></i> <span id="theme-text">Dark Mode</span>
            </button>
        </div>
        <nav class="nav flex-column mb-auto">
            <a href="dashboard_superadmin.php" class="sidebar-link" data-preview-title="Global Dashboard" data-preview-icon="fa-chart-line" data-preview-desc="Ringkasan eksekutif, total pendapatan seluruh cabang, dan jumlah console aktif.">><i class="fa-solid fa-chart-line"></i> Global Dashboard</a>
            <a href="kelola_admin.php" class="sidebar-link" data-preview-title="Kelola Admin" data-preview-icon="fa-users-gear" data-preview-desc="Tambah, edit, atau blokir akun Admin Pengelola Cabang.">><i class="fa-solid fa-users-gear"></i> Kelola Admin</a>
            <a href="data_cabang.php" class="sidebar-link active" data-preview-title="Data Cabang" data-preview-icon="fa-store" data-preview-desc="Manajemen alamat, status operasional, dan info toko seluruh cabang.">><i class="fa-solid fa-store"></i> Data Cabang</a>
            <a href="laporan_global.php" class="sidebar-link" data-preview-title="Laporan Global" data-preview-icon="fa-file-contract" data-preview-desc="Analisis finansial menyeluruh dan unduh laporan performa rental bulanan.">><i class="fa-solid fa-file-contract"></i> Laporan Global</a>
        </nav>
        
        <div class="mt-auto">
            <hr class="border-secondary">
            <a href="logout_superadmin.php" class="sidebar-link text-danger"><i class="fa-solid fa-power-off"></i> Terminate Session</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Topbar -->
        <header class="glass-navbar topbar d-flex justify-content-between align-items-center sticky-top">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-light d-md-none me-3"><i class="fa-solid fa-bars"></i></button>
                <h4 class="m-0 text-white fw-light">Data <span class="fw-bold">Cabang</span></h4>
            </div>
            <div class="d-flex align-items-center">
                <span class="text-white me-3 d-none d-sm-block"><?= htmlspecialchars($_SESSION['superadmin_name']) ?></span>
            </div>
        </header>

        <!-- Content Body -->
        <div class="p-4 p-md-5">
            
            <?php if ($branch_id == 0): ?>
                <!-- Grid Cards Cabang -->
                <div class="row g-4">
                    <?php if (empty($branches)): ?>
                        <div class="col-12 text-center text-muted py-5">Cabang tidak ditemukan.</div>
                    <?php else: ?>
                        <?php foreach($branches as $b): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="glass-card h-100 float-hover p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-circle p-3 me-3" style="background: rgba(0, 243, 255, 0.1); border: 1px solid var(--neon-blue);">
                                        <i class="fa-solid fa-store fs-4 neon-text-blue"></i>
                                    </div>
                                    <div>
                                        <h5 class="text-white fw-bold m-0"><?= htmlspecialchars($b['nama_rental']) ?></h5>
                                        <?php if(($b['status_toko'] ?? 'Buka') == 'Tutup'): ?>
                                            <span class="badge bg-danger mt-1 mb-2"><i class="fa-solid fa-store-slash me-1"></i> Tutup / Cuti</span>
                                        <?php else: ?>
                                            <span class="badge bg-success mt-1 mb-2"><i class="fa-solid fa-door-open me-1"></i> Buka</span>
                                        <?php endif; ?>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-white"><i class="fa-brands fa-whatsapp text-success me-1"></i> <?= htmlspecialchars($b['no_telp'] ?? '-') ?></span>
                                            <span class="text-light" style="font-size: 0.85rem;"><i class="fa-solid fa-location-dot text-danger me-1"></i> <?= htmlspecialchars($b['lokasi']) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <hr class="border-secondary">
                                <div class="d-flex justify-content-between mb-4">
                                    <span class="text-light">Total Console:</span>
                                    <span class="badge badge-neon-purple px-3 py-2"><?= $b['total_console'] ?></span>
                                </div>
                                <a href="data_cabang.php?branch_id=<?= $b['id'] ?>" class="btn btn-outline-info w-100"><i class="fa-solid fa-eye me-2"></i>Lihat Console</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <!-- List Console dalam Cabang -->
                <div class="glass-card p-4 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h2 class="text-white fw-bold m-0 mb-2">
                            <?= htmlspecialchars($branch_info['nama_rental']) ?>
                            <?php if(($branch_info['status_toko'] ?? 'Buka') == 'Tutup'): ?>
                                <span class="badge bg-danger ms-2 fs-6 align-middle"><i class="fa-solid fa-store-slash me-1"></i> Tutup</span>
                            <?php endif; ?>
                        </h2>
                        <div class="text-white mb-1"><i class="fa-brands fa-whatsapp text-success me-1"></i> <?= htmlspecialchars($branch_info['no_telp'] ?? '-') ?></div>
                        <div class="text-light"><i class="fa-solid fa-location-dot me-1 text-danger"></i> <?= htmlspecialchars($branch_info['lokasi']) ?></div>
                    </div>
                    <a href="data_cabang.php" class="btn btn-outline-secondary text-white"><i class="fa-solid fa-arrow-left me-2"></i>Kembali ke Daftar Cabang</a>
                </div>

                <div class="row g-4">
                    <?php if (empty($consoles)): ?>
                        <div class="col-12 text-center text-muted py-5">Belum ada console di cabang ini.</div>
                    <?php else: ?>
                        <?php foreach($consoles as $ps): ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="glass-card h-100 float-hover">
                                <div class="position-relative">
                                    <img src="<?= htmlspecialchars($ps['foto']) ?>" class="w-100 ps-card-img" alt="<?= htmlspecialchars($ps['nama_console']) ?>">
                                    <?php if(($branch_info['status_toko'] ?? 'Buka') == 'Tutup'): ?>
                                    <span class="badge bg-danger position-absolute top-0 end-0 m-3 px-3 py-2 rounded-pill"><i class="fa-solid fa-store-slash me-1"></i>Tutup</span>
                                    <?php else: ?>
                                        <?php if($ps['status'] == 'tersedia'): ?>
                                            <span class="badge badge-neon-green position-absolute top-0 end-0 m-3 px-3 py-2 rounded-pill"><i class="fa-solid fa-check-circle me-1"></i>Tersedia</span>
                                        <?php else: ?>
                                            <span class="badge badge-neon-red position-absolute top-0 end-0 m-3 px-3 py-2 rounded-pill"><i class="fa-solid fa-gamepad me-1"></i><?= ucfirst($ps['status']) ?></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="p-3">
                                    <h6 class="text-white fw-bold mb-1"><?= htmlspecialchars($ps['nama_console']) ?></h6>
                                    <p class="text-muted small mb-2"><?= htmlspecialchars($ps['tipe']) ?></p>
                                    <div class="neon-text-blue fs-6">Rp <?= number_format($ps['harga_per_jam'], 0, ',', '.') ?> / jam</div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

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
