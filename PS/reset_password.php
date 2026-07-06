<?php include "config/db.php"; ?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Lupa Password</title>
  <script src="https://cdn.tailwindcss.com"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assest/css/style.css?v=1783149920">
</head>
<body class="bg-gradient-to-br from-blue-100 via-purple-100 to-pink-100 min-h-screen flex items-center justify-center">

  <div class="bg-white shadow-lg rounded-xl p-8 w-[400px]">
    <h2 class="text-2xl font-bold text-blue-700 mb-6 text-center">Lupa Password?</h2>
    <p class="text-sm text-gray-600 mb-6 text-center">Masukkan Email anda untuk reset password</p>

    <?php if (!isset($_POST['check_email']) && !isset($_POST['reset_password'])): ?>
      <!-- Form input email -->
      <form method="POST" class="space-y-4">
        <label class="block">
          <span class="text-gray-700 text-sm">Email</span>
          <input type="email" name="email" placeholder="Masukkan Email" required
                 class="w-full mt-1 px-4 py-2 border rounded-full focus:outline-none focus:ring-2 focus:ring-green-500" />
        </label>
        <button type="submit" name="check_email"
                class="w-full bg-blue-600 text-white py-2 rounded-full font-semibold hover:brightness-110 transition duration-200">
          Kirim
        </button>
      </form>
      <p class="text-center text-sm mt-4">
        <a href="login.php" class="text-blue-600 hover:underline">← Kembali Ke Halaman Login</a>
      </p>

    <?php elseif (isset($_POST['check_email'])):
      $email = $_POST['email'];
      $check = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
      $user = mysqli_fetch_assoc($check);
      if ($user): ?>
        <!-- Form ubah password -->
        <form method="POST" class="space-y-4">
          <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
          <label>
            <span class="text-gray-700 text-sm">Password Baru</span>
            <input type="password" name="new_password" placeholder="Password baru" required
                   class="w-full mt-1 px-4 py-2 border rounded-full focus:outline-none focus:ring-2 focus:ring-green-500" />
          </label>
          <button type="submit" name="reset_password"
                  class="w-full bg-blue-600 text-white py-2 rounded-full font-semibold hover:brightness-110 transition duration-200">
            Simpan Password Baru
          </button>
        </form>

      <?php else: ?>
        <p class="text-red-600 font-semibold text-center">Email tidak ditemukan.</p>
        <p class="text-center mt-4"><a href="reset_password.php" class="text-blue-600 hover:underline">Coba Lagi</a></p>
      <?php endif; ?>

    <?php elseif (isset($_POST['reset_password'])):
      $email = $_POST['email'];
      $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
      $update = mysqli_query($conn, "UPDATE users SET password = '$new_password' WHERE email = '$email'");
      if ($update): ?>
        <p class="text-green-600 text-center font-semibold">Password berhasil diubah!</p>
        <p class="text-center mt-2"><a href="login.php" class="text-blue-600 hover:underline">Kembali ke Login</a></p>
      <?php else: ?>
        <p class="text-red-600 font-semibold text-center">Gagal menyimpan password.</p>
      <?php endif; ?>
    <?php endif; ?>
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
