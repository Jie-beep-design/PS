<?php
session_start();
include 'config/db.php';

// Set Timezone UTC+7 (WIB)
date_default_timezone_set('Asia/Jakarta');

$console_id = isset($_GET['console_id']) ? (int)$_GET['console_id'] : 0;
$console_data = null;
$branch_id = 0;

if ($console_id > 0) {
    $res = mysqli_query($conn, "SELECT c.*, a.nama_rental, a.jam_buka, a.jam_tutup, a.status_toko FROM consoles c JOIN admin_cabang a ON c.admin_cabang_id = a.id WHERE c.id = $console_id");
    if ($res && mysqli_num_rows($res) > 0) {
        $console_data = mysqli_fetch_assoc($res);
        $branch_id = $console_data['admin_cabang_id'];
    } else {
        header("Location: dashboard.php");
        exit();
    }
} else {
    header("Location: dashboard.php");
    exit();
}

// Prepare 7 days data
$jadwal = [];
$today = time();
$start_date = date('Y-m-d', $today);
$end_date = date('Y-m-d', strtotime('+6 days', $today));

for ($i = 0; $i < 7; $i++) {
    $tgl = date('Y-m-d', strtotime("+$i days", $today));
    $jadwal[$tgl] = [];
}

// Fetch bookings for the next 7 days
$query_bookings = "
    SELECT tanggal, jam_mulai, jam_selesai, status 
    FROM bookings 
    WHERE console_id = $console_id 
    AND tanggal BETWEEN '$start_date' AND '$end_date'
    AND status != 'selesai'
    AND status_pembayaran = 'sudah bayar'
    ORDER BY jam_mulai ASC
";
$res_bookings = mysqli_query($conn, $query_bookings);

if ($res_bookings) {
    while ($row = mysqli_fetch_assoc($res_bookings)) {
        $b_tgl = $row['tanggal'];
        if (isset($jadwal[$b_tgl])) {
            $jadwal[$b_tgl][] = [
                'jam_mulai' => substr($row['jam_mulai'], 0, 5),
                'jam_selesai' => substr($row['jam_selesai'], 0, 5)
            ];
        }
    }
}

$current_date = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Console - RumahPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assest/css/style.css">
    <style>
        body { padding-top: 80px; }
        .jadwal-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .booked-item {
            background: rgba(255, 0, 85, 0.1);
            border: 1px solid rgba(255, 0, 85, 0.3);
            border-left: 4px solid #ff0055;
            padding: 10px 15px;
            border-radius: 8px;
            color: white;
            display: flex;
            align-items: center;
        }
    </style>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<nav class="navbar navbar-expand-lg glass-navbar fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-white text-decoration-none" href="dashboard.php?branch_id=<?= $branch_id ?>">
            <i class="fa-solid fa-arrow-left me-2"></i>Kembali
        </a>
    </div>
</nav>

<div class="container py-4">
    <div class="glass-card p-4 mb-4 d-flex align-items-center">
        <img src="<?= htmlspecialchars($console_data['foto']) ?>" width="100" height="80" class="rounded me-4" style="object-fit: cover;">
        <div>
            <h2 class="text-white fw-bold mb-1">Jadwal <?= htmlspecialchars($console_data['nama_console']) ?></h2>
            <p class="text-light m-0 mb-1"><i class="fa-solid fa-store me-2 text-danger"></i><?= htmlspecialchars($console_data['nama_rental']) ?></p>
            <span class="badge badge-neon-blue mt-1">Zona Waktu: WIB (UTC+7)</span>
            <span class="badge bg-secondary mt-1">Jam Operasional: <?= substr($console_data['jam_buka'],0,5) ?> - <?= substr($console_data['jam_tutup'],0,5) ?></span>
        </div>
    </div>

    <?php if (($console_data['status_toko'] ?? 'Buka') == 'Tutup'): ?>
        <div class="alert alert-warning text-center bg-warning bg-opacity-25 border-warning text-white py-4" style="backdrop-filter: blur(5px);">
            <i class="fa-solid fa-door-closed fs-1 mb-3 text-warning"></i>
            <h4 class="fw-bold">Cabang Sedang Tutup / Cuti</h4>
            <p class="mb-0">Mohon maaf, Anda tidak dapat melakukan sewa untuk sementara waktu.</p>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center bg-info bg-opacity-10 border-info text-white" style="backdrop-filter: blur(5px);">
            <i class="fa-solid fa-circle-info me-2 text-info"></i>
            <strong>Info Operasional:</strong> Rental ini beroperasi mulai pukul <span class="neon-text-blue fw-bold"><?= substr($console_data['jam_buka'],0,5) ?></span> hingga <span class="neon-text-blue fw-bold"><?= substr($console_data['jam_tutup'],0,5) ?></span>.<br>
            Silakan pilih waktu sewa yang berada dalam jam operasional dan pastikan tidak bentrok dengan jadwal yang sudah disewa.
        </div>

        <div class="row g-4">
            <?php foreach ($jadwal as $tgl => $bookings_hari_ini): 
                $hari_array = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                $bulan_array = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
                $time_tgl = strtotime($tgl);
                $hari_idx = date('w', $time_tgl);
                $bulan_idx = date('n', $time_tgl);
                $format_tgl = $hari_array[$hari_idx] . ', ' . date('d', $time_tgl) . ' ' . $bulan_array[$bulan_idx] . ' ' . date('Y', $time_tgl);
                $is_today = ($tgl == $current_date);
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="glass-card p-4 h-100 d-flex flex-column">
                    <h5 class="text-white mb-3 border-bottom border-secondary pb-2">
                        <i class="fa-regular fa-calendar-days me-2 neon-text-purple"></i><?= $format_tgl ?> 
                        <?= $is_today ? '<span class="badge bg-danger ms-2 fs-6">Hari Ini</span>' : '' ?>
                    </h5>
                    
                    <div class="flex-grow-1 mb-4">
                        <?php if (empty($bookings_hari_ini)): ?>
                            <div class="text-center py-4 rounded available-item" style="background: rgba(0,255,0,0.05); border: 1px dashed rgba(0,255,0,0.2);">
                                <i class="fa-regular fa-face-smile text-success fs-3 mb-2"></i>
                                <p class="text-success m-0 fw-bold">Semua jam tersedia</p>
                                <small class="text-muted">(selama jam operasional)</small>
                            </div>
                        <?php else: ?>
                            <p class="text-warning small mb-2"><i class="fa-solid fa-lock me-1"></i> Telah Disewa Pada Jam:</p>
                            <div class="jadwal-list">
                            <?php foreach ($bookings_hari_ini as $b): ?>
                                <div class="booked-item">
                                    <i class="fa-solid fa-clock me-3 fs-5 text-danger"></i>
                                    <div>
                                        <span class="fw-bold fs-5"><?= $b['jam_mulai'] ?> - <?= $b['jam_selesai'] ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <a href="form_sewa.php?console_id=<?= $console_id ?>&tanggal=<?= $tgl ?>" class="btn btn-neon w-100 mt-auto">
                        <i class="fa-regular fa-clock me-2"></i>Pilih Waktu Sewa
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
