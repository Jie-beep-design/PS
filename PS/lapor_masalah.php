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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['delete_id'])) {
        $del_id = (int)$_POST['delete_id'];
        mysqli_query($conn, "DELETE FROM laporan_bug WHERE id=$del_id AND admin_cabang_id='$admin_cabang_id'");
        $message = "Satu laporan berhasil dihapus.";
        $message_type = "success";
    } elseif(isset($_POST['reset_all'])) {
        mysqli_query($conn, "DELETE FROM laporan_bug WHERE admin_cabang_id='$admin_cabang_id'");
        $message = "Semua riwayat laporan Anda berhasil dibersihkan.";
        $message_type = "success";
    } elseif(isset($_POST['kategori_masalah'])) {
        $kategori = mysqli_real_escape_string($conn, $_POST['kategori_masalah']);
        $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
        
        $query = "INSERT INTO laporan_bug (admin_cabang_id, kategori_masalah, deskripsi) VALUES ('$admin_cabang_id', '$kategori', '$deskripsi')";
        if (mysqli_query($conn, $query)) {
            $message = "Laporan berhasil dikirim ke Super Admin.";
            $message_type = "success";
        } else {
            $message = "Gagal mengirim laporan: " . mysqli_error($conn);
            $message_type = "danger";
        }
    }
}

// Ambil riwayat laporan
$riwayat = [];
$res_riwayat = mysqli_query($conn, "SELECT * FROM laporan_bug WHERE admin_cabang_id = '$admin_cabang_id' ORDER BY id DESC");
if ($res_riwayat) {
    while($r = mysqli_fetch_assoc($res_riwayat)) {
        $riwayat[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lapor Masalah - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assest/css/style.css?v=<?= time() ?>">
    <style>
        .sidebar-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; position: fixed; height: 100vh; overflow-y: auto; padding: 1.5rem; z-index: 1000; }
        .main-content { margin-left: 280px; flex-grow: 1; display: flex; flex-direction: column; }
        .topbar { padding: 1rem 2rem; }
        .glass-table th { background: rgba(0,0,0,0.6); color: #00f3ff; border-bottom: 1px solid rgba(0, 243, 255, 0.3); }
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
            <a href="penyewaan.php" class="sidebar-link" data-preview-title="Penyewaan" data-preview-icon="fa-file-invoice-dollar" data-preview-desc="Pantau seluruh transaksi aktif, riwayat booking, dan approval pembayaran.">><i class="fa-solid fa-file-invoice-dollar"></i> Penyewaan</a>
            <a href="laporan.php" class="sidebar-link" data-preview-title="Laporan" data-preview-icon="fa-chart-line" data-preview-desc="Laporan pendapatan finansial detail harian dan bulanan cabang.">><i class="fa-solid fa-chart-line"></i> Laporan</a>
            <a href="pengaturan_pembayaran.php" class="sidebar-link" data-preview-title="Metode Pembayaran" data-preview-icon="fa-qrcode" data-preview-desc="Atur informasi rekening dan e-wallet QRIS untuk menerima pembayaran.">><i class="fa-solid fa-qrcode"></i> Metode Pembayaran</a>
            <a href="pengaturan_toko.php" class="sidebar-link" data-preview-title="Pengaturan Toko" data-preview-icon="fa-store" data-preview-desc="Kelola status buka/tutup toko, jam operasional, dan keterangan cabang.">><i class="fa-solid fa-store"></i> Pengaturan Toko</a>
            <a href="lapor_masalah.php" class="sidebar-link active"><i class="fa-solid fa-headset"></i> Lapor Masalah</a>
        </nav>
        <div class="mt-auto">
            <hr class="border-secondary">
            <a href="admin_logout.php" class="sidebar-link text-danger"><i class="fa-solid fa-power-off"></i> System Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="glass-navbar topbar d-flex justify-content-between align-items-center sticky-top">
            <div class="d-flex align-items-center">
                <h4 class="m-0 text-white"><i class="fa-solid fa-headset me-2 neon-text-blue"></i> Lapor Masalah</h4>
            </div>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-sm-block">
                    <p class="m-0 text-white fw-bold"><?= htmlspecialchars($_SESSION['admin_name']) ?></p>
                </div>
            </div>
        </header>

        <div class="p-4 p-md-5">
            <?php if($message): ?>
                <div class="alert alert-<?= $message_type ?> bg-<?= $message_type == 'success' ? 'success' : 'danger' ?> bg-opacity-25 text-white border-<?= $message_type ?> mb-4">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-md-5">
                    <div class="glass-card p-4 h-100">
                        <h5 class="text-white mb-4">Kirim Laporan Bug / Masalah</h5>
                        <form action="lapor_masalah.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label text-light">Kategori Masalah</label>
                                <select name="kategori_masalah" class="form-select form-control-glass text-white" required>
                                    <option class="text-dark" value="">Pilih Kategori</option>
                                    <option class="text-dark" value="Bug Sistem">Bug Sistem / Error</option>
                                    <option class="text-dark" value="Masalah Data">Masalah Data (Console/Sewa)</option>
                                    <option class="text-dark" value="Akun">Akun / Login</option>
                                    <option class="text-dark" value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-light">Deskripsi Masalah</label>
                                <textarea name="deskripsi" class="form-control form-control-glass text-white" rows="4" placeholder="Jelaskan masalah yang Anda hadapi secara detail..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-neon w-100"><i class="fa-solid fa-paper-plane me-2"></i> Kirim Laporan</button>
                        </form>
                    </div>
                </div>
                
                <div class="col-md-7">
                    <div class="glass-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="text-white m-0">Riwayat Laporan Saya</h5>
                            <form method="POST" class="m-0" onsubmit="event.preventDefault(); Swal.fire({title: 'Konfirmasi', text: 'Yakin ingin menghapus semua riwayat laporan Anda?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#00f3ff', cancelButtonColor: '#bc13fe', confirmButtonText: 'Ya, Lanjutkan', cancelButtonText: 'Batal', background: '#0f172a', color: '#fff'}).then((result) => { if (result.isConfirmed) this.submit(); });">
                                <button type="submit" name="reset_all" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash-can me-1"></i> Bersihkan</button>
                            </form>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover table-borderless align-middle" style="background: transparent;">
                                <thead>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                        <th>Tanggal</th>
                                        <th>Kategori</th>
                                        <th>Status</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($riwayat)): ?>
                                        <tr><td colspan="3" class="text-center text-muted py-3">Belum ada riwayat laporan.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($riwayat as $r): 
                                            $badge_class = 'bg-secondary';
                                            if($r['status'] == 'Diproses') $badge_class = 'bg-warning text-dark';
                                            if($r['status'] == 'Selesai') $badge_class = 'bg-success';
                                        ?>
                                        <tr>
                                            <td class="text-light"><small><?= date('d/m/Y H:i', strtotime($r['tanggal_laporan'])) ?></small></td>
                                            <td><?= htmlspecialchars($r['kategori_masalah']) ?></td>
                                            <td><span class="badge <?= $badge_class ?>"><?= $r['status'] ?></span></td>
                                            <td class="text-end">
                                                <form method="POST" class="d-inline" onsubmit="event.preventDefault(); Swal.fire({title: 'Konfirmasi', text: 'Hapus laporan ini?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#00f3ff', cancelButtonColor: '#bc13fe', confirmButtonText: 'Ya, Lanjutkan', cancelButtonText: 'Batal', background: '#0f172a', color: '#fff'}).then((result) => { if (result.isConfirmed) this.submit(); });">
                                                    <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-xmark"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
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
