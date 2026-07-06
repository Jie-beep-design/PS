<?php
session_start();
include 'config/db.php';

// Cek session dihapus karena sekarang guest bisa akses

$search_query = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$branch_id = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;

$branches = [];
$consoles = [];
$branch_info = null;

if ($branch_id > 0) {
    // Tampilan 2: Menampilkan console dari cabang yang dipilih
    $res_branch = mysqli_query($conn, "SELECT * FROM admin_cabang WHERE id = $branch_id");
    if ($res_branch && mysqli_num_rows($res_branch) > 0) {
        $branch_info = mysqli_fetch_assoc($res_branch);
        
        $query_consoles = "
            SELECT 
                c.*, 
                b.status AS booking_status
            FROM consoles c
            LEFT JOIN bookings b 
                ON c.id = b.console_id 
                AND b.status IN ('aktif', 'pause') 
                AND b.tanggal = CURDATE()
            WHERE c.admin_cabang_id = $branch_id
            ORDER BY c.id ASC
        ";
        
        $res_consoles = mysqli_query($conn, $query_consoles);
        if ($res_consoles) {
            while ($row = mysqli_fetch_assoc($res_consoles)) {
                // Tentukan status real
                if ($row['booking_status'] == 'aktif') {
                    $row['status'] = 'digunakan';
                } elseif ($row['booking_status'] == 'pause') {
                    $row['status'] = 'pause';
                }
                $consoles[] = $row;
            }
        }
    } else {
        // Cabang tidak ditemukan, kembali ke awal
        header("Location: dashboard.php");
        exit();
    }
} else {
    // Tampilan 1: Menampilkan list cabang rental (bisa dicari)
    $sql_branches = "SELECT * FROM admin_cabang WHERE 1=1";
    if (!empty($search_query)) {
        $sql_branches .= " AND (nama_rental LIKE '%$search_query%' OR lokasi LIKE '%$search_query%')";
    }
    $res_branches = mysqli_query($conn, $sql_branches);
    if ($res_branches) {
        while ($row = mysqli_fetch_assoc($res_branches)) {
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
    <title>Dashboard User - RumahPS</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assest/css/style.css?v=<?= time() ?>">
    <style>
        body { padding-top: 80px; }
        .ps-card-img { height: 200px; object-fit: cover; border-top-left-radius: 16px; border-top-right-radius: 16px; }
        .branch-card-img { height: 150px; object-fit: cover; border-top-left-radius: 16px; border-top-right-radius: 16px; opacity: 0.8; }
        
        /* Fix Mobile Navbar Transparency */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: rgba(15, 23, 42, 0.95);
                backdrop-filter: blur(10px);
                padding: 1rem;
                border-radius: 12px;
                margin-top: 10px;
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
        }
    </style>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg glass-navbar fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold neon-text-blue" href="dashboard.php">
            <i class="fa-solid fa-gamepad me-2"></i>Rumah<span class="neon-text-purple">PS</span>
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link text-white <?= $branch_id == 0 ? 'active' : '' ?>" href="dashboard.php">Cari Rental</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-muted" href="status_sewa.php">Status Sewa</a>
                </li>
            </ul>
            
            <div class="d-flex align-items-center me-3 mb-2 mb-lg-0">
                <button id="theme-toggle" class="btn btn-sm btn-outline-light" style="border-radius: 20px; font-weight: bold; border-width: 2px;">
                    <i id="theme-icon" class="fa-solid fa-moon"></i> <span id="theme-text" class="d-none d-lg-inline">Dark Mode</span>
                </button>
            </div>
            <div class="d-flex align-items-center dropdown">
                <a href="#" class="text-white text-decoration-none dropdown-toggle me-3" id="manajemenDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-regular fa-user-circle me-2 neon-text-blue"></i>Masuk
                </a>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark glass-card border-secondary mt-2" aria-labelledby="manajemenDropdown" style="min-width: 250px;">
                    <li><h6 class="dropdown-header text-info"><i class="fa-solid fa-shield-halved me-2"></i>Portal Manajemen</h6></li>
                    <li><hr class="dropdown-divider border-secondary"></li>
                    <li><a class="dropdown-item py-2" href="admin_login.php"><i class="fa-solid fa-user-tie me-2 text-danger"></i>Login Pengelola Rental</a></li>
                    <li><a class="dropdown-item py-2" href="login_superadmin.php"><i class="fa-solid fa-server me-2 text-warning"></i>Login Admin Sistem</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="container py-4">

    <?php if ($branch_id == 0): ?>
        <!-- TAMPILAN 1: LIST CABANG RENTAL -->
        <div class="glass-card p-4 mb-5 position-relative overflow-hidden float-idle">
            <div class="position-absolute top-0 start-0 w-100 h-100" style="background: radial-gradient(circle at center, rgba(0, 243, 255, 0.1) 0%, transparent 70%); pointer-events: none;"></div>
            
            <div class="row align-items-center position-relative" style="z-index: 1;">
                <!-- Kiri: Animasi Slide Rotasi PS -->
                <div class="col-6 col-md-3 d-flex justify-content-center order-1">
                    <div class="ps-showcase-container ps-showcase-slide large-icon" style="height: 150px; overflow: visible;">
                        <i class="fa-solid fa-gamepad ps-icon-showcase icon-1"></i>
                        <i class="fa-solid fa-vr-cardboard ps-icon-showcase icon-2"></i>
                        <i class="fa-solid fa-headset ps-icon-showcase icon-3"></i>
                    </div>
                </div>
                
                <!-- Tengah: Main Search Area -->
                <div class="col-12 col-md-6 text-center py-4 order-3 order-md-2">
                    <h1 class="display-5 fw-bold mb-3 text-white">TEMUKAN <span class="neon-text-blue">RENTALAN</span> TERDEKAT</h1>
                    <p class="lead text-muted mb-4">Rasakan pengalaman gaming tanpa gravitasi di rental pilihan Anda.</p>
                    
                    <!-- Search bar -->
                    <form action="dashboard.php" method="GET" class="row justify-content-center mb-4">
                        <div class="col-11 col-md-10">
                            <div class="input-group">
                                <span class="input-group-text input-group-text-glass"><i class="fa-solid fa-search"></i></span>
                                <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" class="form-control form-control-glass border-start-0" placeholder="Cari nama rental...">
                                <button class="btn btn-neon px-3 px-md-4" type="submit">CARI</button>
                            </div>
                        </div>
                    </form>
                    
                    <a href="status_sewa.php" class="btn btn-outline-info rounded-pill px-4 py-2"><i class="fa-solid fa-clock-rotate-left me-2"></i>Cek Status Sewa / Timer</a>
                </div>
                
                <!-- Kanan: Animasi Slide Rotasi PS -->
                <div class="col-6 col-md-3 d-flex justify-content-center order-2 order-md-3">
                    <div class="ps-showcase-container ps-showcase-slide large-icon" style="height: 150px; overflow: visible; animation-direction: reverse;">
                        <i class="fa-solid fa-headset ps-icon-showcase icon-3"></i>
                        <i class="fa-solid fa-vr-cardboard ps-icon-showcase icon-2"></i>
                        <i class="fa-solid fa-gamepad ps-icon-showcase icon-1"></i>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="mb-4 text-white border-bottom border-secondary pb-2">
            <i class="fa-solid fa-map-location-dot me-2 neon-text-purple"></i>Rekomendasi Rental Terdekat
        </h3>

        <!-- Grid Cards Cabang -->
        <div class="row g-4">
            <?php if (empty($branches)): ?>
                <div class="col-12 text-center text-muted py-5">Rental tidak ditemukan.</div>
            <?php else: ?>
                <?php foreach($branches as $b): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-card h-100 float-hover">
                        <div class="position-relative">
                            <img src="https://images.unsplash.com/photo-1542751371-adc38448a05e?w=800&q=80" class="w-100 branch-card-img" alt="Rental Store">
                            <span class="badge badge-neon-blue position-absolute top-0 end-0 m-3 px-3 py-2 rounded-pill"><i class="fa-solid fa-star me-1"></i>Verified</span>
                        </div>
                        <div class="p-4">
                            <h5 class="text-white fw-bold mb-1"><?= htmlspecialchars($b['nama_rental']) ?></h5>
                            <p class="text-light mb-4"><i class="fa-solid fa-location-dot me-2 text-danger"></i><?= htmlspecialchars($b['lokasi']) ?></p>
                            <a href="dashboard.php?branch_id=<?= $b['id'] ?>" class="btn btn-neon-purple w-100"><i class="fa-solid fa-door-open me-2"></i>Kunjungi Rental</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- TAMPILAN 2: LIST CONSOLE DALAM CABANG -->
        <?php if(($branch_info['status_toko'] ?? 'Buka') == 'Tutup'): ?>
            <div class="alert alert-warning text-center mb-4 bg-warning bg-opacity-25 border border-warning text-white p-3 rounded" style="backdrop-filter: blur(5px);">
                <i class="fa-solid fa-door-closed me-2 fs-5 text-warning"></i> <strong>Rental Sedang Tutup / Cuti</strong><br>
                <small class="mt-1 d-block"><?= !empty($branch_info['keterangan_tutup']) ? htmlspecialchars($branch_info['keterangan_tutup']) : 'Mohon maaf, rental ini sedang tidak beroperasi untuk sementara waktu.' ?></small>
            </div>
        <?php endif; ?>
        
        <div class="glass-card p-4 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="text-white fw-bold m-0"><?= htmlspecialchars($branch_info['nama_rental']) ?></h2>
                <span class="text-light"><i class="fa-solid fa-location-dot me-1 text-danger"></i> <?= htmlspecialchars($branch_info['lokasi']) ?></span>
                <a href="#" class="text-info ms-2 text-decoration-none" data-bs-toggle="modal" data-bs-target="#detailLokasiModal"><i class="fa-solid fa-circle-info me-1"></i>Detail Lokasi</a>
            </div>
            <div class="d-flex gap-2">
                <a href="status_sewa.php" class="btn btn-outline-info"><i class="fa-solid fa-clock-rotate-left me-2"></i>Cek Status Sewa</a>
                <a href="dashboard.php" class="btn btn-outline-secondary text-white"><i class="fa-solid fa-arrow-left me-2"></i>Kembali</a>
            </div>
        </div>

        <h3 class="mb-4 text-white border-bottom border-secondary pb-2"><i class="fa-solid fa-list me-2 neon-text-purple"></i>Console Tersedia</h3>

        <!-- Grid Cards Console -->
        <div class="row g-4">
            <?php if (empty($consoles)): ?>
                <div class="col-12 text-center text-muted py-5">Belum ada console di rental ini.</div>
            <?php else: ?>
                <?php foreach($consoles as $ps): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-card h-100 float-hover">
                        <div class="position-relative">
                            <img src="<?= htmlspecialchars($ps['foto']) ?>" class="w-100 ps-card-img" alt="<?= htmlspecialchars($ps['nama_console']) ?>">
                            <?php if(($branch_info['status_toko'] ?? 'Buka') == 'Tutup'): ?>
                                <span class="badge bg-danger position-absolute top-0 end-0 m-3 px-3 py-2 rounded-pill"><i class="fa-solid fa-store-slash me-1"></i>Tutup</span>
                            <?php elseif($ps['status'] == 'tersedia'): ?>
                                <span class="badge badge-neon-green position-absolute top-0 end-0 m-3 px-3 py-2 rounded-pill"><i class="fa-solid fa-check-circle me-1"></i>Tersedia</span>
                            <?php else: ?>
                                <span class="badge badge-neon-red position-absolute top-0 end-0 m-3 px-3 py-2 rounded-pill"><i class="fa-solid fa-gamepad me-1"></i><?= ucfirst($ps['status']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <h5 class="text-white fw-bold mb-2"><?= htmlspecialchars($ps['nama_console']) ?> <span class="badge bg-secondary ms-2" style="font-size: 0.7em;"><?= htmlspecialchars($ps['tipe']) ?></span></h5>
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <span class="neon-text-blue fs-5">Rp <?= number_format($ps['harga_per_jam'], 0, ',', '.') ?> <small class="text-muted fs-6">/ jam</small></span>
                            </div>
                            
                            <?php if(($branch_info['status_toko'] ?? 'Buka') == 'Tutup'): ?>
                                <button class="btn btn-outline-warning text-white w-100 disabled mb-2" style="background: rgba(255,193,7,0.1);"><i class="fa-solid fa-lock me-2"></i>Rental Sedang Tutup</button>
                                <button class="btn btn-outline-secondary w-100 disabled" style="font-size: 0.9rem; opacity: 0.5;"><i class="fa-regular fa-calendar-xmark me-2"></i>Jadwal Terkunci</button>
                            <?php else: ?>
                                <?php if($ps['status'] == 'tersedia'): ?>
                                    <a href="form_sewa.php?console_id=<?= $ps['id'] ?>" class="btn btn-neon w-100 mb-2"><i class="fa-solid fa-bolt me-2"></i>Sewa Sekarang</a>
                                <?php else: ?>
                                    <button class="btn btn-outline-secondary text-white w-100 disabled mb-2" style="background: rgba(255,255,255,0.05);"><i class="fa-solid fa-lock me-2"></i>Sedang Dimainkan</button>
                                <?php endif; ?>
                                <a href="jadwal_console.php?console_id=<?= $ps['id'] ?>" class="btn btn-outline-info w-100" style="font-size: 0.9rem;"><i class="fa-regular fa-calendar-check me-2"></i>Lihat Jadwal</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>



</div>

<?php if ($branch_id > 0): ?>
<!-- Modal Detail Lokasi -->
<div class="modal fade" id="detailLokasiModal" tabindex="-1" aria-labelledby="detailLokasiModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-card border-secondary text-white">
      <div class="modal-header border-secondary">
        <h5 class="modal-title" id="detailLokasiModalLabel"><i class="fa-solid fa-store me-2 text-info"></i>Detail Tempat Rental</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <?php if (!empty($branch_info['foto_rental'])): ?>
            <img src="<?= htmlspecialchars($branch_info['foto_rental']) ?>" alt="Foto Rental" class="img-fluid rounded mb-3" style="max-height: 250px; object-fit: cover; width: 100%;">
        <?php else: ?>
            <div class="bg-secondary rounded mb-3 d-flex align-items-center justify-content-center" style="height: 150px;">
                <span class="text-muted"><i class="fa-solid fa-image me-2"></i>Belum ada foto</span>
            </div>
        <?php endif; ?>
        
        <h4 class="fw-bold text-white mb-2"><?= htmlspecialchars($branch_info['nama_rental']) ?></h4>
        <p class="text-light mb-2"><i class="fa-solid fa-location-dot me-2 text-danger"></i> <?= htmlspecialchars($branch_info['lokasi']) ?></p>
        
        <div class="p-2 mt-3 rounded" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
            <p class="mb-0 fs-5 text-white">
                <i class="fa-brands fa-whatsapp me-2 text-success"></i> 
                <?= !empty($branch_info['no_telp']) ? htmlspecialchars($branch_info['no_telp']) : '<span class="text-muted fs-6">Belum ada nomor</span>' ?>
            </p>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

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
