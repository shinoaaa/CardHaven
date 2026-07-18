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
    const dropdown = document.getElementById('profile-dropdown');
    if (dropdown) dropdown.style.display = 'none';

    // Session dihapus di server; browser tidak menyimpan identitas apa pun.
    CardHavenAuth.logout();
}

/* ─────────────────────────────────────────────
   INIT — Menentukan apakah user sedang login atau tidak
───────────────────────────────────────────── */
document.addEventListener("DOMContentLoaded", function() {
    // Status login diambil dari PHP session (window.CH_AUTH), bukan storage browser.
    const isUser = CardHavenAuth.isLoggedIn() ? CardHavenAuth.username() : '';

    const signBtn = document.getElementById('btn-sign');
    const loggedInSection = document.getElementById('user-logged-in-section');
    const namaUser = document.getElementById('namaUser');

    if (isUser) {
        // Jika sudah login
        if (signBtn) signBtn.style.display = 'none';
        if (loggedInSection) loggedInSection.style.display = 'flex';
        if (namaUser) namaUser.textContent = isUser;
    } else {
        // Jika belum login
        if (signBtn) signBtn.style.display = 'flex';
        if (loggedInSection) loggedInSection.style.display = 'none';
    }
});