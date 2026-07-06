<?php
session_start();
if(!isset($_SESSION['superadmin_name'])) {
    header("Location: login_superadmin.php");
    exit();
}

include 'config/db.php';

$bulan_ini = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');

$laporan = [];
$total_keseluruhan = 0;

$query_laporan = "
    SELECT a.nama_rental, a.lokasi, 
           COUNT(b.id) as total_booking,
           SUM(CEIL(TIME_TO_SEC(TIMEDIFF(b.jam_selesai, b.jam_mulai)) / 3600) * c.harga_per_jam) as total_pendapatan
    FROM admin_cabang a
    LEFT JOIN consoles c ON a.id = c.admin_cabang_id
    LEFT JOIN bookings b ON c.id = b.console_id AND DATE_FORMAT(b.tanggal, '%Y-%m') = '$bulan_ini' AND b.status_pembayaran = 'sudah bayar'
    WHERE 1=1
    GROUP BY a.id
    ORDER BY total_pendapatan DESC
";

$res_laporan = mysqli_query($conn, $query_laporan);
if($res_laporan) {
    while($row = mysqli_fetch_assoc($res_laporan)) {
        $row['total_booking'] = $row['total_booking'] ? $row['total_booking'] : 0;
        $row['total_pendapatan'] = $row['total_pendapatan'] ? $row['total_pendapatan'] : 0;
        $total_keseluruhan += $row['total_pendapatan'];
        $laporan[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Global - Super Admin</title>
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
            <a href="data_cabang.php" class="sidebar-link" data-preview-title="Data Cabang" data-preview-icon="fa-store" data-preview-desc="Manajemen alamat, status operasional, dan info toko seluruh cabang.">><i class="fa-solid fa-store"></i> Data Cabang</a>
            <a href="laporan_global.php" class="sidebar-link active" data-preview-title="Laporan Global" data-preview-icon="fa-file-contract" data-preview-desc="Analisis finansial menyeluruh dan unduh laporan performa rental bulanan.">><i class="fa-solid fa-file-contract"></i> Laporan Global</a>
            <a href="kelola_laporan_masalah.php" class="sidebar-link" data-preview-title="Laporan Bug" data-preview-icon="fa-bug" data-preview-desc="Cek laporan kendala atau bug dari user maupun admin cabang.">><i class="fa-solid fa-bug"></i> Laporan Bug</a>
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
                <h4 class="m-0 text-white fw-light">Laporan <span class="fw-bold">Global</span></h4>
            </div>
            <div class="d-flex align-items-center">
                <span class="text-white me-3 d-none d-sm-block"><?= htmlspecialchars($_SESSION['superadmin_name']) ?></span>
            </div>
        </header>

        <!-- Content Body -->
        <div class="p-4 p-md-5">
            
            <div class="glass-card p-4 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h5 class="text-white m-0"><i class="fa-solid fa-calendar-alt me-2 neon-text-blue"></i>Filter Bulan</h5>
                </div>
                <form action="" method="GET" class="d-flex gap-2">
                    <input type="month" name="bulan" value="<?= $bulan_ini ?>" class="form-control bg-dark text-white border-secondary" style="color-scheme: dark;">
                    <button type="submit" class="btn btn-neon">Tampilkan</button>
                </form>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="glass-card p-4 text-center" style="box-shadow: inset 0 0 20px rgba(0, 255, 135, 0.1); border-left: 4px solid var(--neon-green);">
                        <p class="text-muted mb-1 text-uppercase small fw-bold">Total Pendapatan Keseluruhan (Bulan Ini)</p>
                        <h2 class="text-white m-0 neon-text-green fw-bold">Rp <?= number_format($total_keseluruhan, 0, ',', '.') ?></h2>
                    </div>
                </div>
            </div>

            <div class="glass-card p-4">
                <h5 class="text-white mb-4"><i class="fa-solid fa-chart-bar me-2 neon-text-purple"></i>Rincian Pendapatan per Cabang</h5>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle" style="background: transparent;">
                        <thead>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <th>Nama Rental</th>
                                <th>Lokasi</th>
                                <th class="text-center">Total Booking</th>
                                <th class="text-end">Total Pendapatan (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($laporan)): ?>
                                <tr><td colspan="4" class="text-center text-muted">Belum ada data untuk bulan ini.</td></tr>
                            <?php else: ?>
                                <?php foreach($laporan as $row): ?>
                                <tr>
                                    <td class="fw-bold text-white"><?= htmlspecialchars($row['nama_rental']) ?></td>
                                    <td><?= htmlspecialchars($row['lokasi']) ?></td>
                                    <td class="text-center"><span class="badge badge-neon-blue"><?= $row['total_booking'] ?> Transaksi</span></td>
                                    <td class="text-end fw-bold neon-text-green">Rp <?= number_format($row['total_pendapatan'], 0, ',', '.') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
