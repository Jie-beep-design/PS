<?php
function patchFile($file, $search, $replace) {
    if(!file_exists($file)) return;
    $content = file_get_contents($file);
    $content = str_replace($search, $replace, $content);
    file_put_contents($file, $content);
    echo "Patched div in $file\n";
}

patchFile('logout.php', '<div class="text-center">', '<div class="text-center glass p-8 rounded-2xl">');
patchFile('admin_logout.php', '<div class="text-center bg-gray-900/60 backdrop-blur-md border border-red-500/30 p-8 rounded-2xl shadow-[0_0_30px_rgba(220,38,38,0.3)] floating">', '<div class="text-center glass p-8 rounded-2xl floating">');
patchFile('logout_superadmin.php', '<div class="text-center bg-gray-900/60 backdrop-blur-md border border-yellow-500/30 p-10 rounded-2xl shadow-[0_0_40px_rgba(255,215,0,0.2)] floating w-full max-w-md">', '<div class="text-center glass p-10 rounded-2xl floating w-full max-w-md">');
?>
