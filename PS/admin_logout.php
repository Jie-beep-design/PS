<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Admin Logout - RumahPS</title>
  <script src="https://cdn.tailwindcss.com"></script>

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
<body class="bg-black text-white flex items-center justify-center min-h-screen" style="background: radial-gradient(circle at center, #450a0a 0%, #000000 100%);">
  <style>
    @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-10px); } 100% { transform: translateY(0px); } }
    .floating { animation: float 4s ease-in-out infinite; }
  </style>
  <div class="text-center glass p-8 rounded-2xl floating">
    <div class="w-16 h-16 border-4 border-red-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
    <h1 class="text-2xl font-bold mb-2 text-red-500">Admin berhasil logout</h1>
    <p class="text-gray-400">Mengalihkan ke portal admin...</p>
  </div>
  <script>
    setTimeout(function() {
      window.location.href = 'dashboard.php';
    }, 1500);
  </script>
</body>
</html>
