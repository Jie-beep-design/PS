<?php
session_start();
include 'config/db.php';

if(!isset($_SESSION['admin_name']) || !isset($_SESSION['admin_id'])){
    header("Location: admin_login.php");
    exit();
}

$admin_cabang_id = $_SESSION['admin_id'];

// Handle payment status update if needed (optional feature for admin)
if(isset($_POST['update_pembayaran']) && isset($_POST['booking_id'])){
    $b_id = mysqli_real_escape_string($conn, $_POST['booking_id']);
    $status_bayar = mysqli_real_escape_string($conn, $_POST['status_bayar']);
    mysqli_query($conn, "UPDATE bookings SET status_pembayaran = '$status_bayar' WHERE id = '$b_id'");
}

// Fetch rentals for this admin's consoles
$penyewaan = [];
$query = "
    SELECT 
        b.id AS booking_id,
        COALESCE(b.nama_pelanggan, u.nama, 'Guest') AS nama_user,
        b.no_telp,
        c.nama_console,
        b.tanggal,
        b.jam_mulai,
        b.jam_selesai,
        b.status AS status_bermain,
        b.status_pembayaran,
        b.bukti_pembayaran
    FROM bookings b
    JOIN consoles c ON b.console_id = c.id
    LEFT JOIN users u ON b.user_id = u.id
    WHERE c.admin_cabang_id = '$admin_cabang_id'
    AND b.status_pembayaran = 'sudah bayar'
    ORDER BY b.tanggal DESC, b.jam_mulai DESC
";
$result = mysqli_query($conn, $query);
if ($result) {
    while($row = mysqli_fetch_assoc($result)){
        $penyewaan[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Penyewaan - RumahPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assest/css/style.css?v=<?= time() ?>">
    <style>
        .sidebar-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; position: fixed; height: 100vh; overflow-y: auto; padding: 1.5rem; z-index: 1000; }
        .main-content { margin-left: 280px; flex-grow: 1; display: flex; flex-direction: column; }
        .topbar { padding: 1rem 2rem; }
        .glass-table { background: rgba(0,0,0,0.4); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; }
        .glass-table th { background: rgba(0,0,0,0.6); color: #00f3ff; border-bottom: 1px solid rgba(0, 243, 255, 0.3); }
        .glass-table td { color: #fff; border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: middle; }
        
        .badge-status-belum-mulai { background: rgba(108, 117, 125, 0.2); color: #ced4da; border: 1px solid #6c757d; }
        .badge-status-bermain { background: rgba(220, 53, 69, 0.2); color: #ff4d6d; border: 1px solid #dc3545; box-shadow: 0 0 10px rgba(220,53,69,0.3); }
        .badge-status-selesai { background: rgba(25, 135, 84, 0.2); color: #4ade80; border: 1px solid #198754; }
        
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
            <a href="penyewaan.php" class="sidebar-link active" data-preview-title="Penyewaan" data-preview-icon="fa-file-invoice-dollar" data-preview-desc="Pantau seluruh transaksi aktif, riwayat booking, dan approval pembayaran.">><i class="fa-solid fa-file-invoice-dollar"></i> Penyewaan</a>
            <a href="laporan.php" class="sidebar-link" data-preview-title="Laporan" data-preview-icon="fa-chart-line" data-preview-desc="Laporan pendapatan finansial detail harian dan bulanan cabang.">><i class="fa-solid fa-chart-line"></i> Laporan</a>
            <a href="pengaturan_pembayaran.php" class="sidebar-link" data-preview-title="Metode Pembayaran" data-preview-icon="fa-qrcode" data-preview-desc="Atur informasi rekening dan e-wallet QRIS untuk menerima pembayaran.">><i class="fa-solid fa-qrcode"></i> Metode Pembayaran</a>
            <a href="pengaturan_toko.php" class="sidebar-link" data-preview-title="Pengaturan Toko" data-preview-icon="fa-store" data-preview-desc="Kelola status buka/tutup toko, jam operasional, dan keterangan cabang.">><i class="fa-solid fa-store"></i> Pengaturan Toko</a>
        </nav>
        <div class="mt-auto">
            <hr class="border-secondary">
            <a href="admin_logout.php" class="sidebar-link text-danger"><i class="fa-solid fa-power-off"></i> System Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="glass-navbar topbar d-flex justify-content-between align-items-center sticky-top">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-light d-md-none me-3"><i class="fa-solid fa-bars"></i></button>
                <h4 class="m-0 text-white"><i class="fa-solid fa-file-invoice-dollar me-2 neon-text-purple"></i> Daftar Penyewaan</h4>
            </div>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-sm-block">
                    <p class="m-0 text-white fw-bold"><?= htmlspecialchars($_SESSION['admin_name']) ?></p>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['admin_name']) ?>&background=8b5cf6&color=fff&rounded=true" alt="Admin" width="40" height="40" class="rounded-circle border border-info">
            </div>
        </header>

        <div class="p-4 p-md-5">
            <div class="table-responsive glass-table p-3">
                <table class="table table-dark table-borderless table-hover mb-0" style="background: transparent;">
                    <thead>
                        <tr>
                            <th>Nama User</th>
                            <th>No. Telp User</th>
                            <th>Console</th>
                            <th>Tanggal & Waktu</th>
                            <th>Status Bermain</th>
                            <th>Status Pembayaran</th>
                            <th>Bukti</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($penyewaan)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data penyewaan.</td></tr>
                        <?php endif; ?>
                        <?php foreach($penyewaan as $p): 
                            // Format status bermain
                            $status_bermain_text = '';
                            $status_class = '';
                            if($p['status_bermain'] == 'pending'){
                                $status_bermain_text = 'Belum Mulai';
                                $status_class = 'badge-status-belum-mulai';
                            } elseif($p['status_bermain'] == 'aktif'){
                                $status_bermain_text = 'Sedang Bermain';
                                $status_class = 'badge-status-bermain';
                            } elseif($p['status_bermain'] == 'pause'){
                                $status_bermain_text = 'Pause';
                                $status_class = 'badge-status-belum-mulai border-warning text-warning';
                            } else {
                                $status_bermain_text = 'Selesai';
                                $status_class = 'badge-status-selesai';
                            }

                            // Format status pembayaran
                            $bayar = $p['status_pembayaran'] ?? 'belum bayar';
                            $bayar_class = $bayar == 'sudah bayar' ? 'badge-neon-green' : 'badge-neon-red';
                        ?>
                        <tr>
                            <td class="fw-bold"><i class="fa-solid fa-user text-info me-2"></i><?= htmlspecialchars($p['nama_user'] ?? 'Guest') ?></td>
                            <td><?= htmlspecialchars($p['no_telp'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['nama_console']) ?></td>
                            <td>
                                <div><i class="fa-regular fa-calendar me-2 text-info"></i><span class="text-light"><?= date('d M Y', strtotime($p['tanggal'])) ?></span></div>
                                <div class="small mt-1"><i class="fa-regular fa-clock me-2 text-warning"></i><span class="text-light"><?= substr($p['jam_mulai'],0,5) ?> - <?= substr($p['jam_selesai'],0,5) ?></span></div>
                            </td>
                            <td><span class="badge rounded-pill <?= $status_class ?> px-3 py-2"><?= $status_bermain_text ?></span></td>
                            <td>
                                <span class="badge <?= $bayar_class ?> text-uppercase"><?= $bayar ?></span>
                            </td>
                            <td>
                                <?php if($p['bukti_pembayaran'] === 'CASH'): ?>
                                    <span class="badge bg-success" style="font-size: 0.8rem; border: 2px solid #000; box-shadow: 2px 2px 0 #000;"><i class="fa-solid fa-money-bill-wave me-1"></i> CASH</span>
                                <?php elseif(!empty($p['bukti_pembayaran'])): ?>
                                    <a href="<?= htmlspecialchars($p['bukti_pembayaran']) ?>" target="_blank" class="btn btn-sm btn-outline-info"><i class="fa-solid fa-image me-1"></i> Lihat</a>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
