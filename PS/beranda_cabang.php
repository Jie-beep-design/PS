<?php
session_start();
include "config/db.php";

if (!isset($_GET['admin_cabang_id']) || !is_numeric($_GET['admin_cabang_id'])) {
  die("Admin tidak ditemukan.");
}

$admin_cabang_id = $_GET['admin_cabang_id'];

// Ambil info admin
$admin_query = mysqli_query($conn, "SELECT * FROM admin_cabang WHERE id = '$admin_cabang_id'");
$admin_data = mysqli_fetch_assoc($admin_query);
if (!$admin_data) {
  die("Data admin tidak ditemukan.");
}

// Ambil PS dari admin ini
$ps_result = mysqli_query($conn, "SELECT * FROM playstation WHERE admin_cabang_id = '$admin_cabang_id'");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Beranda Cabang - <?= htmlspecialchars($admin_data['nama_rental']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 min-h-screen">
  <div class="p-6 bg-white shadow sticky top-0 z-10">
    <h1 class="text-2xl font-bold text-blue-700">Rental: <?= htmlspecialchars($admin_data['nama_rental']) ?></h1>
    <p class="text-gray-600">Lokasi: <?= htmlspecialchars($admin_data['lokasi']) ?></p>
    <a href="dashboard.php" class="text-sm text-blue-600 hover:underline">← Kembali ke semua cabang</a>
  </div>

  <main class="p-6">
    <h2 class="text-xl font-semibold mb-4">Daftar PS di Cabang Ini</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <?php while ($ps = mysqli_fetch_assoc($ps_result)) : ?>
        <div class="bg-white p-4 rounded shadow">
          <img src="<?= htmlspecialchars($ps['gambar']) ?>" class="h-40 w-full object-cover rounded mb-2">
          <h3 class="text-lg font-bold"><?= htmlspecialchars($ps['nama']) ?></h3>
          <p class="text-blue-600 mb-2">Rp <?= number_format($ps['harga_per_jam'], 0, ',', '.') ?> / jam</p>
          <a href="form_sewa.php?id=<?= $ps['id'] ?>" class="block text-center bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Sewa Sekarang</a>
        </div>
      <?php endwhile; ?>
    </div>
  </main>
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
