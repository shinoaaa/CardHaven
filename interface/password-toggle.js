/**
 * interface/password-toggle.js
 * ---------------------------------------------------------------------------
 * Tombol lihat / sembunyikan password. Satu file untuk semua halaman.
 *
 * Cara pakai di HTML:
 *      <div class="password-wrap">
 *          <input type="password" id="passwordInput" ...>
 *          <button type="button" class="password-toggle" data-target="passwordInput"
 *                  aria-label="Show password">
 *              <img src="/cardhaven/assets/image/view.svg" alt="">
 *          </button>
 *      </div>
 *
 * Ikon:
 *   - password tersembunyi -> tampil view.svg      (klik = lihat password)
 *   - password terlihat    -> tampil view-off.svg  (klik = sembunyikan lagi)
 *
 * Pakai event delegation, jadi tetap jalan untuk field yang awalnya
 * disembunyikan (contoh: form Forgot Password).
 */
(function () {
    'use strict';

    var ICON_SHOW = '/cardhaven/assets/image/view.svg';     // klik untuk melihat
    var ICON_HIDE = '/cardhaven/assets/image/view-off.svg'; // klik untuk menyembunyikan

    function togglePassword(btn) {
        var input = document.getElementById(btn.getAttribute('data-target'));
        if (!input) return;

        var img     = btn.querySelector('img');
        var reveal  = input.type === 'password'; // true = sedang disembunyikan, mau ditampilkan
        var focused = document.activeElement === input;
        var start   = input.selectionStart;
        var end     = input.selectionEnd;

        input.type = reveal ? 'text' : 'password';

        if (img) img.src = reveal ? ICON_HIDE : ICON_SHOW;
        btn.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
        btn.setAttribute('aria-pressed', reveal ? 'true' : 'false');

        // Ganti type membuat kursor lompat ke akhir — kembalikan posisinya
        // supaya user bisa lanjut mengetik di tempat yang sama.
        if (focused) {
            input.focus();
            try { input.setSelectionRange(start, end); } catch (e) { /* diabaikan */ }
        }
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('.password-toggle') : null;
        if (!btn) return;
        e.preventDefault();
        togglePassword(btn);
    });
})();
