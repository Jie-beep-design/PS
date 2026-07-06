<?php
session_start();
if(!isset($_SESSION['superadmin_name'])) {
    header("Location: login_superadmin.php");
    exit();
}

include 'config/db.php';

$message = '';
$message_type = '';

// Handle Delete (Permanent)
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    // MySQL cascading delete handles console & bookings deletion if foreign keys are configured properly.
    // If not, we will delete it explicitly.
    mysqli_query($conn, "DELETE FROM bookings WHERE console_id IN (SELECT id FROM consoles WHERE admin_cabang_id = $del_id)");
    mysqli_query($conn, "DELETE FROM consoles WHERE admin_cabang_id = $del_id");
    $q_del = mysqli_query($conn, "DELETE FROM admin_cabang WHERE id = $del_id");
    
    if ($q_del) {
        $message = "Admin cabang berhasil dihapus permanen beserta data konsol dan riwayat sewanya.";
        $message_type = "success";
    } else {
        $message = "Gagal menghapus admin cabang.";
        $message_type = "danger";
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $nama_rental = mysqli_real_escape_string($conn, $_POST['nama_rental']);
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $no_telp = isset($_POST['no_telp']) ? mysqli_real_escape_string($conn, $_POST['no_telp']) : '';
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Edit
        $id = (int)$_POST['id'];
        $q_update = "UPDATE admin_cabang SET username = '$username', nama_rental = '$nama_rental', lokasi = '$lokasi', no_telp = '$no_telp'";
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $q_update .= ", password = '$password'";
        }
        if (isset($_FILES['foto_rental']) && $_FILES['foto_rental']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['foto_rental']['tmp_name'];
            $name = time() . '_' . basename($_FILES['foto_rental']['name']);
            if (!is_dir("uploads")) mkdir("uploads", 0777, true);
            $target = "uploads/" . $name;
            if (move_uploaded_file($tmp_name, $target)) {
                $q_update .= ", foto_rental = '$target'";
            }
        }
        
        $q_update .= " WHERE id = $id";
        if (mysqli_query($conn, $q_update)) {
            $message = "Data cabang berhasil diperbarui.";
            $message_type = "success";
        } else {
            $message = "Gagal memperbarui data.";
            $message_type = "danger";
        }
    } else {
        // Add
        $foto_path = "";
        if (isset($_FILES['foto_rental']) && $_FILES['foto_rental']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['foto_rental']['tmp_name'];
            $name = time() . '_' . basename($_FILES['foto_rental']['name']);
            if (!is_dir("uploads")) mkdir("uploads", 0777, true);
            $target = "uploads/" . $name;
            if (move_uploaded_file($tmp_name, $target)) {
                $foto_path = $target;
            }
        }
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $q_insert = "INSERT INTO admin_cabang (username, password, nama_rental, lokasi, no_telp, foto_rental) VALUES ('$username', '$password', '$nama_rental', '$lokasi', '$no_telp', '$foto_path')";
        if (mysqli_query($conn, $q_insert)) {
            $message = "Admin cabang baru berhasil ditambahkan.";
            $message_type = "success";
        } else {
            $message = "Gagal menambahkan admin cabang.";
            $message_type = "danger";
        }
    }
}

// Fetch all branches
$branches = [];
$res = mysqli_query($conn, "SELECT * FROM admin_cabang ORDER BY id DESC");
if ($res) {
    while($row = mysqli_fetch_assoc($res)) {
        $branches[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Admin - Super Admin</title>
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
            <a href="kelola_admin.php" class="sidebar-link active" data-preview-title="Kelola Admin" data-preview-icon="fa-users-gear" data-preview-desc="Tambah, edit, atau blokir akun Admin Pengelola Cabang.">><i class="fa-solid fa-users-gear"></i> Kelola Admin</a>
            <a href="data_cabang.php" class="sidebar-link" data-preview-title="Data Cabang" data-preview-icon="fa-store" data-preview-desc="Manajemen alamat, status operasional, dan info toko seluruh cabang.">><i class="fa-solid fa-store"></i> Data Cabang</a>
            <a href="laporan_global.php" class="sidebar-link" data-preview-title="Laporan Global" data-preview-icon="fa-file-contract" data-preview-desc="Analisis finansial menyeluruh dan unduh laporan performa rental bulanan.">><i class="fa-solid fa-file-contract"></i> Laporan Global</a>
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
                <h4 class="m-0 text-white fw-light">Kelola <span class="fw-bold">Admin Cabang</span></h4>
            </div>
            <div class="d-flex align-items-center">
                <span class="text-white me-3 d-none d-sm-block"><?= htmlspecialchars($_SESSION['superadmin_name']) ?></span>
            </div>
        </header>

        <!-- Content Body -->
        <div class="p-4 p-md-5">
            
            <?php if($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show bg-<?= $message_type == 'success' ? 'success' : 'danger' ?> bg-opacity-25 text-white border-<?= $message_type ?>">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="glass-card p-4 mb-5">
                <h5 class="text-white mb-4"><i class="fa-solid fa-user-plus me-2 neon-text-blue"></i>Tambah Admin Cabang Baru</h5>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-light">Nama Pengelola (Username)</label>
                            <input type="text" name="username" class="form-control form-control-glass text-white" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light">Password</label>
                            <input type="password" name="password" class="form-control form-control-glass text-white" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light">Nama Rental Cabang <small class="text-muted">(pastikan nama tidak sama dengan username/email anda)</small></label>
                            <input type="text" name="nama_rental" class="form-control form-control-glass text-white" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light">Lokasi / Alamat</label>
                            <input type="text" name="lokasi" class="form-control form-control-glass text-white" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light">Nomor Telepon / WA</label>
                            <input type="text" name="no_telp" class="form-control form-control-glass text-white" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light">Foto Tempat Rental (Opsional)</label>
                            <input type="file" name="foto_rental" class="form-control form-control-glass text-white" accept="image/*">
                        </div>
                        <div class="col-12 text-end mt-4">
                            <button type="submit" class="btn btn-neon px-5"><i class="fa-solid fa-plus me-2"></i> Tambah Admin</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="glass-card p-4">
                <h5 class="text-white mb-4"><i class="fa-solid fa-list me-2 neon-text-purple"></i>Daftar Admin Cabang</h5>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle" style="background: transparent;">
                        <thead>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <th>No</th>
                                <th>Nama Rental</th>
                                <th>Lokasi</th>
                                <th>No. Telp</th>
                                <th>Username</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($branches)): ?>
                                <tr><td colspan="5" class="text-center text-muted">Belum ada data admin cabang.</td></tr>
                            <?php else: ?>
                                <?php $no = 1; foreach($branches as $b): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($b['nama_rental']) ?></td>
                                    <td><?= htmlspecialchars($b['lokasi']) ?></td>
                                    <td><?= htmlspecialchars($b['no_telp'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($b['username']) ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-info me-2" onclick="editAdmin(<?= htmlspecialchars(json_encode($b)) ?>)" data-bs-toggle="modal" data-bs-target="#editModal"><i class="fa-solid fa-edit"></i> Edit</button>
                                        <a href="kelola_admin.php?delete=<?= $b['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="event.preventDefault(); Swal.fire({title: 'Konfirmasi', text: 'Yakin hapus PERMANEN cabang ini beserta seluruh data console dan transaksinya?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#00f3ff', cancelButtonColor: '#bc13fe', confirmButtonText: 'Ya, Lanjutkan', cancelButtonText: 'Batal', background: '#0f172a', color: '#fff'}).then((result) => { if (result.isConfirmed) { if(this.tagName === 'A'){ window.location.href = this.href; } else { this.closest('form').submit(); } } });"><i class="fa-solid fa-trash"></i> Hapus</a>
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

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-card border-0">
      <div class="modal-header border-secondary">
        <h5 class="modal-title text-white"><i class="fa-solid fa-edit me-2 neon-text-blue"></i>Edit Admin Cabang</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="" method="POST" enctype="multipart/form-data">
        <div class="modal-body">
            <input type="hidden" name="id" id="edit_id">
            <div class="mb-3">
                <label class="form-label text-light">Nama Pengelola (Username)</label>
                <input type="text" name="username" id="edit_username" class="form-control form-control-glass text-white" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-light">Password <small class="text-muted">(Kosongkan jika tidak diubah)</small></label>
                <input type="password" name="password" class="form-control form-control-glass text-white">
            </div>
            <div class="mb-3">
                <label class="form-label text-light">Nama Rental Cabang <small class="text-muted">(pastikan nama tidak sama dengan username/email anda)</small></label>
                <input type="text" name="nama_rental" id="edit_nama_rental" class="form-control form-control-glass text-white" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-light">Lokasi</label>
                <input type="text" name="lokasi" id="edit_lokasi" class="form-control form-control-glass text-white" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-light">Nomor Telepon</label>
                <input type="text" name="no_telp" id="edit_no_telp" class="form-control form-control-glass text-white" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-light">Foto Tempat Rental <small class="text-muted">(Kosongkan jika tidak diubah)</small></label>
                <input type="file" name="foto_rental" class="form-control form-control-glass text-white" accept="image/*">
            </div>
        </div>
        <div class="modal-footer border-secondary">
            <button type="button" class="btn btn-outline-secondary text-white" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-neon">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function editAdmin(data) {
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_username').value = data.username;
        document.getElementById('edit_nama_rental').value = data.nama_rental;
        document.getElementById('edit_lokasi').value = data.lokasi;
        document.getElementById('edit_no_telp').value = data.no_telp ? data.no_telp : '';
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
