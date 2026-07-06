<?php
$files = ['logout.php', 'admin_logout.php', 'logout_superadmin.php'];
foreach ($files as $file) {
    if(!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    if (strpos($content, 'assest/css/style.css') === false) {
        $injection = "
    <!-- Custom CSS -->
    <link rel=\"stylesheet\" href=\"assest/css/style.css?v=".time()."\">
    <script>
      // Load Comic Mode from LocalStorage
      if (localStorage.getItem('theme') === 'comic') {
          document.documentElement.classList.add('comic-mode');
          document.addEventListener('DOMContentLoaded', () => {
              document.body.classList.add('comic-mode');
          });
      }
    </script>
</head>";
        $content = str_replace("</head>", $injection, $content);
        file_put_contents($file, $content);
        echo "Patched CSS in $file\n";
    }
}
?>
