<?php
session_start();
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_user = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email_user) || empty($password)) {
        $error_msg = "Username/Email & password kosong!";
    } else {
        $query = "SELECT * FROM admin_cabang WHERE email='$email_user' OR username='$email_user'";
        $result = mysqli_query($conn, $query);

        if ($data = mysqli_fetch_assoc($result)) {
            if ($data['password'] == $password || password_verify($password, $data['password'])) {
                $_SESSION['admin_id']   = $data['id'];
                $_SESSION['admin_name'] = $data['username'];
                $_SESSION['role']       = 'admin_cabang';

                header("Location: dashboard_admin.php");
                exit();
            }
        }
        
        // Jika gagal
        $error_msg = "Username/Email atau Password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pengelola Login - RumahPS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome -->
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
      background: transparent;
      color: #ef4444; /* text-red-500 */
      border: 1px solid #ef4444;
      text-transform: uppercase;
      letter-spacing: 1px;
      transition: all 0.3s ease;
    }
    .glow-btn:hover {
      background: #ef4444;
      color: #000;
      box-shadow: 0 0 15px #ef4444;
    }
    .glow-btn:active {
      transform: scale(0.98);
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
<body class="bg-black min-h-screen flex items-center justify-center p-4 relative" style="background: radial-gradient(circle at center, #450a0a 0%, #000000 100%);">
  
  <div class="glass w-full max-w-md p-8 rounded-2xl shadow-[0_0_30px_rgba(220,38,38,0.3)] floating relative z-10">
    <div class="text-center mb-8">
      <div class="inline-block p-4 rounded-full bg-red-900/30 border border-red-500/50 mb-4 shadow-[0_0_15px_rgba(220,38,38,0.5)]">
        <i class="fa-solid fa-user-shield text-3xl text-red-500"></i>
      </div>
      <h1 class="text-3xl font-bold text-white mb-2">Pengelola <span class="text-red-500">Portal</span></h1>
      <p class="text-gray-400">Setting personnel only</p>
    </div>

    <?php if(isset($error_msg)): ?>
        <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded mb-6 text-center text-sm">
            <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-5">
      <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <i class="fa-solid fa-envelope text-gray-400"></i>
        </div>
        <input type="text" name="email" required placeholder="Pengelola Email / Username" class="w-full bg-black/50 border border-red-900/50 rounded-lg pl-10 pr-4 py-3 text-white outline-none glow-input transition">
      </div>
      
      <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <i class="fa-solid fa-lock text-gray-400"></i>
        </div>
        <input type="password" name="password" required placeholder="Password" class="w-full bg-black/50 border border-red-900/50 rounded-lg pl-10 pr-4 py-3 text-white outline-none glow-input transition">
      </div>
      
      <div class="flex items-center justify-between mt-2">
        <a href="admin_forgot_password.php" class="text-sm text-red-400 hover:text-red-300 hover:underline transition">Lupa Password?</a>
      </div>

      <button type="submit" class="w-full font-bold py-3 rounded-lg mt-4 glow-btn flex items-center justify-center gap-2">
        <i class="fa-solid fa-right-to-bracket"></i> Login To Dashboard
      </button>
    </form>

    <div class="mt-8 space-y-4 text-center">
      <p class="text-gray-400 text-sm">Belum punya akun? 
        <a href="admin_register.php" class="text-red-400 hover:text-red-300 hover:underline font-semibold">Daftar Pengelola</a>
      </p>
      <div class="border-t border-red-900/30 pt-4">
        <a href="dashboard.php" class="text-gray-500 hover:text-white transition text-sm flex items-center justify-center gap-2">
          <i class="fa-solid fa-arrow-left"></i> Kembali ke Menu Utama
        </a>
      </div>
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
