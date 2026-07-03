/**
 * Smooth drag-to-scroll untuk carousel/tabel yang bisa digeser horizontal —
 * seperti tabel di dashboard admin: tahan lalu geser dengan mouse, tanpa perlu
 * klik tombol panah dulu. Sentuh/trackpad tetap jalan lewat overflow-x native.
 */
(function () {
    function enableDragScroll(el) {
        if (!el) return;

        let isDown = false;
        let startX = 0;
        let startScroll = 0;
        let moved = false;

        el.style.userSelect = 'none';

        el.addEventListener('pointerdown', (e) => {
            // Jangan bajak interaksi pada input/tekstarea di dalam container
            if (e.target.closest('input, textarea, select')) return;
            if (e.pointerType === 'mouse' && e.button !== 0) return;
            isDown = true;
            moved = false;
            startX = e.clientX;
            startScroll = el.scrollLeft;
            el.classList.add('dragging');
        });

        el.addEventListener('pointermove', (e) => {
            if (!isDown) return;
            const dx = e.clientX - startX;
            if (Math.abs(dx) > 4) {
                moved = true;
                if (el.setPointerCapture && e.pointerId != null) {
                    try { el.setPointerCapture(e.pointerId); } catch (_) {}
                }
            }
            el.scrollLeft = startScroll - dx;
        });

        const release = () => {
            if (!isDown) return;
            isDown = false;
            el.classList.remove('dragging');
        };
        el.addEventListener('pointerup', release);
        el.addEventListener('pointerleave', release);
        el.addEventListener('pointercancel', release);

        // Cegah klik "tembus" ke tombol kartu (Add To Cart, dll) sesudah menggeser
        el.addEventListener('click', (e) => {
            if (moved) {
                e.preventDefault();
                e.stopPropagation();
                moved = false;
            }
        }, true);

        // Konversi scroll roda vertikal → horizontal supaya mudah dijelajah
        el.addEventListener('wheel', (e) => {
            if (e.deltaY !== 0 && Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
                el.scrollLeft += e.deltaY;
                e.preventDefault();
            }
        }, { passive: false });
    }

    document.addEventListener('DOMContentLoaded', () => {
        enableDragScroll(document.querySelector('.product-list'));
        // Terapkan juga ke elemen lain yang ditandai perlu geser horizontal
        document.querySelectorAll('.drag-scroll').forEach(enableDragScroll);
    });

    // Ekspos supaya bisa dipakai halaman lain jika perlu
    window.enableDragScroll = enableDragScroll;
})();
