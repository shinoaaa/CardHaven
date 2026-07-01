let mailData = [];
let currentPage = 1;
const limitPerPage = 5;

// Notifikasi lama tersimpan dalam Bahasa Indonesia di DB. Terjemahkan
// frasa-frasa template yang dikenal ke Bahasa Inggris saat ditampilkan,
// agar inbox konsisten berbahasa Inggris. Bagian dinamis (#ID, Rp, angka)
// tetap dipertahankan. Urutan penting: frasa panjang lebih dulu.
const NOTIF_TRANSLATIONS = [
    ['PEMBAYARAN BERHASIL', 'Payment Received'],
    ['Pembayaran untuk pesanan', 'Payment for order'],
    ['telah kami terima', 'has been received'],
    ['Pesananmu sedang diproses oleh penjual', 'Your order is now being processed by the seller'],
    ['Pesanan kamu sedang diproses', 'Your order is being processed'],
    ['Pesananmu sedang diproses', 'Your order is being processed'],
    ['Pesanan kamu telah dikirim', 'Your order has been shipped'],
    ['Pesananmu telah dikirim', 'Your order has been shipped'],
    ['Pesanan kamu telah sampai', 'Your order has been delivered'],
    ['Pesananmu telah sampai', 'Your order has been delivered'],
    ['Pesanan kamu telah dibatalkan', 'Your order has been cancelled'],
    ['Pesananmu telah dibatalkan', 'Your order has been cancelled'],
    ['Menunggu pembayaran', 'Awaiting payment'],
    ['Nomor resi', 'Tracking number'],
    ['Terima kasih', 'Thank you'],
    ['sebesar', 'amounting to'],
    ['Halo', 'Hello'],
    ['pesanan', 'order'],
];

function translateNotif(text) {
    if (!text) return text;
    let out = String(text);
    NOTIF_TRANSLATIONS.forEach(([id, en]) => {
        out = out.split(id).join(en);
    });
    return out;
}

document.addEventListener('DOMContentLoaded', () => {
    if(userId) fetchMails();
});

function fetchMails() {
    fetch(`/cardhaven/interface/page-profile/controller/MailController.php?action=getMails&id_pengguna=${userId}`)
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                mailData = data.data;
                const badge = document.getElementById('unreadBadge');
                if(data.unread > 0) badge.style.display = 'block';
                else badge.style.display = 'none';
                renderMailList();
            }
        });
}

function openMailbox() {
    currentPage = 1; 
    renderMailList();
    document.getElementById('modalOverlay').style.display = 'block';
    document.getElementById('modalMailboxList').style.display = 'block';
}

function closeMailboxList() {
    document.getElementById('modalOverlay').style.display = 'none';
    document.getElementById('modalMailboxList').style.display = 'none';
}

function renderMailList() {
    const container = document.getElementById('mailListContainer');
    container.innerHTML = '';
    if(mailData.length === 0) {
        container.innerHTML = '<p style="text-align:center; color:#888;">Mailbox is empty.</p>';
        document.getElementById('mailPagination').innerHTML = '';
        return;
    }

    const startIndex = (currentPage - 1) * limitPerPage;
    const paginatedItems = mailData.slice(startIndex, startIndex + limitPerPage);

    paginatedItems.forEach(mail => {
        const isUnread = mail.status_notifikasi == 0 ? 'unread' : '';
        const item = document.createElement('div');
        item.className = `mail-item ${isUnread}`;
        item.innerHTML = `
            <h4 class="mail-item-title">${translateNotif(mail.judul)}</h4>
            <p class="mail-item-date">${mail.tanggal_notifikasi}</p>
        `;
        // Handle overlap interaction here
        item.onclick = () => openMailContent(mail);
        container.appendChild(item);
    });

    // Handle pagination DOM create
    const totalPages = Math.ceil(mailData.length / limitPerPage);
    const pagination = document.getElementById('mailPagination');
    pagination.innerHTML = '';
    for(let i=1; i<=totalPages; i++) {
        const btn = document.createElement('button');
        btn.innerText = i;
        btn.className = `page-btn ${i === currentPage ? 'active' : ''}`;
        btn.onclick = () => { currentPage = i; renderMailList(); };
        pagination.appendChild(btn);
    }
}

function openMailContent(mail) {
    document.getElementById('modalMailboxList').style.display = 'none';
    document.getElementById('modalMailContent').style.display = 'block';
    
    document.getElementById('mailTitleDetail').innerText = translateNotif(mail.judul);
    document.getElementById('mailDateDetail').innerText = mail.tanggal_notifikasi;
    document.getElementById('mailBodyDetail').innerHTML = translateNotif(mail.isi);

    const btnRead = document.getElementById('btnMarkRead');
    btnRead.disabled = false;
    btnRead.innerText = 'Mark as Read';
    if(mail.status_notifikasi == 0) {
        btnRead.style.display = 'inline-block';
        btnRead.onclick = () => markAsRead(mail.id_notifikasi);
    } else {
        btnRead.style.display = 'none';
    }
}

function closeMailContent() {
    document.getElementById('modalMailContent').style.display = 'none';
    document.getElementById('modalMailboxList').style.display = 'block';
}

function markAsRead(id_notifikasi) {
    const btn = document.getElementById('btnMarkRead');
    if (btn) { btn.disabled = true; btn.innerText = 'Marking...'; }

    const formData = new FormData();
    formData.append('id_notifikasi', id_notifikasi);
    fetch('/cardhaven/interface/page-profile/controller/MailController.php?action=markRead', {
        method: 'POST', body: formData
    }).then(res => res.json()).then(res => {
        if(res.status === 'success') {
            // Update state lokal (pakai == agar aman terhadap tipe number/string)
            const idx = mailData.findIndex(m => m.id_notifikasi == id_notifikasi);
            if(idx > -1) mailData[idx].status_notifikasi = 1;
            // Kembali ke daftar inbox supaya perubahan (tidak lagi unread) terlihat jelas
            closeMailContent();
            fetchMails();
        } else {
            if (btn) { btn.disabled = false; btn.innerText = 'Mark as Read'; }
            if (typeof cardhavenAlert === 'function') {
                cardhavenAlert('error', 'Failed', 'Could not mark this message as read.');
            }
        }
    }).catch(() => {
        if (btn) { btn.disabled = false; btn.innerText = 'Mark as Read'; }
    });
}