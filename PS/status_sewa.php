<?php
session_start();
include 'config/db.php';

global $conn;
date_default_timezone_set('Asia/Jakarta');
$raw_search = isset($_GET['search']) ? $_GET['search'] : (isset($_GET['phone']) ? $_GET['phone'] : '');
$search_query = mysqli_real_escape_string($conn, trim($raw_search));
$my_bookings = isset($_SESSION['my_bookings']) && is_array($_SESSION['my_bookings']) ? $_SESSION['my_bookings'] : [];

$grouped_sewa = [];

if ($conn) {
    // Update status otomatis menjadi 'selesai' jika waktu saat ini sudah melewati jam_selesai
    $query_update = "UPDATE bookings 
                     SET status = 'selesai' 
                     WHERE status != 'selesai' 
                     AND NOW() > CONCAT(tanggal, ' ', jam_selesai)";
    mysqli_query($conn, $query_update);

    // Tampilkan data dari database berdasarkan pencarian no hp, nama, atau session
    // Tampilkan data dari database
    $where_clause = "b.status IN ('aktif', 'pending', 'pause') AND DATE(b.tanggal) = CURDATE()"; // Gambaran umum hari ini
    if (!empty($search_query)) {
        $where_clause = "(b.no_telp = '$search_query' OR b.nama_pelanggan = '$search_query')";
    }

    $query_sewa = "SELECT b.*, c.nama_console AS ps, a.nama_rental, COALESCE(b.nama_pelanggan, u.nama, 'Guest') AS nama_user 
                   FROM bookings b 
                   LEFT JOIN consoles c ON b.console_id = c.id 
                   LEFT JOIN admin_cabang a ON c.admin_cabang_id = a.id
                   LEFT JOIN users u ON b.user_id = u.id
                   WHERE $where_clause 
                   ORDER BY b.tanggal DESC, b.jam_mulai DESC";
    $result = mysqli_query($conn, $query_sewa);
    
    if ($result) {
        while($row = mysqli_fetch_assoc($result)) {
            // Jika ps nama tidak ditemukan, gunakan fallback
            if(empty($row['ps'])) $row['ps'] = "Console ID: " . $row['console_id'];
            
            // Format durasi jadi teks
            $row['durasi_teks'] = $row['durasi_tersisa'] ? $row['durasi_tersisa'] . " Jam" : "0 Jam";
            
            // Logika timer statis (akan diperbarui oleh frontend realtime nanti)
            $row['timer'] = "--:--:--"; 
            if($row['status'] == 'aktif' || $row['status'] == 'pending') {
                // Hitung sisa waktu statis sebagai placeholder
                $end = strtotime($row['tanggal'] . ' ' . $row['jam_selesai']);
                $sisa = max(0, $end - time());
                if ($row['status'] == 'aktif') {
                    $row['timer'] = gmdate("H:i:s", $sisa);
                    $row['end_time'] = $end;
                } else {
                    $row['timer'] = "PENDING";
                }
            } elseif ($row['status'] == 'selesai') {
                $row['timer'] = "00:00:00";
            } elseif ($row['status'] == 'pause') {
                $row['timer'] = "PAUSED";
            }
            
            $cabang_name = !empty($row['nama_rental']) ? $row['nama_rental'] : 'Rental Tidak Diketahui';
            $grouped_sewa[$cabang_name][] = $row;
        }
    }
} else {
    // Data dummy jika DB belum tersedia
    $grouped_sewa = [
        "Rental Winongan" => [
            ["ps" => "PlayStation 5 Pro", "nama_rental" => "Rental Winongan", "durasi_teks" => "2 Jam", "status" => "aktif", "timer" => "01:45:20", "console_id" => 1]
        ],
        "Rental Rejoso" => [
            ["ps" => "PlayStation 4 Fat", "nama_rental" => "Rental Rejoso", "durasi_teks" => "1 Jam", "status" => "pending", "timer" => "--:--:--", "console_id" => 2]
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Status Sewa - RumahPS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assest/css/style.css?v=<?= time() ?>">
  <style>
    body { background-color: #0f172a; color: #e2e8f0; }
    .glass-card {
      background: rgba(30, 41, 59, 0.7);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.05);
      transition: all 0.3s ease;
    }
    .glass-card:hover {
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
      transform: translateY(-2px);
    }
    .glow-aktif { box-shadow: inset 0 0 15px rgba(34, 197, 94, 0.1); border-left: 4px solid #22c55e; }
    .glow-pending { box-shadow: inset 0 0 15px rgba(234, 179, 8, 0.1); border-left: 4px solid #eab308; }
    .glow-selesai { border-left: 4px solid #64748b; }
    .glow-pause { box-shadow: inset 0 0 15px rgba(249, 115, 22, 0.1); border-left: 4px solid #f97316; }
    
    /* Waterfall Icon Background */
    .icon-waterfall-bg {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      z-index: -1;
      display: flex;
      justify-content: center;
      align-items: center;
      overflow: hidden;
      pointer-events: none;
    }
    
    .icon-waterfall-item {
      position: absolute;
      font-size: 15rem; /* Sangat besar */
      opacity: 0;
      animation: waterfallIconAnim 12s infinite cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .icon-waterfall-item.icon-1 { text-shadow: 0 0 30px rgba(0, 243, 255, 0.4), 0 0 60px rgba(0, 243, 255, 0.2); color: rgba(224, 255, 255, 0.15); animation-delay: 0s; }
    .icon-waterfall-item.icon-2 { text-shadow: 0 0 30px rgba(188, 19, 254, 0.4), 0 0 60px rgba(188, 19, 254, 0.2); color: rgba(240, 230, 255, 0.15); animation-delay: 4s; }
    .icon-waterfall-item.icon-3 { text-shadow: 0 0 30px rgba(0, 255, 136, 0.4), 0 0 60px rgba(0, 255, 136, 0.2); color: rgba(230, 255, 230, 0.15); animation-delay: 8s; }

    @keyframes waterfallIconAnim {
      0% { opacity: 0; transform: translateY(-50vh) scale(0.8); }
      20% { opacity: 1; transform: translateY(-20vh) scale(1); }
      80% { opacity: 1; transform: translateY(20vh) scale(1.1); }
      100% { opacity: 0; transform: translateY(50vh) scale(0.9); }
    }
  </style>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="min-h-screen p-4 md:p-8 relative">

  <!-- Waterfall Icon Background Elements -->
  <div class="icon-waterfall-bg">
    <i class="fa-solid fa-gamepad icon-waterfall-item icon-1"></i>
    <i class="fa-solid fa-vr-cardboard icon-waterfall-item icon-2"></i>
    <i class="fa-solid fa-headset icon-waterfall-item icon-3"></i>
  </div>

  <div class="max-w-4xl mx-auto relative z-10">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-3xl font-bold text-white">Status <span class="text-blue-400">Sewa</span></h1>
        <p class="text-gray-400">Riwayat dan pantauan console aktif Anda.</p>
      </div>
      <a href="dashboard.php" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-lg text-sm border border-gray-700 transition">
        &larr; Kembali
      </a>
    </div>

    <!-- Search Form -->
    <div class="glass-card rounded-xl p-5 mb-8">
      <form action="status_sewa.php" method="GET" class="flex flex-col md:flex-row gap-4 items-end">
        <div class="flex-1 w-full">
          <label class="block text-sm font-medium text-gray-400 mb-1">Cek Status dengan No. Telepon atau Nama</label>
          <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" placeholder="Masukkan No. Telepon / Nama Anda" class="w-full bg-gray-900 border border-gray-700 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
        </div>
        <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition h-[42px]">
          Cari
        </button>
      </form>
    </div>

    <div class="alert alert-info text-center mb-6 bg-blue-900/30 border border-blue-500/50 text-blue-300 rounded-lg p-3">
        <i class="fa-solid fa-clock me-2"></i> Mohon datang tepat waktu agar tidak mengurangi waktu bermain kamu.
    </div>

    <!-- Rental List -->
    <div class="space-y-8">
      <?php if(empty($grouped_sewa)): ?>
        <div class="text-center text-gray-400 py-10">Belum ada riwayat sewa atau aktivitas penyewaan hari ini.</div>
      <?php else: ?>
          <?php foreach($grouped_sewa as $cabang => $sewas): ?>
          <div class="cabang-group mb-6">
            <h2 class="text-2xl font-bold text-white mb-4 border-b border-gray-700 pb-2"><i class="fa-solid fa-store text-purple-400 mr-2"></i> <?= htmlspecialchars($cabang) ?></h2>
            <div class="space-y-4">
            <?php foreach($sewas as $sewa): 
            $glow = ''; $badge = '';
            if($sewa['status'] == 'aktif') {
                $glow = 'glow-aktif';
                $badge = '<span class="px-3 py-1 rounded-full bg-green-500/20 text-green-400 border border-green-500/30 text-xs font-bold uppercase">Aktif</span>';
            } elseif($sewa['status'] == 'pending') {
                $glow = 'glow-pending';
                $badge = '<span class="px-3 py-1 rounded-full bg-yellow-500/20 text-yellow-400 border border-yellow-500/30 text-xs font-bold uppercase">Pending</span>';
            } elseif($sewa['status'] == 'pause') {
                $glow = 'glow-pause';
                $badge = '<span class="px-3 py-1 rounded-full bg-orange-500/20 text-orange-400 border border-orange-500/30 text-xs font-bold uppercase">Pause</span>';
            } else {
                $glow = 'glow-selesai';
                $badge = '<span class="px-3 py-1 rounded-full bg-gray-500/20 text-gray-400 border border-gray-500/30 text-xs font-bold uppercase">Selesai</span>';
            }
          ?>
          
          <div class="glass-card <?= $glow ?> rounded-xl p-5 flex flex-col md:flex-row items-center justify-between gap-4">
            
            <!-- Info -->
            <div class="flex-1 w-full">
              <div class="flex items-center gap-3 mb-1">
                <h3 class="text-xl font-bold text-white"><?= htmlspecialchars($sewa['ps']) ?></h3>
                <?= $badge ?>
              </div>
              <p class="text-blue-400 text-sm mb-2 font-medium"><i class="fa-solid fa-user mr-1"></i> <?= htmlspecialchars($sewa['nama_user'] ?? 'Guest') ?></p>
              <p class="text-gray-400 text-sm">Durasi Sewa: <span class="text-gray-200"><?= htmlspecialchars($sewa['durasi_teks']) ?></span></p>
            </div>

            <!-- Timer -->
            <div class="text-center px-6 py-2 bg-black/40 rounded-lg border border-white/5 w-full md:w-auto">
              <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Sisa Waktu</p>
              <p class="text-2xl font-mono tracking-wider <?= $sewa['status'] == 'aktif' ? 'text-blue-400 timer-countdown' : 'text-gray-500' ?>" 
                 <?= $sewa['status'] == 'aktif' ? 'data-end="'.$sewa['end_time'].'"' : '' ?>>
                <?= $sewa['timer'] ?>
              </p>
            </div>

            <!-- Action -->
            <div class="w-full md:w-auto flex justify-end">
              <?php if($sewa['status'] == 'aktif' || $sewa['status'] == 'pause'): ?>
                <a href="jadwal_console.php?console_id=<?= $sewa['console_id'] ?>" class="w-full md:w-auto px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg shadow-[0_0_15px_rgba(37,99,235,0.4)] transition text-center">
                  Lihat Jadwal
                </a>
              <?php else: ?>
                <a href="jadwal_console.php?console_id=<?= $sewa['console_id'] ?>" class="w-full md:w-auto px-5 py-2.5 bg-gray-800 text-gray-500 hover:text-gray-300 rounded-lg transition text-center">
                  Lihat Jadwal
                </a>
              <?php endif; ?>
            </div>

          </div>
          <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const timers = document.querySelectorAll('.timer-countdown');
        
        setInterval(() => {
            const now = Math.floor(Date.now() / 1000);
            
            timers.forEach(timer => {
                const end = parseInt(timer.getAttribute('data-end'));
                if (!isNaN(end)) {
                    let diff = end - now;
                    if (diff > 0) {
                        let h = Math.floor(diff / 3600);
                        let m = Math.floor((diff % 3600) / 60);
                        let s = diff % 60;
                        
                        timer.textContent = 
                            (h < 10 ? "0" + h : h) + ":" + 
                            (m < 10 ? "0" + m : m) + ":" + 
                            (s < 10 ? "0" + s : s);
                    } else {
                        timer.textContent = "00:00:00";
                        timer.classList.remove('text-blue-400');
                        timer.classList.add('text-gray-500');
                        // Optionally refresh the page when a timer hits zero
                        // location.reload();
                    }
                }
            });
        }, 1000);
    });
  </script>
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
