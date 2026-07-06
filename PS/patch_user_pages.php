<?php
$user_files = [
    'dashboard.php',
    'status_sewa.php',
    'form_sewa.php',
    'jadwal_console.php',
    'pembayaran.php',
    'beranda_cabang.php',
    'rumah_playstation.php'
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

$toggle_btn_html = '
            <div class="d-flex align-items-center me-3 mb-2 mb-lg-0">
                <button id="theme-toggle" class="btn btn-sm btn-outline-light" style="border-radius: 20px; font-weight: bold; border-width: 2px;">
                    <i id="theme-icon" class="fa-solid fa-moon"></i> <span id="theme-text" class="d-none d-lg-inline">Dark Mode</span>
                </button>
            </div>
            <div class="d-flex align-items-center dropdown">';

foreach ($user_files as $file) {
    $filepath = __DIR__ . '/' . $file;
    if (!file_exists($filepath)) continue;
    
    $content = file_get_contents($filepath);
    $modified = false;
    
    // Inject button
    if (strpos($content, 'id="theme-toggle"') === false) {
        // Try to replace the dropdown div
        $content = str_replace('<div class="d-flex align-items-center dropdown">', $toggle_btn_html, $content);
        $modified = true;
    }
    
    // Inject JS
    if (strpos($content, '// Theme Toggle Logic') === false && strpos($content, '</body>') !== false) {
        $content = str_replace('</body>', $js_logic, $content);
        $modified = true;
    }
    
    if ($modified) {
        file_put_contents($filepath, $content);
        echo "Patched: " . $file . "\n";
    }
}
echo "Done patching user pages!\n";
