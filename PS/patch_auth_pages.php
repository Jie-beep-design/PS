<?php
$auth_files = [
    'login.php',
    'register.php',
    'forgot_password.php',
    'reset_password.php',
    'admin_login.php',
    'admin_register.php',
    'admin_forgot_password.php',
    'admin_reset_password.php',
    'login_superadmin.php',
    'buat_superadmin.php'
];

$js_logic = <<<EOD
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
EOD;

foreach ($auth_files as $file) {
    $filepath = __DIR__ . '/' . $file;
    if (!file_exists($filepath)) continue;
    
    $content = file_get_contents($filepath);
    $modified = false;
    
    // Inject JS
    if (strpos($content, '// Theme Toggle Logic') === false && strpos($content, '</body>') !== false) {
        $content = str_replace('</body>', $js_logic, $content);
        $modified = true;
    }
    
    if ($modified) {
        file_put_contents($filepath, $content);
        echo "Patched auth page: " . $file . "\n";
    }
}
echo "Done patching auth pages!\n";
