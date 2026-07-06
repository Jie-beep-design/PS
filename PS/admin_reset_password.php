<?php
session_start();
include 'config/db.php';

$message = '';
$token = isset($_GET['token']) ? mysqli_real_escape_string($conn, $_GET['token']) : (isset($_POST['token']) ? mysqli_real_escape_string($conn, $_POST['token']) : '');

if(empty($token)) {
    die("Token tidak valid atau tidak ditemukan.");
}

// Cek token di DB
$query = "SELECT id FROM admin_cabang WHERE reset_token = '$token' LIMIT 1";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Token sudah digunakan, kadaluarsa, atau tidak valid. Silakan ulangi proses lupa password.");
}

$admin = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    if ($password !== $confirm) {
        $message = '<div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded mb-4 text-center">Password baru dan konfirmasi tidak cocok!</div>';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update = "UPDATE admin_cabang SET password = '$hashed_password', reset_token = NULL WHERE id = " . $admin['id'];
        
        if (mysqli_query($conn, $update)) {
            $message = '<div class="bg-green-500/20 border border-green-500 text-green-400 px-4 py-3 rounded mb-4 text-center">Password Admin berhasil direset! Silakan login kembali.</div>';
        } else {
            $message = '<div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded mb-4 text-center">Terjadi kesalahan pada database.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Admin Reset Password - RumahPS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .glass {
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(220, 38, 38, 0.3);
    }
    .glow-input:focus {
      box-shadow: 0 0 12px rgba(220, 38, 38, 0.5);
      border-color: #ef4444;
    }
    .glow-btn {
      transition: all 0.3s ease;
      box-shadow: 0 0 10px rgba(220, 38, 38, 0.4);
    }
    .glow-btn:hover {
      box-shadow: 0 0 20px rgba(220, 38, 38, 0.8);
      transform: translateY(-2px);
    }
    @keyframes float {
      0% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
      100% { transform: translateY(0px); }
    }
    .floating {
      animation: float 4s ease-in-out infinite;
    }
  </style>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assest/css/style.css?v=1783149920">
</head>
<body class="bg-black min-h-screen flex items-center justify-center p-4" style="background: radial-gradient(circle at bottom, #450a0a 0%, #000000 100%);">

  <div class="glass w-full max-w-md p-8 rounded-2xl shadow-[0_0_30px_rgba(220,38,38,0.3)] floating">
    <div class="text-center mb-8">
      <div class="w-16 h-16 rounded-full bg-red-900/30 border border-red-500/50 flex items-center justify-center mx-auto mb-4 shadow-[0_0_15px_rgba(220,38,38,0.5)]">
        <i class="fa-solid fa-unlock-keyhole text-2xl text-red-500"></i>
      </div>
      <h1 class="text-2xl font-bold text-white mb-2">Setup Password Baru</h1>
      <p class="text-gray-400 text-sm">Masukkan password baru untuk akun Admin Anda.</p>
    </div>

    <?= $message ?>

    <form method="POST" action="admin_reset_password.php" class="space-y-4">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      <div>
        <input type="password" name="password" required placeholder="Password Baru" class="w-full bg-black/50 border border-red-900/50 rounded-lg px-4 py-3 text-white outline-none glow-input transition">
      </div>
      <div>
        <input type="password" name="confirm_password" required placeholder="Konfirmasi Password Baru" class="w-full bg-black/50 border border-red-900/50 rounded-lg px-4 py-3 text-white outline-none glow-input transition">
      </div>
      
      <button type="submit" class="w-full bg-red-600 hover:bg-red-500 text-white font-bold py-3 rounded-lg mt-4 glow-btn">
        Simpan Password Baru
      </button>
    </form>

    <div class="text-center mt-8">
      <a href="admin_login.php" class="text-gray-500 hover:text-white transition flex items-center justify-center gap-2">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Login Admin
      </a>
    </div>
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
