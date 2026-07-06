<?php
// dashboard_superadmin.php - Dummy UI
session_start();
if(!isset($_SESSION['superadmin_name'])) $_SESSION['superadmin_name'] = "GodMode_Admin";

include 'config/db.php';

// 1. Total Cabang
$q_cabang = mysqli_query($conn, "SELECT COUNT(id) AS total FROM admin_cabang");
$total_cabang = mysqli_fetch_assoc($q_cabang)['total'] ?? 0;

// 2. Total Console
$q_console = mysqli_query($conn, "SELECT COUNT(id) AS total FROM consoles");
$total_console = mysqli_fetch_assoc($q_console)['total'] ?? 0;

// 3. Total Transaksi (Hanya yang sudah bayar)
$q_transaksi = mysqli_query($conn, "
    SELECT SUM(CEIL(TIME_TO_SEC(TIMEDIFF(b.jam_selesai, b.jam_mulai)) / 3600) * c.harga_per_jam) AS total 
    FROM bookings b 
    JOIN consoles c ON b.console_id = c.id 
    WHERE b.status_pembayaran = 'sudah bayar'
");
$total_transaksi = mysqli_fetch_assoc($q_transaksi)['total'] ?? 0;
$total_transaksi_formatted = 'Rp ' . number_format($total_transaksi, 0, ',', '.');
if ($total_transaksi >= 1000000) {
    $total_transaksi_formatted = 'Rp ' . round($total_transaksi / 1000000, 1) . 'M';
} elseif ($total_transaksi >= 1000) {
    $total_transaksi_formatted = 'Rp ' . round($total_transaksi / 1000, 1) . 'K';
}

// 4. Active Users (Active bookings)
$q_active = mysqli_query($conn, "SELECT COUNT(id) AS total FROM bookings WHERE status = 'aktif'");
$active_users = mysqli_fetch_assoc($q_active)['total'] ?? 0;

$stats = [
    ["title" => "Total Cabang", "value" => $total_cabang, "icon" => "fa-network-wired", "color" => "blue"],
    ["title" => "Total Console", "value" => $total_console, "icon" => "fa-gamepad", "color" => "purple"],
    ["title" => "Total Transaksi", "value" => $total_transaksi_formatted, "icon" => "fa-chart-pie", "color" => "green"],
    ["title" => "Active Users", "value" => $active_users, "icon" => "fa-users", "color" => "yellow"],
];

// 5. Recent Activity (Latest Bookings)
$recent_activities = [];
$view_all = isset($_GET['view']) && $_GET['view'] === 'all';
$limit_query = $view_all ? "" : "LIMIT 5";

$q_recent = mysqli_query($conn, "
    SELECT b.tanggal, b.jam_mulai, b.status, c.nama_console, a.nama_rental 
    FROM bookings b
    JOIN consoles c ON b.console_id = c.id
    JOIN admin_cabang a ON c.admin_cabang_id = a.id
    ORDER BY b.id DESC $limit_query
");
if ($q_recent) {
    while($row = mysqli_fetch_assoc($q_recent)){
        $recent_activities[] = $row;
    }
}
// 6. Data Keseluruhan Console (Global View per Cabang)
$global_consoles = [];
$q_global_branches = mysqli_query($conn, "SELECT id, nama_rental, status_toko FROM admin_cabang ORDER BY id ASC");
if ($q_global_branches) {
    while ($branch = mysqli_fetch_assoc($q_global_branches)) {
        $branch_id = $branch['id'];
        
        $query_consoles = "
            SELECT 
                c.*, 
                b.status AS booking_status,
                b.jam_selesai,
                b.durasi_tersisa
            FROM consoles c
            LEFT JOIN bookings b 
                ON c.id = b.console_id 
                AND b.status IN ('aktif', 'pause') 
                AND b.tanggal = CURDATE()
            WHERE c.admin_cabang_id = $branch_id
            ORDER BY c.id ASC
        ";
        $res_consoles = mysqli_query($conn, $query_consoles);
        $consoles_list = [];
        if ($res_consoles) {
            while ($c = mysqli_fetch_assoc($res_consoles)) {
                if ($c['booking_status'] == 'aktif') {
                    $c['status_real'] = 'sedang dimainkan';
                } elseif ($c['booking_status'] == 'pause') {
                    $c['status_real'] = 'pending';
                } else {
                    $c['status_real'] = $c['status'];
                }
                
                // Calculate timer if active
                $timer = "";
                $end_timestamp = 0;
                if ($c['status_real'] == 'sedang dimainkan') {
                    if (!empty($c['jam_selesai'])) {
                        $end_timestamp = strtotime(date('Y-m-d') . ' ' . $c['jam_selesai']);
                        $sisa = max(0, $end_timestamp - time());
                        $timer = gmdate("H:i:s", $sisa);
                    }
                } elseif ($c['status_real'] == 'pending') {
                    $sisa = (int)$c['durasi_tersisa'];
                    $timer = gmdate("H:i:s", $sisa);
                }
                
                $c['timer'] = $timer;
                $c['end_timestamp'] = $end_timestamp;
                
                $consoles_list[] = $c;
            }
        }
        $branch['consoles'] = $consoles_list;
        $global_consoles[] = $branch;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - RumahPS</title>
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
        
        .stat-card-blue { box-shadow: inset 0 0 20px rgba(0, 243, 255, 0.1); border-left: 4px solid var(--neon-blue); }
        .stat-card-purple { box-shadow: inset 0 0 20px rgba(188, 19, 254, 0.1); border-left: 4px solid var(--neon-purple); }
        .stat-card-green { box-shadow: inset 0 0 20px rgba(0, 255, 135, 0.1); border-left: 4px solid var(--neon-green); }
        .stat-card-yellow { box-shadow: inset 0 0 20px rgba(255, 234, 0, 0.1); border-left: 4px solid var(--neon-yellow); }
        
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
            <a href="dashboard_superadmin.php" class="sidebar-link active" data-preview-title="Global Dashboard" data-preview-icon="fa-chart-line" data-preview-desc="Ringkasan eksekutif, total pendapatan seluruh cabang, dan jumlah console aktif."><i class="fa-solid fa-chart-line"></i> Global Dashboard</a>
            <a href="kelola_admin.php" class="sidebar-link" data-preview-title="Kelola Admin" data-preview-icon="fa-users-gear" data-preview-desc="Tambah, edit, atau blokir akun Admin Pengelola Cabang."><i class="fa-solid fa-users-gear"></i> Kelola Admin</a>
            <a href="data_cabang.php" class="sidebar-link" data-preview-title="Data Cabang" data-preview-icon="fa-store" data-preview-desc="Manajemen alamat, status operasional, dan info toko seluruh cabang."><i class="fa-solid fa-store"></i> Data Cabang</a>
            <a href="laporan_global.php" class="sidebar-link" data-preview-title="Laporan Global" data-preview-icon="fa-file-contract" data-preview-desc="Analisis finansial menyeluruh dan unduh laporan performa rental bulanan."><i class="fa-solid fa-file-contract"></i> Laporan Global</a>
            <a href="kelola_laporan_masalah.php" class="sidebar-link" data-preview-title="Laporan Bug" data-preview-icon="fa-bug" data-preview-desc="Cek laporan kendala atau bug dari user maupun admin cabang."><i class="fa-solid fa-bug"></i> Laporan Bug</a>
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
                <h4 class="m-0 text-white fw-light">System <span class="fw-bold">Overview</span></h4>
            </div>
            <div class="d-flex align-items-center">
                <span class="text-white me-3 d-none d-sm-block"><?= htmlspecialchars($_SESSION['superadmin_name']) ?></span>
                <div class="position-relative">
                    <i class="fa-solid fa-bell text-white fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                </div>
            </div>
        </header>

        <!-- Content Body -->
        <div class="p-4 p-md-5">
            
            <!-- Statistics -->
            <div class="row g-4 mb-5">
                <?php foreach($stats as $s): ?>
                <div class="col-sm-6 col-xl-3">
                    <div class="glass-card stat-card-<?= $s['color'] ?> p-4 h-100 float-hover">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-light mb-1 text-uppercase small fw-bold"><?= $s['title'] ?></p>
                                <h3 class="text-white fw-bold m-0"><?= $s['value'] ?></h3>
                            </div>
                            <div class="fs-1 neon-text-<?= $s['color'] ?> opacity-75">
                                <i class="fa-solid <?= $s['icon'] ?>"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Recent Activity Table Dummy -->
            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="text-white m-0">
                        <i class="fa-solid fa-bolt me-2 neon-text-blue"></i>
                        <?= $view_all ? 'Semua Aktivitas Booking' : '5 Aktivitas Booking Terkini' ?>
                    </h5>
                    <?php if($view_all): ?>
                        <a href="dashboard_superadmin.php" class="btn btn-sm btn-outline-secondary text-white">View Less</a>
                    <?php else: ?>
                        <a href="?view=all" class="btn btn-sm btn-outline-secondary text-white">View All</a>
                    <?php endif; ?>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-dark table-hover table-borderless align-middle" style="background: transparent;">
                        <thead style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                            <tr>
                                <th>Cabang</th>
                                <th>Action</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($recent_activities)): ?>
                            <tr><td colspan="4" class="text-center text-muted">Belum ada aktivitas.</td></tr>
                            <?php else: ?>
                                <?php foreach($recent_activities as $act): 
                                    $status_color = 'neon-green';
                                    if($act['status'] == 'pending') $status_color = 'neon-yellow';
                                    if($act['status'] == 'selesai') $status_color = 'secondary';
                                    if($act['status'] == 'pause') $status_color = 'neon-red';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($act['nama_rental']) ?></td>
                                    <td>Sewa <?= htmlspecialchars($act['nama_console']) ?></td>
                                    <td><span class="badge badge-<?= $status_color ?> text-uppercase"><?= $act['status'] ?></span></td>
                                    <td class="text-light"><?= date('d/m/Y', strtotime($act['tanggal'])) ?> <?= substr($act['jam_mulai'],0,5) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Global Console View -->
            <div class="mt-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="text-white m-0 fw-bold"><i class="fa-solid fa-gamepad me-2 neon-text-purple"></i>Console Live Monitoring</h4>
                </div>
                
                <?php if(empty($global_consoles)): ?>
                    <div class="text-center text-muted py-4">Belum ada data cabang dan console.</div>
                <?php else: ?>
                    <?php foreach($global_consoles as $branch): ?>
                        <div class="mb-5">
                            <h5 class="text-white mb-3 border-bottom border-secondary pb-2">
                                <i class="fa-solid fa-store text-info me-2"></i> Cabang: <?= htmlspecialchars($branch['nama_rental']) ?>
                                <?php if(($branch['status_toko'] ?? 'Buka') == 'Tutup'): ?>
                                    <span class="badge bg-danger ms-2"><i class="fa-solid fa-door-closed me-1"></i>Tutup</span>
                                <?php endif; ?>
                            </h5>
                            
                            <div class="row g-4">
                                <?php if(empty($branch['consoles'])): ?>
                                    <div class="col-12 text-muted">Tidak ada console di cabang ini.</div>
                                <?php else: ?>
                                    <?php foreach($branch['consoles'] as $ps): 
                                        $status_real = strtolower($ps['status_real']);
                                        $badge_color = 'bg-secondary';
                                        $icon = 'fa-gamepad';
                                        $is_closed = ($branch['status_toko'] ?? 'Buka') == 'Tutup';
                                        
                                        if($is_closed) {
                                            $status_real = 'terkunci';
                                            $badge_color = 'badge-neon-red';
                                            $icon = 'fa-lock';
                                        } else {
                                            if($status_real == 'tersedia') {
                                                $badge_color = 'badge-neon-green';
                                                $icon = 'fa-check-circle';
                                            } elseif($status_real == 'sedang dimainkan' || $status_real == 'aktif') {
                                                $badge_color = 'badge-neon-red';
                                                $icon = 'fa-play-circle';
                                            } elseif($status_real == 'pause') {
                                                $badge_color = 'badge-neon-yellow text-dark';
                                                $icon = 'fa-pause-circle';
                                            } elseif($status_real == 'pending') {
                                                $badge_color = 'badge-neon-yellow text-dark';
                                                $icon = 'fa-clock';
                                            } elseif($status_real == 'rusak') {
                                                $badge_color = 'badge-neon-red';
                                                $icon = 'fa-triangle-exclamation';
                                            }
                                        }
                                    ?>
                                    <div class="col-md-6 col-lg-4 col-xl-3">
                                        <div class="glass-card h-100 float-hover">
                                            <div class="position-relative">
                                                <img src="<?= htmlspecialchars($ps['foto']) ?>" alt="<?= htmlspecialchars($ps['nama_console']) ?>" class="w-100" style="height: 180px; object-fit: cover; border-top-left-radius: 16px; border-top-right-radius: 16px;">
                                                <span class="badge <?= $badge_color ?> position-absolute top-0 end-0 m-2 px-3 py-2 rounded-pill text-uppercase" style="font-size:0.7rem; letter-spacing: 0.5px;">
                                                    <i class="fa-solid <?= $icon ?> me-1"></i> <?= $status_real ?>
                                                </span>
                                            </div>
                                            <div class="p-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="text-white m-0 fw-bold"><?= htmlspecialchars($ps['nama_console']) ?></h6>
                                                    <span class="badge bg-dark border border-secondary" style="font-size:0.7em;"><?= htmlspecialchars($ps['tipe']) ?></span>
                                                </div>
                                                
                                                <?php if($ps['timer'] !== ""): ?>
                                                    <div class="text-center p-1 rounded mt-2" style="background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.05);">
                                                        <?php if($ps['status_real'] == 'sedang dimainkan'): ?>
                                                            <div class="m-0 neon-text-red fw-bold timer-aktif" style="font-family: monospace; font-size: 1.1rem;" data-endtime="<?= $ps['end_timestamp'] ?>"><?= $ps['timer'] ?></div>
                                                        <?php else: ?>
                                                            <div class="m-0 neon-text-yellow blink-text fw-bold" style="font-family: monospace; font-size: 1.1rem;"><?= $ps['timer'] ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Optimized Timer Loop for Super Admin
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
