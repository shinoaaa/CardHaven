/**
 * Swipe / drag navigation for the home carousels.
 * Carousel-carousel di homepage memakai paginasi server (klik panah untuk
 * ganti halaman). Modul ini menambah gestur geser (mouse drag / swipe sentuh)
 * pada area carousel: geser ke kiri = halaman berikutnya, ke kanan = sebelumnya.
 * Ini praktik UX yang umum & mempermudah, dan tetap memakai tombol panah yang ada.
 */
(function () {
    const THRESHOLD = 60; // jarak minimum (px) agar dianggap swipe

    function enableSwipeNav(container, prevSel, nextSel) {
        if (!container) return;

        let startX = 0, startY = 0, dragging = false, moved = false;

        container.style.touchAction = 'pan-y'; // biarkan scroll vertikal tetap jalan
        container.style.cursor = 'grab';

        container.addEventListener('pointerdown', (e) => {
            if (e.pointerType === 'mouse' && e.button !== 0) return;
            dragging = true;
            moved = false;
            startX = e.clientX;
            startY = e.clientY;
            container.style.cursor = 'grabbing';
        });

        window.addEventListener('pointermove', (e) => {
            if (!dragging) return;
            const dx = Math.abs(e.clientX - startX);
            const dy = Math.abs(e.clientY - startY);
            if (dx > 10 && dx > dy) moved = true;
        });

        window.addEventListener('pointerup', (e) => {
            if (!dragging) return;
            dragging = false;
            container.style.cursor = 'grab';

            const dx = e.clientX - startX;
            const dy = e.clientY - startY;

            if (Math.abs(dx) > THRESHOLD && Math.abs(dx) > Math.abs(dy)) {
                const btn = document.querySelector(dx < 0 ? nextSel : prevSel);
                if (btn) btn.click();

                // Cegah klik "tembus" ke tombol di dalam kartu (mis. Add To Cart)
                // setelah gestur geser.
                if (moved) {
                    const stop = (ev) => {
                        ev.stopPropagation();
                        ev.preventDefault();
                        window.removeEventListener('click', stop, true);
                    };
                    window.addEventListener('click', stop, true);
                    setTimeout(() => window.removeEventListener('click', stop, true), 350);
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        enableSwipeNav(document.querySelector('.product-list'),   '#btn-prev-product',   '#btn-next-product');
        enableSwipeNav(document.getElementById('ui-game-card-list'), '#btn-prev-game-card', '#btn-next-game-card');
        enableSwipeNav(document.querySelector('.promo-content'),  '#btn-prev-promo',     '#btn-next-promo');
    });
})();
