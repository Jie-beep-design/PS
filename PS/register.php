<?php
session_start();
include 'config/db.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $message = '<div class="bg-red-500/20 border border-red-500 text-red-400 px-4 py-3 rounded mb-4 text-center">Konfirmasi password tidak cocok!</div>';
    } else {
        $cek_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
        if (mysqli_num_rows($cek_email) > 0) {
            $message = '<div class="bg-red-500/20 border border-red-500 text-red-400 px-4 py-3 rounded mb-4 text-center">Email sudah terdaftar! Gunakan email lain.</div>';
        } else {
            // Insert user
            $query = "INSERT INTO users (nama, email, password, role) VALUES ('$nama', '$email', '$password', 'user')";
            
            if (mysqli_query($conn, $query)) {
                $message = '<div class="bg-green-500/20 border border-green-500 text-green-400 px-4 py-3 rounded mb-4 text-center">Registrasi berhasil! Silakan login.</div>';
            } else {
                $message = '<div class="bg-red-500/20 border border-red-500 text-red-400 px-4 py-3 rounded mb-4 text-center">Gagal menyimpan data ke database.</div>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Register - RumahPS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .glass {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .glow-input:focus {
      box-shadow: 0 0 10px rgba(188, 19, 254, 0.5);
      border-color: #bc13fe;
    }
    .glow-btn {
      transition: 0.3s;
      box-shadow: 0 0 10px rgba(188, 19, 254, 0.5);
    }
    .glow-btn:hover {
      box-shadow: 0 0 20px rgba(188, 19, 254, 0.8);
      transform: translateY(-2px);
    }
  </style>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assest/css/style.css?v=1783149920">
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4" style="background: radial-gradient(circle at center, #1e1b4b 0%, #0f172a 100%);">

  <div class="glass w-full max-w-md p-8 rounded-2xl shadow-2xl">
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-white mb-2">Rumah<span class="text-purple-500">PS</span></h1>
      <p class="text-gray-400">Buat akun baru Anda</p>
    </div>

    <?= $message ?>

    <form method="POST" action="register.php" class="space-y-4">
      <div>
        <label class="block text-gray-300 text-sm mb-1">Nama Lengkap</label>
        <input type="text" name="nama" required class="w-full bg-black/30 border border-gray-600 rounded-lg px-4 py-2 text-white outline-none glow-input transition">
      </div>
      <div>
        <label class="block text-gray-300 text-sm mb-1">Email</label>
        <input type="email" name="email" required class="w-full bg-black/30 border border-gray-600 rounded-lg px-4 py-2 text-white outline-none glow-input transition">
      </div>
      <div>
        <label class="block text-gray-300 text-sm mb-1">Password</label>
        <input type="password" name="password" required class="w-full bg-black/30 border border-gray-600 rounded-lg px-4 py-2 text-white outline-none glow-input transition">
      </div>
      <div>
        <label class="block text-gray-300 text-sm mb-1">Konfirmasi Password</label>
        <input type="password" name="confirm_password" required class="w-full bg-black/30 border border-gray-600 rounded-lg px-4 py-2 text-white outline-none glow-input transition">
      </div>
      
      <button type="submit" class="w-full bg-purple-600 text-white font-bold py-3 rounded-lg mt-6 glow-btn">
        Register Account
      </button>
    </form>

    <div class="text-center mt-6">
      <p class="text-gray-400 text-sm">Sudah punya akun? 
        <a href="login.php" class="text-purple-400 hover:text-purple-300 hover:underline">Login di sini</a>
      </p>
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
