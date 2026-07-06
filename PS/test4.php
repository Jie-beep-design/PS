<?php
session_start();
$_SESSION['admin_id'] = 1;
$_SESSION['admin_name'] = 'Admin';
$_GET['bulan'] = '07';
$_GET['tahun'] = '2026';
ob_start();
include 'laporan.php';
$html = ob_get_clean();

// Check if doni is in html
if (strpos($html, 'doni') !== false) {
    echo "DONI IS IN HTML!\n";
} else {
    echo "DONI NOT FOUND IN HTML!\n";
}

// Find total income
preg_match('/<h2 class="neon-text-green m-0">(Rp [0-9\.]+)<\/h2>/', $html, $matches);
if($matches) {
    echo "Total Income Displayed: " . $matches[1] . "\n";
} else {
    echo "Total Income NOT FOUND\n";
}
?>
