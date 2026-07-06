<?php
// forgot_password.php - Dummy UI
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = htmlspecialchars($_POST['email']);
    $message = '<div class="bg-blue-500/20 border border-blue-500 text-blue-300 px-4 py-3 rounded mb-4 text-center">Link reset password telah dikirim ke <strong>'.$email.'</strong> (Simulasi)</div>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password - RumahPS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .glass {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .glow-input:focus {
      box-shadow: 0 0 10px rgba(0, 243, 255, 0.5);
      border-color: #00f3ff;
    }
    .glow-btn {
      transition: 0.3s;
      box-shadow: 0 0 10px rgba(0, 243, 255, 0.4);
    }
    .glow-btn:hover {
      box-shadow: 0 0 20px rgba(0, 243, 255, 0.8);
      transform: translateY(-2px);
    }
  </style>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assest/css/style.css?v=1783149920">
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4" style="background: radial-gradient(circle at top right, #0f172a 0%, #000000 100%);">

  <div class="glass w-full max-w-md p-8 rounded-2xl shadow-2xl">
    <div class="text-center mb-8">
      <div class="w-16 h-16 rounded-full bg-blue-500/20 border border-blue-500 flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
      </div>
      <h1 class="text-2xl font-bold text-white mb-2">Reset Password</h1>
      <p class="text-gray-400 text-sm">Masukkan email Anda dan kami akan mengirimkan instruksi untuk mereset password.</p>
    </div>

    <?= $message ?>

    <form method="POST" action="forgot_password.php" class="space-y-4">
      <div>
        <label class="block text-gray-300 text-sm mb-1">Alamat Email</label>
        <input type="email" name="email" required placeholder="nama@email.com" class="w-full bg-black/30 border border-gray-600 rounded-lg px-4 py-3 text-white outline-none glow-input transition">
      </div>
      
      <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg mt-4 glow-btn">
        Kirim Reset Password
      </button>
    </form>

    <div class="text-center mt-8">
      <a href="login.php" class="text-gray-400 hover:text-white transition flex items-center justify-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Kembali ke Login
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
