<?php
session_start();
include 'config/db.php';

if(!isset($_SESSION['admin_name']) || !isset($_SESSION['admin_id'])){
    header("Location: admin_login.php");
    exit();
}

$admin_cabang_id = $_SESSION['admin_id'];
$action_msg = '';
$action_type = '';

// Handle Hapus
if (isset($_GET['hapus_id'])) {
    $id_hapus = mysqli_real_escape_string($conn, $_GET['hapus_id']);
    $check = mysqli_query($conn, "SELECT id FROM consoles WHERE id = '$id_hapus' AND admin_cabang_id = '$admin_cabang_id'");
    if(mysqli_num_rows($check) > 0) {
        if(mysqli_query($conn, "DELETE FROM consoles WHERE id = '$id_hapus'")) {
            $action_msg = "Console berhasil dihapus!";
            $action_type = "success";
        }
    }
}

// Handle Edit console
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_console'])) {
    $id_edit = mysqli_real_escape_string($conn, $_POST['id_console']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_console']);
    $harga = (int) $_POST['harga_per_jam'];
    $tipe = mysqli_real_escape_string($conn, $_POST['tipe']);
    
    $update_query = "UPDATE consoles SET nama_console='$nama', harga_per_jam='$harga', tipe='$tipe' ";
    
    // Handle file upload
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $filename = time() . '_' . basename($_FILES["foto"]["name"]);
        $target_file = $target_dir . $filename;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($imageFileType, $allowed)) {
            if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
                $update_query .= ", foto='$target_file' ";
            }
        }
    }
    $update_query .= " WHERE id='$id_edit' AND admin_cabang_id='$admin_cabang_id'";
    
    if (mysqli_query($conn, $update_query)) {
        $action_msg = "Console berhasil diupdate!";
        $action_type = "success";
    } else {
        $action_msg = "Gagal mengupdate console.";
        $action_type = "danger";
    }
}

// Handle upload console
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_console'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_console']);
    $harga = (int) $_POST['harga_per_jam'];
    $tipe = mysqli_real_escape_string($conn, $_POST['tipe']);
    $foto_path = 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=500&q=80'; // fallback
    
    // Handle file upload
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $filename = time() . '_' . basename($_FILES["foto"]["name"]);
        $target_file = $target_dir . $filename;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($imageFileType, $allowed)) {
            if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
                $foto_path = $target_file;
            } else {
                $action_msg = "Gagal mengupload foto.";
                $action_type = "danger";
            }
        } else {
            $action_msg = "Format foto tidak didukung (hanya JPG, PNG, WEBP).";
            $action_type = "danger";
        }
    }

    if (!$action_msg) {
        // GET admin lokasi if needed or just use admin_cabang_id. We will just use admin_cabang_id.
        $query_admin = "SELECT lokasi FROM admin_cabang WHERE id = '$admin_cabang_id'";
        $res_admin = mysqli_query($conn, $query_admin);
        $lokasi = mysqli_fetch_assoc($res_admin)['lokasi'] ?? '';

        $insert = "INSERT INTO consoles (admin_cabang_id, nama_console, harga_per_jam, foto, status, lokasi, tipe) 
                   VALUES ('$admin_cabang_id', '$nama', '$harga', '$foto_path', 'tersedia', '$lokasi', '$tipe')";
        
        if (mysqli_query($conn, $insert)) {
            $action_msg = "Console berhasil ditambahkan!";
            $action_type = "success";
        } else {
            $action_msg = "Gagal menyimpan ke database.";
            $action_type = "danger";
        }
    }
}

$edit_data = null;
if (isset($_GET['edit_id'])) {
    $id_e = mysqli_real_escape_string($conn, $_GET['edit_id']);
    $res_e = mysqli_query($conn, "SELECT * FROM consoles WHERE id = '$id_e' AND admin_cabang_id = '$admin_cabang_id'");
    $edit_data = mysqli_fetch_assoc($res_e);
}

// Fetch current consoles
$consoles = [];
$query = "SELECT * FROM consoles WHERE admin_cabang_id = '$admin_cabang_id' ORDER BY id DESC";
$result = mysqli_query($conn, $query);
if ($result) {
    while($row = mysqli_fetch_assoc($result)){
        $consoles[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Console - RumahPS</title>
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
            <a href="data_console.php" class="sidebar-link active" data-preview-title="Data Console" data-preview-icon="fa-desktop" data-preview-desc="Manajemen daftar console PS, tambah, edit, atau hapus perangkat.">><i class="fa-solid fa-desktop"></i> Data Console</a>
            <a href="penyewaan.php" class="sidebar-link" data-preview-title="Penyewaan" data-preview-icon="fa-file-invoice-dollar" data-preview-desc="Pantau seluruh transaksi aktif, riwayat booking, dan approval pembayaran.">><i class="fa-solid fa-file-invoice-dollar"></i> Penyewaan</a>
            <a href="laporan.php" class="sidebar-link" data-preview-title="Laporan" data-preview-icon="fa-chart-line" data-preview-desc="Laporan pendapatan finansial detail harian dan bulanan cabang.">><i class="fa-solid fa-chart-line"></i> Laporan</a>
            <a href="pengaturan_pembayaran.php" class="sidebar-link" data-preview-title="Metode Pembayaran" data-preview-icon="fa-qrcode" data-preview-desc="Atur informasi rekening dan e-wallet QRIS untuk menerima pembayaran.">><i class="fa-solid fa-qrcode"></i> Metode Pembayaran</a>
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
                <h4 class="m-0 text-white"><i class="fa-solid fa-desktop me-2 neon-text-purple"></i> Data Console</h4>
            </div>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-sm-block">
                    <p class="m-0 text-white fw-bold"><?= htmlspecialchars($_SESSION['admin_name']) ?></p>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['admin_name']) ?>&background=8b5cf6&color=fff&rounded=true" alt="Admin" width="40" height="40" class="rounded-circle border border-info">
            </div>
        </header>

        <!-- Content Body -->
        <div class="p-4 p-md-5">
            <?php if($action_msg): ?>
                <div class="alert alert-animated alert-<?= $action_type ?> bg-<?= $action_type == 'success' ? 'success' : 'danger' ?> bg-opacity-25 text-white border-<?= $action_type ?> mb-4">
                    <?= htmlspecialchars($action_msg) ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Form Tambah -->
                <div class="col-lg-4 mb-4">
                    <div class="glass-card p-4">
                        <h5 class="text-white mb-4"><i class="fa-solid fa-<?= $edit_data ? 'pen' : 'plus-circle' ?> text-info me-2"></i> <?= $edit_data ? 'Edit' : 'Tambah' ?> Console</h5>
                        <form method="POST" enctype="multipart/form-data" action="data_console.php">
                            <?php if($edit_data): ?>
                            <input type="hidden" name="edit_console" value="1">
                            <input type="hidden" name="id_console" value="<?= $edit_data['id'] ?>">
                            <?php else: ?>
                            <input type="hidden" name="tambah_console" value="1">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label class="form-label text-gray-300">Nama Console (misal: PS5 TV 1)</label>
                                <input type="text" name="nama_console" class="form-control bg-dark text-white border-secondary" required value="<?= htmlspecialchars($edit_data['nama_console'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-gray-300">Tipe (PS3/PS4/PS5)</label>
                                <select name="tipe" class="form-select bg-dark text-white border-secondary">
                                    <option value="PS3" <?= ($edit_data['tipe']??'')=='PS3'?'selected':'' ?>>PlayStation 3</option>
                                    <option value="PS4" <?= ($edit_data['tipe']??'')=='PS4'?'selected':'' ?>>PlayStation 4</option>
                                    <option value="PS5" <?= ($edit_data['tipe']??'')=='PS5'?'selected':'' ?>>PlayStation 5</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-gray-300">Harga Per Jam (Rp)</label>
                                <input type="number" name="harga_per_jam" class="form-control bg-dark text-white border-secondary" required value="<?= htmlspecialchars($edit_data['harga_per_jam'] ?? '') ?>">
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-gray-300">Upload Foto <?= $edit_data ? '(opsional)' : '' ?></label>
                                <input type="file" name="foto" accept="image/*" class="form-control bg-dark text-white border-secondary">
                            </div>
                            <button type="submit" class="btn btn-neon w-100"><i class="fa-solid fa-save"></i> <?= $edit_data ? 'Update' : 'Simpan' ?> Console</button>
                            <?php if($edit_data): ?>
                                <a href="data_console.php" class="btn btn-outline-secondary w-100 mt-2">Batal Edit</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Tabel Data -->
                <div class="col-lg-8">
                    <div class="table-responsive glass-table p-3">
                        <table class="table table-dark table-borderless table-hover mb-0" style="background: transparent;">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Foto</th>
                                    <th>Nama</th>
                                    <th>Tipe</th>
                                    <th>Harga/Jam</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($consoles)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data console yang diupload.</td></tr>
                                <?php endif; ?>
                                <?php $no=1; foreach($consoles as $c): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <img src="<?= htmlspecialchars($c['foto']) ?>" width="60" height="40" class="rounded" style="object-fit: cover;">
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($c['nama_console']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($c['tipe']) ?></span></td>
                                    <td>Rp <?= number_format($c['harga_per_jam'], 0, ',', '.') ?></td>
                                    <td>
                                        <?php if($c['status'] == 'tersedia'): ?>
                                            <span class="badge badge-neon-green">Tersedia</span>
                                        <?php elseif($c['status'] == 'digunakan'): ?>
                                            <span class="badge badge-neon-red">Digunakan</span>
                                        <?php else: ?>
                                            <span class="badge badge-neon-yellow"><?= ucfirst($c['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?edit_id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-info"><i class="fa-solid fa-pen"></i></a>
                                        <a href="?hapus_id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="event.preventDefault(); Swal.fire({title: 'Konfirmasi', text: 'Yakin ingin menghapus console ini?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#00f3ff', cancelButtonColor: '#bc13fe', confirmButtonText: 'Ya, Lanjutkan', cancelButtonText: 'Batal', background: '#0f172a', color: '#fff'}).then((result) => { if (result.isConfirmed) { if(this.tagName === 'A'){ window.location.href = this.href; } else { this.closest('form').submit(); } } });"><i class="fa-solid fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
