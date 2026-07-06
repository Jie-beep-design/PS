<?php
session_start();
include 'config/db.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username']) && isset($_POST['no_telp'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $no_telp = mysqli_real_escape_string($conn, $_POST['no_telp']);
    
    $query = "SELECT id FROM admin_cabang WHERE username = '$username' AND no_telp = '$no_telp'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $admin = mysqli_fetch_assoc($result);
        $token = bin2hex(random_bytes(32));
        
        $update = "UPDATE admin_cabang SET reset_token = '$token' WHERE id = " . $admin['id'];
        if(mysqli_query($conn, $update)) {
            $base_url = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
            $reset_link = $base_url . "/admin_reset_password.php?token=" . $token;
            
            $message = '
            <div class="bg-green-500/20 border border-green-500 text-green-300 px-4 py-4 rounded mb-4 text-center">
                <p class="mb-2">Data terverifikasi! Silakan klik tombol di bawah ini untuk mereset password Anda.</p>
                <div class="flex flex-col gap-2 mt-4">
                    <a href="'.$reset_link.'" class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded transition">Langsung Reset Password</a>
                </div>
            </div>';
        } else {
            $message = '<div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded mb-4 text-center">Terjadi kesalahan sistem saat membuat token.</div>';
        }
    } else {
        $message = '<div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded mb-4 text-center">Username/Email atau Nomor WA tidak sesuai dengan data terdaftar!</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Admin Forgot Password - RumahPS</title>
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
<body class="bg-black min-h-screen flex items-center justify-center p-4" style="background: radial-gradient(circle at top left, #450a0a 0%, #000000 100%);">

  <div class="glass w-full max-w-md p-8 rounded-2xl shadow-[0_0_30px_rgba(220,38,38,0.3)] floating">
    <div class="text-center mb-8">
      <div class="w-16 h-16 rounded-full bg-red-900/30 border border-red-500/50 flex items-center justify-center mx-auto mb-4 shadow-[0_0_15px_rgba(220,38,38,0.5)]">
        <i class="fa-solid fa-key text-2xl text-red-500"></i>
      </div>
      <h1 class="text-2xl font-bold text-white mb-2">Reset Akses Admin</h1>
      <p class="text-gray-400 text-sm">Masukkan detail otoritas Anda untuk memverifikasi kepemilikan akun.</p>
    </div>

    <?= $message ?>

    <form method="POST" action="admin_forgot_password.php" class="space-y-4">
      <div>
        <label class="block text-gray-400 text-sm mb-1">Username / Email Terdaftar</label>
        <input type="text" name="username" required placeholder="Masukkan Username/Email" class="w-full bg-black/50 border border-red-900/50 rounded-lg px-4 py-3 text-white outline-none glow-input transition">
      </div>
      <div>
        <label class="block text-gray-400 text-sm mb-1">Nomor WA Admin Cabang</label>
        <input type="text" name="no_telp" required placeholder="Contoh: 08123456789" class="w-full bg-black/50 border border-red-900/50 rounded-lg px-4 py-3 text-white outline-none glow-input transition">
      </div>
      
      <button type="submit" class="w-full bg-red-600 hover:bg-red-500 text-white font-bold py-3 rounded-lg mt-6 glow-btn">
        Verifikasi & Dapatkan Link Reset
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
