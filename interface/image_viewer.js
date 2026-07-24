/**
 * Viewer foto full-size global.
 * Dulu foto dibuka lewat tab baru (target="_blank" / window.open), sekarang tampil
 * sebagai overlay di halaman yang sama dengan tombol X untuk kembali.
 *
 * Pakai: chViewImage(src, caption)  — mis. onclick="chViewImage(this.src)"
 */
(function () {
    if (window.chViewImage) return; // jangan dobel kalau file ke-include dua kali

    var overlay, imgEl, capEl;

    function build() {
        var style = document.createElement('style');
        style.textContent = [
            '.ch-img-viewer{position:fixed;inset:0;background:rgba(0,0,0,.85);display:none;',
            'align-items:center;justify-content:center;z-index:100000;padding:40px 20px;}',
            '.ch-img-viewer.show{display:flex;}',
            '.ch-img-viewer img{max-width:100%;max-height:calc(100vh - 120px);object-fit:contain;',
            'border-radius:10px;box-shadow:0 10px 40px rgba(0,0,0,.5);background:#fff;}',
            '.ch-img-viewer-close{position:absolute;top:18px;right:22px;width:44px;height:44px;',
            'border:none;border-radius:50%;background:rgba(255,255,255,.15);color:#fff;font-size:1.6rem;',
            'line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;',
            'transition:.2s;}',
            '.ch-img-viewer-close:hover{background:#E74C3C;transform:scale(1.08);}',
            '.ch-img-viewer-cap{position:absolute;bottom:22px;left:0;right:0;text-align:center;',
            'color:#fff;font-size:.9rem;padding:0 20px;}',
            '@media (max-width:600px){.ch-img-viewer{padding:60px 12px;}',
            '.ch-img-viewer-close{top:12px;right:12px;width:38px;height:38px;font-size:1.3rem;}}'
        ].join('');
        document.head.appendChild(style);

        overlay = document.createElement('div');
        overlay.className = 'ch-img-viewer';
        overlay.innerHTML =
            '<button type="button" class="ch-img-viewer-close" title="Close (Esc)" aria-label="Close">&times;</button>' +
            '<img alt="Full size photo">' +
            '<div class="ch-img-viewer-cap"></div>';
        document.body.appendChild(overlay);

        imgEl = overlay.querySelector('img');
        capEl = overlay.querySelector('.ch-img-viewer-cap');

        overlay.querySelector('.ch-img-viewer-close').onclick = window.chCloseImage;
        // Klik area gelap = tutup, klik fotonya sendiri jangan menutup.
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) window.chCloseImage();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && overlay.classList.contains('show')) window.chCloseImage();
        });
    }

    window.chViewImage = function (src, caption) {
        if (!src) return;
        if (!overlay) build();
        imgEl.src = src;
        capEl.innerText = caption || '';
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    };

    window.chCloseImage = function () {
        if (!overlay) return;
        overlay.classList.remove('show');
        document.body.style.overflow = '';
        imgEl.src = '';
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', build);
    } else {
        build();
    }
})();
