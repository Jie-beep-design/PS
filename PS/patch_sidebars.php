<?php
$admin_links = [
    'dashboard_admin.php' => ['Dashboard', 'fa-gauge', 'Ringkasan live monitor, status console, dan performa rental hari ini.'],
    'data_console.php' => ['Data Console', 'fa-desktop', 'Manajemen daftar console PS, tambah, edit, atau hapus perangkat.'],
    'penyewaan.php' => ['Penyewaan', 'fa-file-invoice-dollar', 'Pantau seluruh transaksi aktif, riwayat booking, dan approval pembayaran.'],
    'laporan.php' => ['Laporan', 'fa-chart-line', 'Laporan pendapatan finansial detail harian dan bulanan cabang.'],
    'pengaturan_pembayaran.php' => ['Metode Pembayaran', 'fa-qrcode', 'Atur informasi rekening dan e-wallet QRIS untuk menerima pembayaran.'],
    'pengaturan_toko.php' => ['Pengaturan Toko', 'fa-store', 'Kelola status buka/tutup toko, jam operasional, dan keterangan cabang.']
];

$super_links = [
    'dashboard_superadmin.php' => ['Global Dashboard', 'fa-chart-line', 'Ringkasan eksekutif, total pendapatan seluruh cabang, dan jumlah console aktif.'],
    'kelola_admin.php' => ['Kelola Admin', 'fa-users-gear', 'Tambah, edit, atau blokir akun Admin Pengelola Cabang.'],
    'data_cabang.php' => ['Data Cabang', 'fa-store', 'Manajemen alamat, status operasional, dan info toko seluruh cabang.'],
    'laporan_global.php' => ['Laporan Global', 'fa-file-contract', 'Analisis finansial menyeluruh dan unduh laporan performa rental bulanan.'],
    'kelola_laporan_masalah.php' => ['Laporan Bug', 'fa-bug', 'Cek laporan kendala atau bug dari user maupun admin cabang.']
];

$all_links = array_merge($admin_links, $super_links);

$js_code = <<<EOD

<!-- Dynamic Hover Preview Container & Script -->
<div id="menu-preview" class="menu-preview-card">
    <h6><i id="preview-icon" class="fa-solid fa-info-circle"></i> <span id="preview-title">Title</span></h6>
    <p id="preview-desc">Description</p>
</div>
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
        themeToggleBtn.addEventListener('click', () => {
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

    // Mobile Sidebar Toggle
    const toggleBtn = document.querySelector('.btn-outline-light.d-md-none');
    const sidebar = document.querySelector('.sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            sidebar.classList.toggle('show');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target) && sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }

    const previewCard = document.getElementById('menu-preview');
    const previewTitle = document.getElementById('preview-title');
    const previewDesc = document.getElementById('preview-desc');
    const previewIconEl = document.getElementById('preview-icon');
    
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.addEventListener('mouseenter', (e) => {
            if(window.innerWidth < 768) return; // Disable on mobile
            const title = link.getAttribute('data-preview-title');
            if(title) {
                previewTitle.innerText = title;
                previewDesc.innerText = link.getAttribute('data-preview-desc');
                previewIconEl.className = `fa-solid \${link.getAttribute('data-preview-icon')}`;
                previewCard.classList.add('show');
            }
        });
        
        link.addEventListener('mousemove', (e) => {
            if(window.innerWidth < 768) return;
            // offset so cursor doesn't block the card
            previewCard.style.left = (e.clientX + 20) + 'px';
            previewCard.style.top = (e.clientY + 20) + 'px';
        });
        
        link.addEventListener('mouseleave', () => {
            previewCard.classList.remove('show');
        });
    });
});
</script>

</body>
EOD;

$dir = __DIR__;
$files = glob($dir . '/*.php');

foreach ($files as $file) {
    if (basename($file) == 'patch_sidebars.php') continue;
    
    $content = file_get_contents($file);
    
    // Only process files that have the sidebar
    if (strpos($content, 'sidebar-link') === false) continue;
    
    $modified = false;
    
    // Fix sidebar visibility on mobile
    if (strpos($content, 'd-none d-md-flex') !== false) {
        $content = str_replace('d-none d-md-flex', 'd-flex', $content);
        $modified = true;
    }
    
    // Inject data attributes
    foreach ($all_links as $href => $data) {
        $title = $data[0];
        $icon = $data[1];
        $desc = $data[2];
        
        // Regex to find: <a href="page.php" class="sidebar-link active"> or <a href="page.php" class="sidebar-link">
        // that do NOT already have data-preview-title
        $pattern = '/<a\s+href="' . preg_quote($href, '/') . '"\s+class="([^"]*sidebar-link[^"]*)"(?!.*data-preview-title)/is';
        
        $replacement = '<a href="' . $href . '" class="$1" data-preview-title="' . $title . '" data-preview-icon="' . $icon . '" data-preview-desc="' . $desc . '">';
        
        $new_content = preg_replace($pattern, $replacement, $content);
        if ($new_content !== null && $new_content !== $content) {
            $content = $new_content;
            $modified = true;
        }
    }
    
    // Replace old JS with new JS
    // Remove old JS block if it exists (from <!-- Dynamic Hover to </script>)
    $old_js_pattern = '/<!-- Dynamic Hover Preview Container & Script -->.*?<\/script>/is';
    if (preg_match($old_js_pattern, $content)) {
        $content = preg_replace($old_js_pattern, '', $content);
        $modified = true;
    }
    
    // Inject JS before </body>
    if (strpos($content, '</body>') !== false) {
        $content = str_replace('</body>', $js_code, $content);
        $modified = true;
    }
    
    // Inject Theme Toggle Button
    if (strpos($content, 'id="theme-toggle"') === false) {
        $toggle_btn_html = '
        <div class="text-center mt-2 mb-3">
            <button id="theme-toggle" class="btn btn-sm btn-outline-light" style="border-radius: 20px; font-weight: bold; border-width: 2px;">
                <i id="theme-icon" class="fa-solid fa-moon"></i> <span id="theme-text">Dark Mode</span>
            </button>
        </div>
        <nav';
        
        $new_content = preg_replace('/<nav\s+class="nav/is', $toggle_btn_html . ' class="nav', $content, 1, $count);
        if ($new_content !== null && $count > 0) {
            $content = $new_content;
            $modified = true;
        }
    }
    
    // Cache bust CSS if needed
    if (strpos($content, 'style.css"') !== false) {
        $content = str_replace('style.css"', 'style.css?v=<?= time() ?>"', $content);
        $modified = true;
    }
    
    // Also inject alert-animated
    if (strpos($content, 'alert-<?= $action_type ?>') !== false && strpos($content, 'alert-animated') === false) {
        $content = str_replace('alert-<?= $action_type ?>', 'alert-animated alert-<?= $action_type ?>', $content);
        $modified = true;
    }
    
    if ($modified) {
        file_put_contents($file, $content);
        echo "Patched: " . basename($file) . "\n";
    }
}
echo "Done patching!\n";
