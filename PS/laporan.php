<?php
session_start();
include 'config/db.php';

if(!isset($_SESSION['admin_name']) || !isset($_SESSION['admin_id'])){
    header("Location: admin_login.php");
    exit();
}

$admin_cabang_id = $_SESSION['admin_id'];
$bulan_ini = isset($_GET['bulan']) ? str_pad($_GET['bulan'], 2, '0', STR_PAD_LEFT) : date('m');
$tahun_ini = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

$nama_bulan_arr = ["01" => "Januari", "02" => "Februari", "03" => "Maret", "04" => "April", "05" => "Mei", "06" => "Juni", "07" => "Juli", "08" => "Agustus", "09" => "September", "10" => "Oktober", "11" => "November", "12" => "Desember"];
$bulan_text = $nama_bulan_arr[$bulan_ini] ?? $bulan_ini;

// 1. Total Consoles
$q_console = "SELECT COUNT(id) as total FROM consoles WHERE admin_cabang_id = '$admin_cabang_id'";
$res_console = mysqli_query($conn, $q_console);
$total_consoles = mysqli_fetch_assoc($res_console)['total'] ?? 0;

// 2. Total Unique Users this month
$q_users = "
    SELECT COUNT(DISTINCT b.user_id) as total 
    FROM bookings b 
    JOIN consoles c ON b.console_id = c.id 
    WHERE c.admin_cabang_id = '$admin_cabang_id' 
    AND MONTH(b.tanggal) = '$bulan_ini' 
    AND YEAR(b.tanggal) = '$tahun_ini'
";
$res_users = mysqli_query($conn, $q_users);
$total_users = mysqli_fetch_assoc($res_users)['total'] ?? 0;

// 3. Total Nominal / Pendapatan this month (using CEIL of hours diff * harga_per_jam)
// Assuming only counting 'selesai' and 'aktif'/'pause' or just all bookings? Usually all that are not cancelled.
$q_income = "
    SELECT SUM(
        CEIL(TIME_TO_SEC(TIMEDIFF(b.jam_selesai, b.jam_mulai)) / 3600) * c.harga_per_jam
    ) as total_income
    FROM bookings b 
    JOIN consoles c ON b.console_id = c.id 
    WHERE c.admin_cabang_id = '$admin_cabang_id' 
    AND MONTH(b.tanggal) = '$bulan_ini' 
    AND YEAR(b.tanggal) = '$tahun_ini'
    AND b.status_pembayaran = 'sudah bayar'
";
$res_income = mysqli_query($conn, $q_income);
$total_income = mysqli_fetch_assoc($res_income)['total_income'] ?? 0;

// Detail Data (Bookings this month)
$laporan = [];
$q_detail = "
    SELECT 
        b.tanggal,
        COALESCE(b.nama_pelanggan, u.nama, 'Guest') AS nama_user,
        b.no_telp,
        c.nama_console,
        c.harga_per_jam,
        b.jam_mulai,
        b.jam_selesai,
        CEIL(TIME_TO_SEC(TIMEDIFF(b.jam_selesai, b.jam_mulai)) / 3600) as jam_main,
        b.status_pembayaran,
        b.bukti_pembayaran
    FROM bookings b
    JOIN consoles c ON b.console_id = c.id
    LEFT JOIN users u ON b.user_id = u.id
    WHERE c.admin_cabang_id = '$admin_cabang_id'
    AND MONTH(b.tanggal) = '$bulan_ini' 
    AND YEAR(b.tanggal) = '$tahun_ini'
    ORDER BY b.tanggal DESC
";
$res_detail = mysqli_query($conn, $q_detail);
if($res_detail){
    while($row = mysqli_fetch_assoc($res_detail)){
        $laporan[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - RumahPS</title>
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
        
        .stat-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(90deg, #00f3ff, #8b5cf6);
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; width: 100%; }
        }
        
        @media print {
            body { background: white !important; color: black !important; }
            .sidebar, .topbar, .filter-section, .btn { display: none !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            .glass-table { background: transparent !important; border: none !important; }
            table { border-collapse: collapse !important; width: 100% !important; }
            th, td { border: 1px solid #ccc !important; color: black !important; padding: 8px !important; }
            th { background: #f0f0f0 !important; }
            .stat-card { background: transparent !important; border: 1px solid #ccc !important; }
            .text-white, .text-gray-400, .neon-text-green { color: black !important; }
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
            <a href="penyewaan.php" class="sidebar-link" data-preview-title="Penyewaan" data-preview-icon="fa-file-invoice-dollar" data-preview-desc="Pantau seluruh transaksi aktif, riwayat booking, dan approval pembayaran.">><i class="fa-solid fa-file-invoice-dollar"></i> Penyewaan</a>
            <a href="laporan.php" class="sidebar-link active" data-preview-title="Laporan" data-preview-icon="fa-chart-line" data-preview-desc="Laporan pendapatan finansial detail harian dan bulanan cabang.">><i class="fa-solid fa-chart-line"></i> Laporan</a>
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
                <h4 class="m-0 text-white"><i class="fa-solid fa-chart-line me-2 neon-text-green"></i> Laporan Bulanan</h4>
            </div>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-sm-block">
                    <p class="m-0 text-white fw-bold"><?= htmlspecialchars($_SESSION['admin_name']) ?></p>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['admin_name']) ?>&background=8b5cf6&color=fff&rounded=true" alt="Admin" width="40" height="40" class="rounded-circle border border-info">
            </div>
        </header>

        <div class="p-4 p-md-5">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 filter-section gap-3">
                <h5 class="text-white m-0">Statistik Bulan: <span class="neon-text-blue"><?= $bulan_text ?> <?= $tahun_ini ?></span></h5>
                
                <form action="laporan.php" method="GET" class="d-flex gap-2 align-items-center">
                    <select name="bulan" class="form-select form-select-sm bg-dark text-white border-secondary">
                        <?php foreach($nama_bulan_arr as $num => $name): ?>
                            <option value="<?= $num ?>" <?= $bulan_ini == $num ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="tahun" class="form-select form-select-sm bg-dark text-white border-secondary">
                        <?php 
                        $thn_sekarang = date('Y');
                        for($i = $thn_sekarang - 2; $i <= $thn_sekarang; $i++): 
                        ?>
                            <option value="<?= $i ?>" <?= $tahun_ini == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-neon"><i class="fa-solid fa-filter"></i></button>
                </form>
            </div>
            
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-gray-400 mb-1">Total Console</p>
                                <h2 class="text-white m-0"><?= number_format($total_consoles) ?></h2>
                            </div>
                            <div class="stat-icon text-info"><i class="fa-solid fa-gamepad"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-gray-400 mb-1">User Aktif Menyewa</p>
                                <h2 class="text-white m-0"><?= number_format($total_users) ?></h2>
                            </div>
                            <div class="stat-icon text-purple"><i class="fa-solid fa-users"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-gray-400 mb-1">Total Pendapatan</p>
                                <h2 class="neon-text-green m-0">Rp <?= number_format($total_income, 0, ',', '.') ?></h2>
                            </div>
                            <div class="stat-icon text-success"><i class="fa-solid fa-wallet"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 mt-5 filter-section flex-wrap gap-3">
                <h5 class="text-white m-0">Rincian Penyewaan</h5>
                <div>
                    <button onclick="window.print()" class="btn btn-sm btn-outline-info me-2"><i class="fa-solid fa-print me-1"></i> Cetak PDF/Print</button>
                    <button onclick="exportTableToExcel('laporanTable', 'Laporan_<?= $bulan_text ?>_<?= $tahun_ini ?>')" class="btn btn-sm btn-neon-green"><i class="fa-solid fa-file-excel me-1"></i> Cetak Excel</button>
                </div>
            </div>
            <div class="table-responsive glass-table p-3">
                <table id="laporanTable" class="table table-dark table-borderless table-hover mb-0" style="background: transparent;">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>User</th>
                            <th>No. Telp</th>
                            <th>Console</th>
                            <th>Waktu (Jam)</th>
                            <th>Pendapatan</th>
                            <th>Status Bayar</th>
                            <th>Bukti</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($laporan)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data di bulan ini.</td></tr>
                        <?php endif; ?>
                        <?php foreach($laporan as $l): 
                            $jam = max(1, (int)$l['jam_main']);
                            $harga = $jam * $l['harga_per_jam'];
                            $bayar_class = $l['status_pembayaran'] == 'sudah bayar' ? 'text-success' : 'text-danger';
                        ?>
                        <tr>
                            <td><i class="fa-regular fa-calendar me-2 text-info"></i><?= date('d/m/Y', strtotime($l['tanggal'])) ?></td>
                            <td><?= htmlspecialchars($l['nama_user'] ?? 'Guest') ?></td>
                            <td><?= htmlspecialchars($l['no_telp'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($l['nama_console']) ?></td>
                            <td><?= $jam ?> Jam <small class="text-light ms-1"><i class="fa-regular fa-clock text-warning me-1"></i><?= substr($l['jam_mulai'],0,5) ?> - <?= substr($l['jam_selesai'],0,5) ?></small></td>
                            <td class="fw-bold">Rp <?= number_format($harga, 0, ',', '.') ?></td>
                            <td class="<?= $bayar_class ?> text-uppercase"><small><?= $l['status_pembayaran'] ?></small></td>
                            <td>
                                <?php if($l['bukti_pembayaran'] === 'CASH'): ?>
                                    <span class="badge bg-success" style="font-size: 0.8rem; border: 2px solid #000; box-shadow: 2px 2px 0 #000;"><i class="fa-solid fa-money-bill-wave me-1"></i> CASH</span>
                                <?php elseif(!empty($l['bukti_pembayaran'])): ?>
                                    <a href="<?= htmlspecialchars($l['bukti_pembayaran']) ?>" target="_blank" class="btn btn-sm btn-outline-info"><i class="fa-solid fa-image me-1"></i> Lihat</a>
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
<script>
function exportTableToExcel(tableID, filename = ''){
    var downloadLink;
    var dataType = 'application/vnd.ms-excel';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
    
    filename = filename?filename+'.xls':'excel_data.xls';
    
    downloadLink = document.createElement("a");
    document.body.appendChild(downloadLink);
    
    if(navigator.msSaveOrOpenBlob){
        var blob = new Blob(['\ufeff', tableHTML], {
            type: dataType
        });
        navigator.msSaveOrOpenBlob( blob, filename);
    }else{
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
        downloadLink.download = filename;
        downloadLink.click();
    }
}
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
