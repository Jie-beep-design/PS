<?php
session_start();

// Hapus hanya data sesi superadmin agar tidak mengganggu sesi admin cabang jika ada
unset($_SESSION['superadmin_id']);
unset($_SESSION['superadmin_name']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Super Admin Logout - RumahPS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-10px); } 100% { transform: translateY(0px); } }
    .floating { animation: float 4s ease-in-out infinite; }
    .gold-glow-text { text-shadow: 0 0 15px rgba(255, 215, 0, 0.6); }
  </style>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assest/css/style.css?v=1783151629">
    <script>
      // Load Comic Mode from LocalStorage
      if (localStorage.getItem('theme') === 'comic') {
          document.documentElement.classList.add('comic-mode');
          document.addEventListener('DOMContentLoaded', () => {
              document.body.classList.add('comic-mode');
          });
      }
    </script>
</head>
<body class="bg-black text-white flex items-center justify-center min-h-screen" style="background: radial-gradient(circle at center, #1a0b2e 0%, #000000 100%);">
  
  <div class="text-center glass p-8 rounded-2xl floating">
    <div class="w-16 h-16 border-4 border-yellow-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
    <h1 class="text-3xl font-extrabold mb-3 tracking-wide">
        SUPER <span class="text-yellow-500 gold-glow-text">ADMIN</span>
    </h1>
    <h2 class="text-xl font-semibold mb-2 text-gray-200">Anda berhasil logout dari Super Admin</h2>
    <p class="text-gray-500 text-sm mt-4">Memutuskan koneksi aman ke portal...</p>
  </div>

  <script>
    setTimeout(function() {
      window.location.href = 'dashboard.php';
    }, 2500); // Redirect otomatis setelah 2.5 detik
  </script>
</body>
</html>
