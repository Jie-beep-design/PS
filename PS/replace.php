<?php
$files = glob("*.php");

$replacements = [
    // Database table & column names
    "FROM admin " => "FROM admin_cabang ",
    "FROM admin\n" => "FROM admin_cabang\n",
    "JOIN admin " => "JOIN admin_cabang ",
    "INTO admin " => "INTO admin_cabang ",
    "UPDATE admin " => "UPDATE admin_cabang ",
    "DESCRIBE admin" => "DESCRIBE admin_cabang",
    "ALTER TABLE admin " => "ALTER TABLE admin_cabang ",
    "admin_id" => "admin_cabang_id",
];

// Special cases that should NOT use admin_cabang or need different fixes
// 1. superadmin login query should use `admin` table instead of admin_cabang
// 2. buat_superadmin.php
// 3. kelola_admin.php insert query role removal

foreach ($files as $file) {
    if ($file == 'update_db_split_admin.php' || $file == 'replace.php') continue;

    $content = file_get_contents($file);
    $orig_content = $content;

    foreach ($replacements as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }
    
    // Reverse admin_cabang_id to admin_id where it's session variables to avoid breaking existing session structure
    // Actually, I can just leave admin_cabang_id in the session if I want, or revert to admin_id for session.
    // Let's keep session as `$_SESSION['admin_id']` for branch admin.
    $content = str_replace("\$_SESSION['admin_cabang_id']", "\$_SESSION['admin_id']", $content);
    
    if ($content !== $orig_content) {
        file_put_contents($file, $content);
        echo "Updated $file\n";
    }
}
?>
