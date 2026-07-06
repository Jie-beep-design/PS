<?php
// admin_status_sewa.php - Dummy UI
$consoles = [
    ["nama" => "PlayStation 5 Pro - TV 1", "status" => "digunakan", "timer" => "01:45:20"],
    ["nama" => "PlayStation 5 Slim - TV 2", "status" => "tersedia", "timer" => "--:--:--"],
    ["nama" => "PlayStation 4 Pro - TV 3", "status" => "pause", "timer" => "00:15:00"],
    ["nama" => "PlayStation 4 Fat - VIP Room", "status" => "digunakan", "timer" => "02:10:05"],
    ["nama" => "PlayStation 5 - Reguler 1", "status" => "tersedia", "timer" => "--:--:--"],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Admin - Console Status - RumahPS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body { background-color: #000; color: #e2e8f0; }
    .glass-card {
      background: rgba(15, 23, 42, 0.7);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.05);
      transition: all 0.3s ease;
    }
    .glass-card:hover {
      transform: translateY(-4px);
    }
    @keyframes float {
      0% { transform: translateY(0px); }
      50% { transform: translateY(-8px); }
      100% { transform: translateY(0px); }
    }
    .floating {
      animation: float 5s ease-in-out infinite;
    }
    .glow-tersedia { box-shadow: 0 0 15px rgba(34, 197, 94, 0.15); border-top: 3px solid #22c55e; }
    .glow-digunakan { box-shadow: 0 0 15px rgba(239, 68, 68, 0.15); border-top: 3px solid #ef4444; }
    .glow-pause { box-shadow: 0 0 15px rgba(234, 179, 8, 0.15); border-top: 3px solid #eab308; }
    
    .btn-glow-green { background: rgba(34, 197, 94, 0.1); border: 1px solid #22c55e; color: #4ade80; transition: 0.3s; }
    .btn-glow-green:hover { background: #22c55e; color: #000; box-shadow: 0 0 15px #22c55e; }
    
    .btn-glow-red { background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #f87171; transition: 0.3s; }
    .btn-glow-red:hover { background: #ef4444; color: #fff; box-shadow: 0 0 15px #ef4444; }
    
    .btn-glow-yellow { background: rgba(234, 179, 8, 0.1); border: 1px solid #eab308; color: #facc15; transition: 0.3s; }
    .btn-glow-yellow:hover { background: #eab308; color: #000; box-shadow: 0 0 15px #eab308; }
    
    .btn-glow-blue { background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; color: #60a5fa; transition: 0.3s; }
    .btn-glow-blue:hover { background: #3b82f6; color: #fff; box-shadow: 0 0 15px #3b82f6; }
  </style>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="min-h-screen p-4 md:p-8" style="background: radial-gradient(circle at top left, #1e1b4b 0%, #000000 100%);">

  <div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row items-center justify-between mb-8 pb-4 border-b border-gray-800">
      <div class="mb-4 md:mb-0">
        <h1 class="text-3xl font-bold text-white mb-1"><i class="fa-solid fa-satellite-dish text-red-500 me-2"></i> Console Monitor</h1>
        <p class="text-gray-400 text-sm">Real-time status & management panel</p>
      </div>
      <a href="dashboard_admin.php" class="px-4 py-2 bg-red-900/30 text-red-400 hover:bg-red-600 hover:text-white rounded-lg border border-red-500/50 transition flex items-center gap-2">
        <i class="fa-solid fa-gauge"></i> Back to Dashboard
      </a>
    </div>

    <!-- Grid Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <?php foreach($consoles as $c): 
        $glowClass = 'glow-'.$c['status'];
        $badgeClass = ''; $badgeText = '';
        if($c['status'] == 'tersedia') { $badgeClass = 'bg-green-500/20 text-green-400'; $badgeText = 'Tersedia'; }
        elseif($c['status'] == 'digunakan') { $badgeClass = 'bg-red-500/20 text-red-400'; $badgeText = 'Digunakan'; }
        elseif($c['status'] == 'pause') { $badgeClass = 'bg-yellow-500/20 text-yellow-400 animate-pulse'; $badgeText = 'Paused'; }
      ?>
      <div class="glass-card <?= $glowClass ?> rounded-xl p-5 flex flex-col justify-between h-full floating">
        <div>
          <div class="flex justify-between items-start mb-4">
            <h3 class="text-lg font-bold text-white w-2/3 leading-tight"><?= $c['nama'] ?></h3>
            <span class="px-2 py-1 rounded text-xs font-semibold uppercase tracking-wider <?= $badgeClass ?> border border-current opacity-80">
              <?= $badgeText ?>
            </span>
          </div>
          
          <!-- Timer Display -->
          <div class="bg-black/60 rounded-lg p-4 text-center mb-6 border border-gray-800">
            <p class="text-xs text-gray-500 uppercase tracking-widest mb-1">Time Elapsed</p>
            <p class="text-2xl font-mono tracking-widest <?= $c['status'] == 'digunakan' ? 'text-red-500 drop-shadow-[0_0_8px_rgba(239,68,68,0.8)]' : ($c['status'] == 'pause' ? 'text-yellow-500' : 'text-gray-600') ?>">
              <?= $c['timer'] ?>
            </p>
          </div>
        </div>

        <!-- Controls -->
        <div class="grid grid-cols-2 gap-2 mt-auto">
          <?php if($c['status'] == 'tersedia'): ?>
            <button class="col-span-2 btn-glow-green py-2 rounded-lg font-semibold flex items-center justify-center gap-2">
              <i class="fa-solid fa-play"></i> Start Session
            </button>
          <?php elseif($c['status'] == 'digunakan'): ?>
            <button class="btn-glow-yellow py-2 rounded-lg font-semibold flex items-center justify-center gap-2 text-sm">
              <i class="fa-solid fa-pause"></i> Pause
            </button>
            <button class="btn-glow-blue py-2 rounded-lg font-semibold flex items-center justify-center gap-2 text-sm">
              <i class="fa-solid fa-plus"></i> Extend
            </button>
            <button class="col-span-2 mt-1 btn-glow-red py-2 rounded-lg font-semibold flex items-center justify-center gap-2 text-sm">
              <i class="fa-solid fa-stop"></i> Stop Session
            </button>
          <?php elseif($c['status'] == 'pause'): ?>
            <button class="btn-glow-green py-2 rounded-lg font-semibold flex items-center justify-center gap-2 text-sm">
              <i class="fa-solid fa-play"></i> Resume
            </button>
            <button class="btn-glow-red py-2 rounded-lg font-semibold flex items-center justify-center gap-2 text-sm">
              <i class="fa-solid fa-stop"></i> Stop
            </button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

  </div>

</body>
</html>
