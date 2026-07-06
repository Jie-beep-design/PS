<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Logout - RumahPS</title>
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
<body class="bg-gray-900 text-white flex items-center justify-center min-h-screen">
  <div class="text-center glass p-8 rounded-2xl">
    <div class="w-16 h-16 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
    <h1 class="text-2xl font-bold mb-2">Anda berhasil logout</h1>
    <p class="text-gray-400">Mengalihkan ke halaman login...</p>
  </div>
  <script>
    setTimeout(function() {
      window.location.href = 'rumah_playstation.php';
    }, 1500);
  </script>
</body>
</html>
