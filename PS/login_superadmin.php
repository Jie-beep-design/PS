<?php
include "config/db.php";
session_start();

if (isset($_POST['login_superadmin'])) {
  $username = mysqli_real_escape_string($conn, $_POST['username']);
  $password = $_POST['password'];

  $query = "SELECT * FROM admin WHERE username='$username'";
  $result = mysqli_query($conn, $query);
  $admin = mysqli_fetch_assoc($result);

  if ($admin && password_verify($password, $admin['password'])) {
    $_SESSION['superadmin_id'] = $admin['id'];
    $_SESSION['superadmin_name'] = $admin['username'];
    header("Location: dashboard_superadmin.php");
    exit();
  } else {
    $error = "Username atau password salah!";
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Super Admin HQ - RumahPS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-10px); } 100% { transform: translateY(0px); } }
    .floating { animation: float 5s ease-in-out infinite; }
    
    .glass-gold {
      background: rgba(10, 10, 15, 0.7);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 215, 0, 0.4);
      box-shadow: 0 0 30px rgba(255, 215, 0, 0.15);
    }
    
    .input-neon:focus {
      border-color: #ffd700;
      box-shadow: 0 0 15px rgba(255, 215, 0, 0.5);
    }
    
    .btn-gold {
      background: linear-gradient(135deg, #b8860b 0%, #ffd700 100%);
      color: #000;
      transition: all 0.3s ease;
      box-shadow: 0 0 15px rgba(255, 215, 0, 0.4);
    }
    
    .btn-gold:hover {
      transform: translateY(-2px) scale(1.02);
      box-shadow: 0 0 25px rgba(255, 215, 0, 0.8);
    }
    
    .gold-glow-text {
      text-shadow: 0 0 15px rgba(255, 215, 0, 0.6);
    }
  </style>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assest/css/style.css?v=1783149920">
</head>
<body class="bg-black min-h-screen flex items-center justify-center p-4 relative" style="background: radial-gradient(circle at center, #1a0b2e 0%, #000000 100%);">
  
  <div class="glass-gold w-full max-w-md p-8 rounded-2xl floating relative z-10">
    <div class="text-center mb-8">
      <div class="inline-block p-4 rounded-full bg-yellow-900/30 border border-yellow-500/50 mb-4 shadow-[0_0_20px_rgba(255,215,0,0.4)]">
        <i class="fa-solid fa-crown text-4xl text-yellow-500"></i>
      </div>
      <h1 class="text-3xl font-extrabold text-white mb-2 tracking-wide">
        SUPER <span class="text-yellow-500 gold-glow-text">ADMIN</span>
      </h1>
      <p class="text-gray-400 text-sm tracking-widest uppercase">System Core Control</p>
    </div>

    <?php if (isset($error)) : ?>
      <div class="mb-5 bg-red-900/40 border border-red-500/50 text-red-400 px-4 py-3 rounded-lg text-center shadow-[0_0_10px_rgba(239,68,68,0.3)]">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= $error ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-5">
      <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
          <i class="fa-solid fa-user-shield text-yellow-600"></i>
        </div>
        <input 
          type="text" 
          name="username" 
          placeholder="Super Admin Username" 
          required 
          class="w-full bg-black/60 border border-yellow-900/50 rounded-xl pl-11 pr-4 py-3 text-white outline-none input-neon transition font-mono tracking-wide" 
        />
      </div>
      
      <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
          <i class="fa-solid fa-key text-yellow-600"></i>
        </div>
        <input 
          type="password" 
          name="password" 
          placeholder="Access Protocol (Password)" 
          required 
          class="w-full bg-black/60 border border-yellow-900/50 rounded-xl pl-11 pr-4 py-3 text-white outline-none input-neon transition font-mono tracking-wide" 
        />
      </div>
      
      <button 
        type="submit" 
        name="login_superadmin" 
        class="w-full btn-gold font-bold py-3.5 rounded-xl mt-6 flex items-center justify-center gap-2 text-lg uppercase tracking-widest"
      >
        <i class="fa-solid fa-fingerprint"></i> Authenticate
      </button>
    </form>

    <div class="border-t border-yellow-900/30 mt-8 pt-5 text-center">
      <a href="rumah_playstation.php" class="text-gray-500 hover:text-yellow-400 transition text-sm flex items-center justify-center gap-2">
        <i class="fa-solid fa-arrow-left"></i> Abort & Return to Base
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
