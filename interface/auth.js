/**
 * interface/auth.js
 * ---------------------------------------------------------------------------
 * Satu-satunya cara JavaScript mengambil id_pengguna & role.
 *
 * Datanya berasal dari window.CH_AUTH, yang dicetak server dari PHP session
 * (lihat auth/session.php -> auth_emit_js()). JADI: JANGAN lagi menyimpan atau
 * membaca id_pengguna / role dari localStorage / sessionStorage.
 *
 * Nilai di sini HANYA untuk tampilan (menyembunyikan menu, menampilkan nama).
 * Otorisasi yang sesungguhnya tetap dilakukan server lewat auth_require_role()
 * / auth_api_require_role(). Mengubah nilai ini di browser tidak memberi
 * hak akses apa pun.
 *
 * Contoh pakai:
 *      const id   = CardHavenAuth.id();      // 0 kalau belum login
 *      const role = CardHavenAuth.role();    // 0=customer 1=employee 2=manager 3=owner
 *      if (CardHavenAuth.isStaff()) { ... }
 */
(function (global) {
    'use strict';

    var ROLES = { CUSTOMER: 0, EMPLOYEE: 1, MANAGER: 2, OWNER: 3 };

    function data() {
        return global.CH_AUTH || {};
    }

    var CardHavenAuth = {
        ROLES: ROLES,

        /** id_pengguna dari session PHP (0 kalau belum login). */
        id: function () {
            return parseInt(data().id, 10) || 0;
        },

        /** role dari session PHP (0/1/2/3). */
        role: function () {
            return parseInt(data().role, 10) || 0;
        },

        username: function () {
            return data().username || '';
        },

        email: function () {
            return data().email || '';
        },

        roleLabel: function () {
            return data().roleLabel || 'Customer';
        },

        /** true kalau ada session login yang sah di server. */
        isLoggedIn: function () {
            return data().loggedIn === true && CardHavenAuth.id() > 0;
        },

        /** true kalau role-nya employee/manager/owner. */
        isStaff: function () {
            return data().isStaff === true;
        },

        /** Cek role: CardHavenAuth.is(2, 3) -> true kalau manager atau owner. */
        is: function () {
            var wanted = Array.prototype.slice.call(arguments).map(Number);
            return CardHavenAuth.isLoggedIn() && wanted.indexOf(CardHavenAuth.role()) !== -1;
        },

        /** Logout: minta server menghapus session, lalu ke halaman login. */
        logout: function () {
            return fetch('/cardhaven/auth/logout.php', {
                method: 'POST',
                credentials: 'same-origin'
            })
                .catch(function () { /* tetap lanjut redirect walau request gagal */ })
                .then(function () {
                    sessionStorage.setItem('ch_toast_msg', 'Logged out successfully');
                    sessionStorage.setItem('ch_toast_icon', 'success');
                    window.location.href = '/CardHaven/login';
                });
        }
    };

    global.CardHavenAuth = CardHavenAuth;
})(window);
