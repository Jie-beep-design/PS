<?php
session_start();
include 'config/db.php';

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if ($booking_id == 0) {
    header("Location: dashboard.php");
    exit();
}

$query = "SELECT b.*, c.harga_per_jam, c.nama_console, a.qris_image, a.dana_image, a.nama_rental 
          FROM bookings b
          JOIN consoles c ON b.console_id = c.id
          JOIN admin_cabang a ON c.admin_cabang_id = a.id
          WHERE b.id = $booking_id";

$result = mysqli_query($conn, $query);
if (!$result || mysqli_num_rows($result) == 0) {
    echo "Data booking tidak ditemukan.";
    exit();
}

$booking = mysqli_fetch_assoc($result);
$total_harga = $booking['durasi_tersisa'] * $booking['harga_per_jam'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['batal'])) {
        $console_id = $booking['console_id'];
        $res_console = mysqli_query($conn, "SELECT admin_cabang_id FROM consoles WHERE id = $console_id");
        $branch_id_res = mysqli_fetch_assoc($res_console)['admin_cabang_id'];
        
        // Hapus booking
        $delete_query = "DELETE FROM bookings WHERE id = $booking_id";
        mysqli_query($conn, $delete_query);
        
        // Redirect ke dashboard
        header("Location: dashboard.php?branch_id=" . $branch_id_res);
        exit();
    } else {
        $bukti_path = '';
        if (isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] == 0) {
            if ($_FILES['bukti_pembayaran']['size'] > 2 * 1024 * 1024) {
                $error_msg = "Ukuran file terlalu besar! Maksimal 2MB.";
            } else {
                $target_dir = "uploads/";
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                
                $imageFileType = strtolower(pathinfo($_FILES["bukti_pembayaran"]["name"], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (in_array($imageFileType, $allowed)) {
                    $filename = time() . '_' . rand(1000,9999) . '_bukti.jpg';
                    $target_file = $target_dir . $filename;
                    
                    $source = $_FILES["bukti_pembayaran"]["tmp_name"];
                    $info = @getimagesize($source);
                    $image = null;
                    if ($info && function_exists('imagecreatefromjpeg') && function_exists('imagecreatefrompng') && function_exists('imagejpeg') && function_exists('imagedestroy')) {
                        if ($info['mime'] == 'image/jpeg') $image = @imagecreatefromjpeg($source);
                        elseif ($info['mime'] == 'image/png') {
                            $image = @imagecreatefrompng($source);
                            if ($image && function_exists('imagecreatetruecolor')) {
                                // Handle transparency for PNG
                                $bg = @imagecreatetruecolor(imagesx($image), imagesy($image));
                                if ($bg) {
                                    imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
                                    imagealphablending($bg, TRUE);
                                    imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
                                    imagedestroy($image);
                                    $image = $bg;
                                }
                            }
                        }
                        elseif ($info['mime'] == 'image/webp' && function_exists('imagecreatefromwebp')) {
                            $image = @imagecreatefromwebp($source);
                        }
                    }
                    
                    if ($image) {
                        imagejpeg($image, $target_file, 60); // Compress to 60% quality
                        imagedestroy($image);
                        $bukti_path = $target_file;
                    } else {
                        // Fallback if compression fails
                        $fallback_file = $target_dir . time() . '_' . basename($_FILES["bukti_pembayaran"]["name"]);
                        if (move_uploaded_file($source, $fallback_file)) {
                            $bukti_path = $fallback_file;
                        }
                    }
                } else {
                    $error_msg = "Format file tidak didukung (Hanya JPG, PNG, WEBP).";
                }
            }
        }
        
        if ($bukti_path) {
            $update_query = "UPDATE bookings SET status_pembayaran = 'sudah bayar', bukti_pembayaran = '$bukti_path' WHERE id = $booking_id";
            mysqli_query($conn, $update_query);
            header("Location: status_sewa.php?success=1");
            exit();
        } elseif (!isset($error_msg)) {
            $error_msg = "Bukti pembayaran valid wajib diunggah.";
        }
    }
}

// Fallback images if not set
$qris_img = $booking['qris_image'] ? $booking['qris_image'] : 'https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - RumahPS</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assest/css/style.css?v=<?= time() ?>">
    <style>
        .payment-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('https://images.unsplash.com/photo-1542751371-adc38448a05e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            z-index: 1;
            padding: 2rem 0;
        }
        .payment-container::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: var(--bg-gradient);
            opacity: 0.9;
            z-index: -1;
        }
        .qr-code {
            max-width: 250px;
            border-radius: 10px;
            border: 2px solid var(--neon-blue);
            padding: 10px;
            background: white;
        }
    </style>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="payment-container">
    <div class="glass-card mx-auto" style="width: 100%; max-width: 600px; padding: 3rem 2.5rem;">
        <div class="text-center mb-4">
            <!-- Animated Icon Showcase -->
            <div class="ps-showcase-container ps-showcase-fade large-icon mx-auto mb-4" style="height: 120px; width: 120px;">
                <i class="fa-solid fa-gamepad ps-icon-showcase icon-1"></i>
                <i class="fa-solid fa-vr-cardboard ps-icon-showcase icon-2"></i>
                <i class="fa-solid fa-headset ps-icon-showcase icon-3"></i>
            </div>
            <h3 class="text-white fw-bold">Selesaikan <span class="neon-text-blue">Pembayaran</span></h3>
        </div>

        <?php if(isset($error_msg)): ?>
            <div class="alert alert-danger bg-danger bg-opacity-25 text-white border-danger mb-4">
                <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <div class="mb-4 p-3 rounded text-center" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
            <p class="text-white mb-1">Total Tagihan:</p>
            <h2 class="neon-text-green fw-bold">Rp <?= number_format($total_harga, 0, ',', '.') ?></h2>
            <p class="text-muted small mt-2">Untuk sewa <?= htmlspecialchars($booking['nama_console']) ?> (<?= $booking['durasi_tersisa'] ?> Jam)</p>
        </div>

        <div class="text-center mb-4">
            <h5 class="text-white mb-3">Scan QR Code Pembayaran</h5>
            <div class="bg-white p-2 d-inline-block rounded mb-3">
                <img src="<?= htmlspecialchars($qris_img) ?>" alt="QRIS" class="qr-code img-fluid" style="max-width: 200px; border: none;">
            </div>
            <div>
                <a href="<?= htmlspecialchars($qris_img) ?>" download="QR_Code_Pembayaran.png" class="btn btn-sm btn-outline-info">
                    <i class="fa-solid fa-download me-1"></i> Download QR Code
                </a>
            </div>
            <p class="text-muted small mt-2">Mendukung semua e-Wallet & M-Banking</p>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <label class="form-label text-white fw-light"><i class="fa-solid fa-upload me-2 text-info"></i>Upload Bukti Pembayaran</label>
                <input type="file" name="bukti_pembayaran" class="form-control form-control-glass text-white" accept="image/*" required>
                <small class="text-muted d-block mt-1">Silakan upload foto/screenshot bukti transfer (Wajib).</small>
            </div>

            <button type="submit" name="bayar" class="btn btn-neon w-100 py-3 fs-5 mb-3">
                <i class="fa-solid fa-check-circle me-2"></i> Saya Sudah Bayar
            </button>
            <button type="submit" name="batal" class="btn btn-outline-danger w-100 py-2" formnovalidate onclick="event.preventDefault(); let form = this.closest('form'); Swal.fire({title: 'Konfirmasi', text: 'Yakin ingin membatalkan sewa ini?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#00f3ff', cancelButtonColor: '#bc13fe', confirmButtonText: 'Ya, Lanjutkan', cancelButtonText: 'Batal', background: '#0f172a', color: '#fff'}).then((result) => { if (result.isConfirmed) { let input = document.createElement('input'); input.type = 'hidden'; input.name = 'batal'; input.value = '1'; form.appendChild(input); form.submit(); } });">
                <i class="fa-solid fa-xmark me-2"></i> Batalkan Sewa
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
