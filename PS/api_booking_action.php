<?php
session_start();
include 'config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? '';
$booking_id = $_POST['booking_id'] ?? 0;
$console_id_post = $_POST['console_id'] ?? 0;

if (!$action) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    exit();
}

if ($action == 'delete_console') {
    if (!$console_id_post) {
        echo json_encode(['status' => 'error', 'message' => 'Console ID missing']);
        exit();
    }
    // Check if it belongs to admin
    $admin_cabang_id = $_SESSION['admin_id'];
    $check = mysqli_query($conn, "SELECT id FROM consoles WHERE id = '$console_id_post' AND admin_cabang_id = '$admin_cabang_id'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "DELETE FROM consoles WHERE id = '$console_id_post'");
        echo json_encode(['status' => 'success', 'message' => 'Console berhasil dihapus']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Akses ditolak']);
    }
    exit();
}

if ($action == 'start_new') {
    if (!$console_id_post) {
        echo json_encode(['status' => 'error', 'message' => 'Console ID missing']);
        exit();
    }
    $tambahan_jam = (int)($_POST['hours'] ?? 1);
    if($tambahan_jam <= 0) $tambahan_jam = 1;
    
    $jam_mulai = date('H:i:s');
    $jam_selesai = date('H:i:s', strtotime("+$tambahan_jam hours"));
    $tanggal = date('Y-m-d');
    
    // Check conflict
    $query_cek = "SELECT id FROM bookings 
                  WHERE console_id = '$console_id_post' 
                  AND tanggal = '$tanggal' 
                  AND status != 'selesai'
                  AND status_pembayaran = 'sudah bayar'
                  AND (jam_mulai < '$jam_selesai') 
                  AND (jam_selesai > '$jam_mulai')";
                  
    $result_cek = mysqli_query($conn, $query_cek);
    if (mysqli_num_rows($result_cek) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak bisa mulai, bentrok dengan jadwal booking lain.']);
        exit();
    }
    
    $insert = "INSERT INTO bookings (user_id, console_id, tanggal, jam_mulai, jam_selesai, status) 
               VALUES (0, '$console_id_post', '$tanggal', '$jam_mulai', '$jam_selesai', 'aktif')";
    if(mysqli_query($conn, $insert)) {
        mysqli_query($conn, "UPDATE consoles SET status = 'digunakan' WHERE id = '$console_id_post'");
        echo json_encode(['status' => 'success', 'message' => "Console dimulai ($tambahan_jam Jam)"]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memulai session']);
    }
    exit();
}

if (!$booking_id) {
    echo json_encode(['status' => 'error', 'message' => 'Booking ID missing']);
    exit();
}

// Fetch current booking data
$query = "SELECT * FROM bookings WHERE id = '$booking_id'";
$result = mysqli_query($conn, $query);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    echo json_encode(['status' => 'error', 'message' => 'Booking not found']);
    exit();
}

$console_id = $booking['console_id'];
$tanggal = $booking['tanggal'];

if ($action == 'start') {
    // Check if it's pending
    if ($booking['status'] == 'pending') {
        $update = "UPDATE bookings SET status = 'aktif', jam_mulai = CURTIME() WHERE id = '$booking_id'";
        if (mysqli_query($conn, $update)) {
            // update console status
            mysqli_query($conn, "UPDATE consoles SET status = 'digunakan' WHERE id = '$console_id'");
            echo json_encode(['status' => 'success', 'message' => 'Console started']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'DB error']);
        }
    } else {
         echo json_encode(['status' => 'error', 'message' => 'Booking not in pending state']);
    }
} elseif ($action == 'pause') {
    if ($booking['status'] == 'aktif') {
        // Calculate remaining seconds
        $end_time = strtotime($tanggal . ' ' . $booking['jam_selesai']);
        $now = time();
        $remaining = max(0, $end_time - $now);

        $update = "UPDATE bookings SET status = 'pause', durasi_tersisa = $remaining WHERE id = '$booking_id'";
        if (mysqli_query($conn, $update)) {
            mysqli_query($conn, "UPDATE consoles SET status = 'pause' WHERE id = '$console_id'");
            echo json_encode(['status' => 'success', 'message' => 'Console paused', 'remaining' => $remaining]);
        } else {
             echo json_encode(['status' => 'error', 'message' => 'DB error']);
        }
    } else {
         echo json_encode(['status' => 'error', 'message' => 'Booking is not active']);
    }
} elseif ($action == 'resume') {
    if ($booking['status'] == 'pause') {
        $remaining = $booking['durasi_tersisa'];
        
        $now = time();
        $new_end_time = $now + $remaining;
        $new_jam_selesai = date('H:i:s', $new_end_time);

        // Check for conflict
        $query_cek = "SELECT id FROM bookings 
                      WHERE console_id = '$console_id' 
                      AND tanggal = '$tanggal' 
                      AND status != 'selesai'
                      AND status_pembayaran = 'sudah bayar'
                      AND id != '$booking_id'
                      AND (jam_mulai < '$new_jam_selesai') 
                      AND (jam_selesai > CURTIME())";
                      
        $result_cek = mysqli_query($conn, $query_cek);
        
        if (mysqli_num_rows($result_cek) > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak bisa resume, akan menabrak jadwal booking berikutnya!']);
            exit();
        }

        $update = "UPDATE bookings SET status = 'aktif', jam_selesai = '$new_jam_selesai', durasi_tersisa = 0 WHERE id = '$booking_id'";
        if (mysqli_query($conn, $update)) {
            mysqli_query($conn, "UPDATE consoles SET status = 'digunakan' WHERE id = '$console_id'");
            echo json_encode(['status' => 'success', 'message' => 'Console resumed', 'new_end' => $new_jam_selesai]);
        } else {
             echo json_encode(['status' => 'error', 'message' => 'DB error']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Booking is not paused']);
    }
} elseif ($action == 'stop') {
    $update = "UPDATE bookings SET status = 'selesai' WHERE id = '$booking_id'";
    if (mysqli_query($conn, $update)) {
        mysqli_query($conn, "UPDATE consoles SET status = 'tersedia' WHERE id = '$console_id'");
        echo json_encode(['status' => 'success', 'message' => 'Console stopped']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'DB error']);
    }
} elseif ($action == 'extend') {
    $tambahan_jam = (int)($_POST['hours'] ?? 1);
    if($tambahan_jam <= 0) $tambahan_jam = 1;

    $jam_selesai_sekarang = $booking['jam_selesai'];
    $jam_selesai_baru = date('H:i:s', strtotime("$jam_selesai_sekarang + $tambahan_jam hours"));
    
    // Check conflict
    $query_cek = "SELECT id FROM bookings 
                  WHERE console_id = '$console_id' 
                  AND tanggal = '$tanggal' 
                  AND status != 'selesai'
                  AND status_pembayaran = 'sudah bayar'
                  AND id != '$booking_id'
                  AND (jam_mulai < '$jam_selesai_baru') 
                  AND (jam_selesai > '$jam_selesai_sekarang')";
                  
    $result_cek = mysqli_query($conn, $query_cek);
    
    if (mysqli_num_rows($result_cek) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak bisa extend, jadwal berikutnya sudah ada.']);
    } else {
        $update = "UPDATE bookings 
                   SET jam_selesai = '$jam_selesai_baru' 
                   WHERE id = '$booking_id'";
        if(mysqli_query($conn, $update)) {
            echo json_encode(['status' => 'success', 'message' => "Waktu berhasil di-extend $tambahan_jam jam."]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'DB error']);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
}
?>
