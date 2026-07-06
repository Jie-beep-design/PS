<?php
$dir = __DIR__;
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php' && $file->getFilename() !== 'patch_sweetalert.php') {
        $path = $file->getRealPath();
        $content = file_get_contents($path);
        $modified = false;

        // Add SweetAlert2 to <head> if not exists
        if (strpos($content, '</head>') !== false && strpos($content, 'sweetalert2') === false) {
            $swal_cdn = "\n    <!-- SweetAlert2 -->\n    <script src=\"https://cdn.jsdelivr.net/npm/sweetalert2@11\"></script>\n</head>";
            $content = str_replace('</head>', $swal_cdn, $content);
            $modified = true;
        }

        // Replace onclick="return confirm('...')"
        if (preg_match_all('/onclick="return confirm\(\'(.*?)\'\)[;]*"/', $content, $matches)) {
            foreach ($matches[0] as $index => $matchStr) {
                $msg = $matches[1][$index];
                $newStr = 'onclick="event.preventDefault(); Swal.fire({title: \'Konfirmasi\', text: \''.$msg.'\', icon: \'warning\', showCancelButton: true, confirmButtonColor: \'#00f3ff\', cancelButtonColor: \'#bc13fe\', confirmButtonText: \'Ya, Lanjutkan\', cancelButtonText: \'Batal\', background: \'#0f172a\', color: \'#fff\'}).then((result) => { if (result.isConfirmed) { if(this.tagName === \'A\'){ window.location.href = this.href; } else { this.closest(\'form\').submit(); } } });"';
                $content = str_replace($matchStr, $newStr, $content);
                $modified = true;
            }
        }

        // Replace onsubmit="return confirm('...')"
        if (preg_match_all('/onsubmit="return confirm\(\'(.*?)\'\)[;]*"/', $content, $matches)) {
            foreach ($matches[0] as $index => $matchStr) {
                $msg = $matches[1][$index];
                $newStr = 'onsubmit="event.preventDefault(); Swal.fire({title: \'Konfirmasi\', text: \''.$msg.'\', icon: \'warning\', showCancelButton: true, confirmButtonColor: \'#00f3ff\', cancelButtonColor: \'#bc13fe\', confirmButtonText: \'Ya, Lanjutkan\', cancelButtonText: \'Batal\', background: \'#0f172a\', color: \'#fff\'}).then((result) => { if (result.isConfirmed) this.submit(); });"';
                $content = str_replace($matchStr, $newStr, $content);
                $modified = true;
            }
        }

        if ($modified) {
            file_put_contents($path, $content);
            echo "Patched: " . $file->getFilename() . "\n";
        }
    }
}
echo "Done.";
?>
