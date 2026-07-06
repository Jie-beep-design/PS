<?php
session_start();
include 'config/db.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $message = "Email & password kosong!";
    } else {
        $query = "SELECT * FROM users WHERE email='$email'";
        $result = mysqli_query($conn, $query);

        if ($data = mysqli_fetch_assoc($result)) {
            if ($data['password'] == $password) {
                $_SESSION['user_id'] = $data['id'];
                $_SESSION['username'] = $data['nama'];
                $_SESSION['role'] = $data['role'];

                header("Location: dashboard.php");
                exit();
            } else {
                $message = "Password salah!";
            }
        } else {
            $message = "Email tidak ditemukan!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RumahPS</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assest/css/style.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('https://images.unsplash.com/photo-1552820728-8b83bb6b773f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') no-repeat center center;
            background-size: cover;
            position: relative;
            z-index: 1;
        }
        .login-container::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: var(--bg-gradient);
            opacity: 0.85;
            z-index: -1;
        }
        .login-card {
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
            animation: float 6s ease-in-out infinite;
        }
        .brand-logo {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 2rem;
            letter-spacing: 2px;
        }
    </style>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="login-container">
    <div class="glass-card login-card">
        <div class="brand-logo neon-text-blue">
            <i class="fa-solid fa-gamepad me-2"></i>Rumah<span class="neon-text-purple">PS</span>
        </div>
        
        <h4 class="text-center mb-4 fw-light text-white">System <span class="fw-bold">Login</span></h4>
        
        <?php if($message): ?>
            <div class="alert alert-danger bg-danger bg-opacity-25 text-white border-danger mb-4" style="backdrop-filter: blur(5px);">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text input-group-text-glass">
                        <i class="fa-solid fa-envelope"></i>
                    </span>
                    <input type="email" name="email" class="form-control form-control-glass border-start-0" placeholder="Email Address" required>
                </div>
            </div>
            
            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text input-group-text-glass">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" name="password" class="form-control form-control-glass border-start-0" placeholder="Password" required>
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input bg-dark border-secondary" type="checkbox" id="rememberMe">
                    <label class="form-check-label text-muted" for="rememberMe">
                        Remember me
                    </label>
                </div>
                <a href="forgot_password.php" class="text-decoration-none neon-text-blue" style="font-size: 0.9rem;">Forgot password?</a>
            </div>
            
            <button type="submit" class="btn btn-neon w-100 py-2 fs-5 mb-4">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Initialize Login
            </button>
            
            <div class="text-center">
                <p class="text-muted mb-3">Don't have an access key? 
                    <a href="register.php" class="text-decoration-none neon-text-purple fw-bold ms-1">Register Now</a>
                </p>
                <div class="border-top border-secondary pt-3 mt-3">
                    <a href="rumah_playstation.php" class="text-decoration-none text-muted hover:text-white transition" style="font-size: 0.9rem;">
                        <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Menu Utama
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
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
