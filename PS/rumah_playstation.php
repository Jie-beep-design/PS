<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Rumah PlayStation</title>
  <script src="https://cdn.tailwindcss.com"></script>

  <style>
    .bg-container {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
    }

    .fade {
      transition: opacity 1s ease-in-out;
      opacity: 0;
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .fade.show {
      opacity: 1;
    }

    /* GLASS EFFECT */
    .glass {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* GLOW BUTTON */
    .glow-btn {
      transition: 0.3s;
      box-shadow: 0 0 10px rgba(59,130,246,0.5);
    }

    .glow-btn:hover {
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 0 25px rgba(59,130,246,0.9);
    }

    /* FLOATING EFFECT */
    @keyframes float {
      0% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
      100% { transform: translateY(0px); }
    }

    .floating {
      animation: float 4s ease-in-out infinite;
    }

    .developer-link {
      position: fixed;
      top: 1rem;
      right: 1rem;
      color: #ffffffcc;
      font-size: 0.875rem;
      z-index: 30;
      text-decoration: none;
    }

    .developer-link:hover {
      color: #ffffff;
      text-decoration: underline;
    }
  </style>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="relative min-h-screen w-full flex items-center justify-center text-white overflow-hidden">

  <!-- Background -->
  <div id="background" class="bg-container"></div>

  <!-- Overlay -->
  <div class="absolute inset-0 bg-black/70 z-10"></div>

  <!-- CONTENT -->
  <div class="z-20 text-center px-6">

    <!-- CARD UTAMA -->
    <div class="glass rounded-2xl p-10 shadow-2xl floating">

      <h1 class="text-5xl font-bold mb-4 text-blue-400 drop-shadow-lg">
        🎮 Rumah PlayStation
      </h1>

      <p class="text-gray-300 mb-8">
        Temukan dan Booking Console PS Pilihanmu Sekarang Juga
      </p>

      <!-- BUTTON -->
      <div class="flex flex-col md:flex-row gap-4 justify-center">

        <a href="dashboard.php"
          class="glow-btn bg-blue-600 px-8 py-3 rounded-xl text-lg font-bold">
          Masuk ke Dashboard
        </a>

      </div>
    </div>
  </div>

  <!-- SCRIPT BACKGROUND -->
  <script>
    const backgrounds = [
      { type: 'image', src: 'assest/media/slide1.jpg' },
      { type: 'video', src: 'assest/media/gameplay1.mp4' },
      { type: 'image', src: 'assest/media/slide2.jpg' },
      { type: 'video', src: 'assest/media/gameplay2.mp4' },
      { type: 'image', src: 'assest/media/slide3.jpg' },
      { type: 'video', src: 'assest/media/gameplay3.mp4' }
    ];

    let index = 0;
    const bgContainer = document.getElementById('background');

    function showBackground(item) {
      bgContainer.innerHTML = '';

      const el = document.createElement(item.type === 'image' ? 'img' : 'video');
      el.className = 'fade';
      el.src = item.src;

      if (item.type === 'video') {
        el.autoplay = true;
        el.loop = false;
        el.muted = true;
        el.playsInline = true;
        el.onended = () => nextBackground();
      } else {
        setTimeout(() => nextBackground(), 4000);
      }

      bgContainer.appendChild(el);
      setTimeout(() => {
        el.classList.add('show');
      }, 50);
    }

    function nextBackground() {
      index = (index + 1) % backgrounds.length;
      showBackground(backgrounds[index]);
    }

    showBackground(backgrounds[index]);
  </script>

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