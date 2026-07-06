<?php
session_start();
include 'config/db.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nama_rental = mysqli_real_escape_string($conn, $_POST['nama_rental']);
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $no_telp = mysqli_real_escape_string($conn, $_POST['no_telp']);
    
    if ($password !== $confirm_password) {
        $message = '<div class="bg-red-500/20 border border-red-500 text-red-400 px-4 py-3 rounded mb-4 text-center">Konfirmasi password tidak cocok!</div>';
    } else {
        // Cek email duplikat
        $cek_email = mysqli_query($conn, "SELECT id FROM admin_cabang WHERE email = '$email'");
        if (mysqli_num_rows($cek_email) > 0) {
            $message = '<div class="bg-red-500/20 border border-red-500 text-red-400 px-4 py-3 rounded mb-4 text-center">Email sudah terdaftar! Gunakan email lain.</div>';
        } else {
            // Handle upload foto_rental
            $foto_rental_path = '';
            if (isset($_FILES['foto_rental']) && $_FILES['foto_rental']['error'] == 0) {
                $target_dir = "uploads/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $file_name = time() . '_' . basename($_FILES["foto_rental"]["name"]);
                $target_file = $target_dir . $file_name;
                
                if (move_uploaded_file($_FILES["foto_rental"]["tmp_name"], $target_file)) {
                    $foto_rental_path = $target_file;
                }
            }
            
            // Insert admin cabang (menyimpan password dalam bentuk plaintext agar sama dengan mekanisme login saat ini)
            $query = "INSERT INTO admin_cabang (username, email, password, nama_rental, lokasi, no_telp, foto_rental) 
                      VALUES ('$nama', '$email', '$password', '$nama_rental', '$lokasi', '$no_telp', '$foto_rental_path')";
            
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
  <title>Register Admin - RumahPS</title>
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
<body class="bg-black min-h-screen flex items-center justify-center p-4 relative" style="background: radial-gradient(circle at bottom right, #450a0a 0%, #000000 100%);">
  
  <div class="glass w-full max-w-md p-8 rounded-2xl shadow-[0_0_30px_rgba(220,38,38,0.3)] floating relative z-10 my-8">
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-white mb-2">Daftar <span class="text-red-500">Admin</span></h1>
      <p class="text-gray-400">Request akses ke sistem manajemen</p>
    </div>

    <?= $message ?>

    <form method="POST" action="admin_register.php" enctype="multipart/form-data" class="space-y-4">
      <div>
        <input type="text" name="nama" required placeholder="Nama Pengelola (Username)" class="w-full bg-black/50 border border-red-900/50 rounded-lg px-4 py-3 text-white outline-none glow-input transition">
      </div>
      <div>
        <input type="email" name="email" required placeholder="Alamat Email" class="w-full bg-black/50 border border-red-900/50 rounded-lg px-4 py-3 text-white outline-none glow-input transition">
      </div>
      <div>
        <input type="text" name="nama_rental" required placeholder="Nama Rental Cabang (pastikan nama tidak sama dengan username/email anda)" class="w-full bg-black/50 border border-red-900/50 rounded-lg px-4 py-3 text-white outline-none glow-input transition">
      </div>
      <div>
        <input type="text" name="lokasi" required placeholder="Lokasi (Kota/Alamat)" class="w-full bg-black/50 border border-red-900/50 rounded-lg px-4 py-3 text-white outline-none glow-input transition">
      </div>
      <div>
        <input type="text" name="no_telp" required placeholder="Nomor Telepon / WA" class="w-full bg-black/50 border border-red-900/50 rounded-lg px-4 py-3 text-white outline-none glow-input transition">
      </div>
      <div>
        <label class="block text-gray-400 text-sm mb-1">Foto Tempat Rental (Opsional)</label>
        <input type="file" name="foto_rental" accept="image/*" class="w-full bg-black/50 border border-red-900/50 rounded-lg px-4 py-3 text-white outline-none glow-input transition">
      </div>
      <div>
        <input type="password" name="password" required placeholder="Password" class="w-full bg-black/50 border border-red-900/50 rounded-lg px-4 py-3 text-white outline-none glow-input transition">
      </div>
      <div>
        <input type="password" name="confirm_password" required placeholder="Konfirmasi Password" class="w-full bg-black/50 border border-red-900/50 rounded-lg px-4 py-3 text-white outline-none glow-input transition">
      </div>
      
      <button type="submit" class="w-full bg-red-600 hover:bg-red-500 text-white font-bold py-3 rounded-lg mt-6 glow-btn">
        Register Admin Cabang
      </button>
    </form>

    <div class="text-center mt-6">
      <p class="text-gray-400 text-sm">Sudah punya akses? 
        <a href="admin_login.php" class="text-red-400 hover:text-red-300 hover:underline">Login Admin</a>
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
