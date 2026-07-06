<?php
session_start();
if(!isset($_SESSION['superadmin_name'])) {
    header("Location: login_superadmin.php");
    exit();
}

include 'config/db.php';

$message = '';
$message_type = '';

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['delete_id'])) {
        $del_id = (int)$_POST['delete_id'];
        mysqli_query($conn, "DELETE FROM laporan_bug WHERE id=$del_id");
        $message = "Satu laporan berhasil dihapus.";
        $message_type = "success";
    } elseif(isset($_POST['reset_all'])) {
        mysqli_query($conn, "TRUNCATE TABLE laporan_bug");
        $message = "Semua laporan berhasil dibersihkan.";
        $message_type = "success";
    } elseif(isset($_POST['status']) && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        
        $query = "UPDATE laporan_bug SET status = '$status' WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            $message = "Status laporan berhasil diperbarui.";
            $message_type = "success";
        } else {
            $message = "Gagal memperbarui status.";
            $message_type = "danger";
        }
    }
}

// Fetch all reports
$laporan = [];
$res = mysqli_query($conn, "SELECT l.*, a.nama_rental, a.no_telp FROM laporan_bug l JOIN admin_cabang a ON l.admin_cabang_id = a.id ORDER BY l.id DESC");
if ($res) {
    while($row = mysqli_fetch_assoc($res)) {
        $laporan[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Laporan - Super Admin</title>
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
            <a href="laporan_global.php" class="sidebar-link" data-preview-title="Laporan Global" data-preview-icon="fa-file-contract" data-preview-desc="Analisis finansial menyeluruh dan unduh laporan performa rental bulanan.">><i class="fa-solid fa-file-contract"></i> Laporan Global</a>
            <a href="kelola_laporan_masalah.php" class="sidebar-link active" data-preview-title="Laporan Bug" data-preview-icon="fa-bug" data-preview-desc="Cek laporan kendala atau bug dari user maupun admin cabang.">><i class="fa-solid fa-bug"></i> Laporan Bug</a>
        </nav>
        
        <div class="mt-auto">
            <hr class="border-secondary">
            <a href="logout_superadmin.php" class="sidebar-link text-danger"><i class="fa-solid fa-power-off"></i> Terminate Session</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="glass-navbar topbar d-flex justify-content-between align-items-center sticky-top">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-light d-md-none me-3"><i class="fa-solid fa-bars"></i></button>
                <h4 class="m-0 text-white fw-light">Kelola <span class="fw-bold">Laporan Bug</span></h4>
            </div>
            <div class="d-flex align-items-center">
                <span class="text-white me-3 d-none d-sm-block"><?= htmlspecialchars($_SESSION['superadmin_name']) ?></span>
            </div>
        </header>

        <div class="p-4 p-md-5">
            <?php if($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show bg-<?= $message_type == 'success' ? 'success' : 'danger' ?> bg-opacity-25 text-white border-<?= $message_type ?>">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="text-white m-0"><i class="fa-solid fa-list me-2 neon-text-purple"></i>Daftar Laporan Masalah</h5>
                    <form method="POST" onsubmit="event.preventDefault(); Swal.fire({title: 'Konfirmasi', text: 'PERINGATAN: Yakin ingin menghapus SEMUA data laporan bug dari sistem? Data yang dihapus tidak dapat dikembalikan.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#00f3ff', cancelButtonColor: '#bc13fe', confirmButtonText: 'Ya, Lanjutkan', cancelButtonText: 'Batal', background: '#0f172a', color: '#fff'}).then((result) => { if (result.isConfirmed) this.submit(); });">
                        <button type="submit" name="reset_all" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash-can me-1"></i> Bersihkan Semua</button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle" style="background: transparent;">
                        <thead>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <th>Tanggal</th>
                                <th>Cabang</th>
                                <th>Kontak WA</th>
                                <th>Kategori</th>
                                <th>Deskripsi</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($laporan)): ?>
                                <tr><td colspan="6" class="text-center text-muted">Belum ada laporan masalah dari cabang.</td></tr>
                            <?php else: ?>
                                <?php foreach($laporan as $l): 
                                    $badge_class = 'bg-secondary';
                                    if($l['status'] == 'Diproses') $badge_class = 'bg-warning text-dark';
                                    if($l['status'] == 'Selesai') $badge_class = 'bg-success';
                                ?>
                                <tr>
                                    <td><small><?= date('d/m/Y H:i', strtotime($l['tanggal_laporan'])) ?></small></td>
                                    <td class="text-info"><?= htmlspecialchars($l['nama_rental']) ?></td>
                                    <td>
                                        <?php if($l['no_telp']): ?>
                                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $l['no_telp']) ?>" target="_blank" class="btn btn-sm btn-outline-success border-0 px-1 py-0"><i class="fa-brands fa-whatsapp"></i> <?= htmlspecialchars($l['no_telp']) ?></a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($l['kategori_masalah']) ?></td>
                                    <td><small><?= htmlspecialchars($l['deskripsi']) ?></small></td>
                                    <td><span class="badge <?= $badge_class ?>"><?= $l['status'] ?></span></td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            <form action="" method="POST" class="d-flex align-items-center gap-2 m-0">
                                                <input type="hidden" name="id" value="<?= $l['id'] ?>">
                                                <select name="status" class="form-select form-select-sm" style="width: 110px; background: rgba(0,0,0,0.5); color: white;">
                                                    <option value="Menunggu" <?= $l['status'] == 'Menunggu' ? 'selected' : '' ?>>Menunggu</option>
                                                    <option value="Diproses" <?= $l['status'] == 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                                                    <option value="Selesai" <?= $l['status'] == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-outline-info">Update</button>
                                            </form>
                                            <form action="" method="POST" class="m-0" onsubmit="event.preventDefault(); Swal.fire({title: 'Konfirmasi', text: 'Yakin hapus laporan ini?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#00f3ff', cancelButtonColor: '#bc13fe', confirmButtonText: 'Ya, Lanjutkan', cancelButtonText: 'Batal', background: '#0f172a', color: '#fff'}).then((result) => { if (result.isConfirmed) this.submit(); });">
                                                <input type="hidden" name="delete_id" value="<?= $l['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
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
