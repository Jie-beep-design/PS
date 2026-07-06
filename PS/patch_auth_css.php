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

$css_link = '    <!-- Custom CSS -->
    <link rel="stylesheet" href="assest/css/style.css?v=' . time() . '">
';

foreach ($auth_files as $file) {
    $filepath = __DIR__ . '/' . $file;
    if (!file_exists($filepath)) continue;
    
    $content = file_get_contents($filepath);
    $modified = false;
    
    // Inject CSS link
    if (strpos($content, 'assest/css/style.css') === false) {
        if (strpos($content, '</head>') !== false) {
            $content = str_replace('</head>', $css_link . '</head>', $content);
            $modified = true;
        }
    }
    
    if ($modified) {
        file_put_contents($filepath, $content);
        echo "Injected style.css into: " . $file . "\n";
    }
}
echo "Done injecting CSS!\n";
