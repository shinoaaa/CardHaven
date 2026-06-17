document.addEventListener('DOMContentLoaded', () => {
    
    // 1. MANAJEMEN OTENTIKASI (LOGIN STATE)
    const idPengguna = localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');
    const signinBtn = document.getElementById('navSignInBtn');
    const profileAvatar = document.getElementById('navProfileAvatar');

    if (idPengguna) {
        // Jika sudah login, sembunyikan tombol Sign In dan munculkan Avatar
        signinBtn.style.display = 'none';
        profileAvatar.style.display = 'block';
        
        // (Opsional) Ambil gambar avatar jika tersimpan di localStorage, atau gunakan default
        const avatarUrl = localStorage.getItem('foto_profile') || '/CardHaven/assets/image/default-avatar.png';
        profileAvatar.style.backgroundImage = `url('${avatarUrl}')`;
    } else {
        // Jika belum login, munculkan tombol Sign In
        signinBtn.style.display = 'flex';
        profileAvatar.style.display = 'none';
    }

    // 2. LOGIKA DRAG TO SCROLL (PRODUK & GAME)
    const sliders = document.querySelectorAll('.draggable-slider');

    sliders.forEach(slider => {
        let isDown = false;
        let startX;
        let scrollLeft;

        slider.addEventListener('mousedown', (e) => {
            isDown = true;
            slider.style.cursor = 'grabbing';
            startX = e.pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        });

        slider.addEventListener('mouseleave', () => {
            isDown = false;
            slider.style.cursor = 'grab';
        });

        slider.addEventListener('mouseup', () => {
            isDown = false;
            slider.style.cursor = 'grab';
        });

        slider.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - slider.offsetLeft;
            const walk = (x - startX) * 2; // Pengali sensitivitas
            slider.scrollLeft = scrollLeft - walk;
        });
    });
});