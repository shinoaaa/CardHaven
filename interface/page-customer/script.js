/**
 * profile-popup/script.js
 * Diperbaiki agar sesuai dengan struktur wadah (wrapper) HTML yang baru.
 */

/* ─────────────────────────────────────────────
   TOGGLE DROPDOWN
───────────────────────────────────────────── */
function toggleProfileDropdown() {
    const dropdown = document.getElementById('profile-dropdown');
    if (!dropdown) return;
    const isOpen = dropdown.style.display === 'block';
    dropdown.style.display = isOpen ? 'none' : 'block';
}

/* Close dropdown when clicking outside of it */
document.addEventListener('click', function (e) {
    const wrapper = document.getElementById('profile-dropdown-wrapper');
    const dropdown = document.getElementById('profile-dropdown');
    if (!wrapper || !dropdown) return;

    if (!wrapper.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});

/* ─────────────────────────────────────────────
   MAILBOX
───────────────────────────────────────────── */
function openMailboxFromProfile() {
    const dropdown = document.getElementById('profile-dropdown');
    const mailbox = document.getElementById('mailbox-popup');
    
    if (dropdown) dropdown.style.display = 'none';
    if (mailbox) mailbox.style.display = 'block';
}

function closeMailboxToProfile() {
    const mailbox = document.getElementById('mailbox-popup');
    const dropdown = document.getElementById('profile-dropdown');

    if (mailbox) mailbox.style.display = 'none';
    if (dropdown) dropdown.style.display = 'block';
}

/* ─────────────────────────────────────────────
   LOGOUT
───────────────────────────────────────────── */
function profileLogout() {
    const authKeys = ['id_pengguna', 'username', 'nama', 'role', 'token', 'foto'];

    authKeys.forEach(function (key) {
        localStorage.removeItem(key);
        sessionStorage.removeItem(key);
    });

    const dropdown = document.getElementById('profile-dropdown');
    if (dropdown) dropdown.style.display = 'none';

    // Redirect ke halaman home
    window.location.href = '/cardhaven/interface/home/index.php';
}

/* ─────────────────────────────────────────────
   INIT — Menentukan apakah user sedang login atau tidak
───────────────────────────────────────────── */
document.addEventListener("DOMContentLoaded", function() {
    const isUser = localStorage.getItem('username') || sessionStorage.getItem('username');
    const foto = localStorage.getItem('foto') || sessionStorage.getItem('foto');
    
    const signBtn = document.getElementById('btn-sign');
    const loggedInSection = document.getElementById('user-logged-in-section');
    const namaUser = document.getElementById('namaUser');
    const avatarImg = document.getElementById('profile-avatar-img');

    if (isUser) {
        // Jika sudah login
        if (signBtn) signBtn.style.display = 'none';
        if (loggedInSection) loggedInSection.style.display = 'flex';
        if (namaUser) namaUser.textContent = isUser;

        if (foto && avatarImg) {
            avatarImg.src = '/cardhaven/' + foto;
        }
    } else {
        // Jika belum login
        if (signBtn) signBtn.style.display = 'flex';
        if (loggedInSection) loggedInSection.style.display = 'none';
    }
});