<div style="width: 100%; height: 100%; display: flex; align-items: center; flex-direction: column;">
    <div class="logo-wrap">
        <img src="/cardhaven/assets/image/logo.svg">
    </div>

    <div class="profile-employee">
        <div class="photo-Profile">
            <img id="profileImage"
                src="/cardhaven/assets/image/image-profile/default.jpg"
                style="object-fit: cover; width: 100%; height: 100%;"
                onerror="handleSidebarImageError(this)">
        </div>
        <div class="userTag">
            <h2 class="coolveticaa" style="color: white; font-size: .65rem;" id="admin-role"></h2>
        </div>
        <div style="margin-top: 1rem;">
            <h2 id="userName" class="coolveticaa" style="font-size: 1rem; color: var(--primary-color);"></h2>
            <h3 id="userEmail" style="font-size: 0.75rem; opacity: 55%; margin: 0.25rem 0 0 0;"></h3>
        </div>
        <div style="width: 100%; margin-top: 0.5rem; display: flex; justify-content: center; gap: .75rem;">
            <a href="javascript:void(0)" onclick="openAdminMailbox()" style="position: relative;" title="Notifications">
                <img src="/cardhaven/assets/image/inbox.svg" style="object-fit:fill; width: 1.35rem; height: 1.35rem;">
                <span id="adminMailBadge" style="display:none; position:absolute; top:-7px; right:-9px; background:#e53935; color:#fff; font-size:0.6rem; font-weight:700; min-width:16px; height:16px; border-radius:9999px; align-items:center; justify-content:center; padding:0 3px; line-height:1;">0</span>
            </a>
            <a href="javascript:void(0)" id="btnLogout">
                <img src="/cardhaven/assets/image/logout.svg" style="object-fit:fill; width: 1.35rem; height: 1.35rem;">
            </a>
        </div>
    </div>

    <div class="navMenu">
        <h2 class="coolveticaa" style="font-size: 1rem; color: var(--primary-color); margin-bottom: 0.5rem;">Menu</h2>

        <div class="menuOption unselected" id="nav-dashboard">
            <a href="activity" style="display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit;">
                <img src="/cardhaven/assets/image/analytics.svg">
                <h2 class="coolveticaa" style="color: var(--highlight)">Activity</h2>
            </a>
        </div>
        <div class="menuOption unselected" id="nav-sales">
            <a href="sales" style="display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit;">
                <img src="/cardhaven/assets/image/sales-report.svg">
                <h2 class="coolveticaa" style="color: var(--highlight)">Report</h2>
            </a>
        </div>
        <div class="menuOption unselected" id="nav-purchase">
            <a href="purchase" style="display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit;">
                <img src="/cardhaven/assets/image/purchase.svg">
                <h2 class="coolveticaa" style="color: var(--highlight)">Purchase</h2>
            </a>
        </div>
        <div class="menuOption unselected" id="nav-transaction">
            <a href="transaction" style="display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit;">
                <img src="/cardhaven/assets/image/transaction.svg">
                <h2 class="coolveticaa" style="color: var(--highlight)">Transaction</h2>
            </a>
        </div>
        <div class="menuOption unselected" id="nav-product">
            <a href="product" style="display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit;">
                <img src="/cardhaven/assets/image/product.svg">
                <h2 class="coolveticaa" style="color: var(--highlight)">Product</h2>
            </a>
        </div>
        <div class="menuOption unselected" id="nav-event">
            <a href="event" style="display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit;">
                <img src="/cardhaven/assets/image/event.svg">
                <h2 class="coolveticaa" style="color: var(--highlight)">Event</h2>
            </a>
        </div>
        <div class="menuOption unselected" id="nav-user">
            <a href="user" style="display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit;">
                <img src="/cardhaven/assets/image/user.svg">
                <h2 class="coolveticaa" style="color: var(--highlight)">User</h2>
            </a>
        </div>
        <div class="menuOption unselected" id="nav-setting">
            <a href="settingaccount" style="display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit;">
                <img src="/cardhaven/assets/image/setting.svg">
                <h2 class="coolveticaa" style="color: var(--highlight)">Account Setting</h2>
            </a>
        </div>

        <?php include 'logout.php' ?>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const navDashboard   = document.getElementById('nav-dashboard');
    const navTransaction = document.getElementById('nav-transaction');
    const navPurchase    = document.getElementById('nav-purchase');
    const navProduct     = document.getElementById('nav-product');
    const navEvent       = document.getElementById('nav-event');
    const navSales       = document.getElementById('nav-sales');
    const navUser        = document.getElementById('nav-user');
    const navSetting     = document.getElementById('nav-setting');
    const adminRole      = document.getElementById('admin-role');
    const profileImageElement = document.getElementById('profileImage');

    // Ambil Data User dari PHP session (window.CH_AUTH), bukan dari storage browser.
    const userId = CardHavenAuth.id();
    document.getElementById("userName").textContent  = CardHavenAuth.username();
    document.getElementById("userEmail").textContent = CardHavenAuth.email();

    if (userId) {
        // Tanpa parameter id — server memakai id dari session.
        fetch(`/CardHaven/interface/page-admin/controller.php?action=getProfileImage`)
            .then(response => response.text())
            .then(textData => {
                try {
                    const data = JSON.parse(textData);
                    if (data.status === 'success' && data.image) {
                        // DB menyimpan nama file saja. Ambil nama file (buang path lama jika ada)
                        // lalu tambahkan folder assets/image/image-profile.
                        const fileName = data.image.split('/').pop();
                        profileImageElement.src = `/CardHaven/assets/image/image-profile/${fileName}`;
                    }
                    // [FIX] Kalau gagal atau user tidak punya foto, biarkan default.jpg tetap tampil
                } catch (e) {
                    // [FIX] Bukan JSON = PHP error / file tidak ketemu, biarkan default foto saja
                    console.error("The response is not JSON:", textData);
                }
            })
            .catch(error => {
                console.error("Fetch/Network error:", error);
            });
    }

    // Role dari PHP session. Menyembunyikan menu di sini hanya untuk kerapian UI —
    // pembatasan sesungguhnya tetap dicek server di tiap halaman & controller.
    const role = CardHavenAuth.role();

    if (role === 2 || role === 1) {
        navUser.style.display  = 'none';
        navSales.style.display = 'none';
    }
    if (role === 1) {
        navEvent.style.display    = 'none';
        navPurchase.style.display = 'none';
    }

    adminRole.textContent = CardHavenAuth.roleLabel();

    const request  = window.location.pathname;
    const url      = request.replace('/CardHaven', '');
    const segments = url.replace(/^\/|\/$/g, '').split('/');
    const page     = segments[1]?.toString();

    switch (page) {
        case "activity":      navDashboard.classList.add('selectedOption');  break;
        case "transaction":   navTransaction.classList.add('selectedOption'); break;
        case "product":       navProduct.classList.add('selectedOption');     break;
        case "purchase":      navPurchase.classList.add('selectedOption');    break;
        case "event":         navEvent.classList.add('selectedOption');       break;
        case "sales":         navSales.classList.add('selectedOption');       break;
        case "user":          navUser.classList.add('selectedOption');        break;
        case "settingaccount":navSetting.classList.add('selectedOption');     break;
        default: break;
    }
});
</script>

<!-- Admin Mailbox Modal -->
<div id="adminMailOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:3000; justify-content:center; align-items:flex-start; padding:4.5rem 1rem;" onclick="if(event.target===this) closeAdminMailbox()">
    <div style="background:#fff; width:min(440px,95vw); max-height:75vh; border-radius:14px; box-shadow:0 12px 40px rgba(0,0,0,.3); display:flex; flex-direction:column; overflow:hidden;">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-bottom:1px solid #eef0f2;">
            <h3 style="margin:0; color:var(--primary-color, #0D47A1); font-size:1.15rem;">Mailbox</h3>
            <div style="display:flex; gap:0.9rem; align-items:center;">
                <button onclick="adminMarkAllRead()" style="background:none; border:none; color:var(--primary-color, #0D47A1); font-size:0.78rem; cursor:pointer; font-weight:600;">Mark all as read</button>
                <button onclick="closeAdminMailbox()" style="background:none; border:none; font-size:22px; cursor:pointer; opacity:.5; line-height:1;">&times;</button>
            </div>
        </div>
        <div id="adminMailList" style="overflow-y:auto; flex:1;">
            <p style="text-align:center; color:#888; padding:1.5rem;">Loading...</p>
        </div>
    </div>
</div>
<script>
// Fungsi Handler jika gambar gagal dimuat
function handleSidebarImageError(img) {
    const defaultImg = '/cardhaven/assets/image/user.svg'; // Gambar fallback terakhir
    const currentSrc = img.src;

    // Foto profil hanya tersimpan di assets/image/image-profile/. Kalau gagal dimuat,
    // langsung pakai gambar default.
    if (!currentSrc.endsWith(defaultImg)) {
        img.src = defaultImg;
    }
}

document.addEventListener("DOMContentLoaded", () => {
    // ... selector nav lainnya (navDashboard, navTransaction, dll tetap sama) ...
    const profileImageElement = document.getElementById('profileImage');

    // Ambil Data User dari PHP session (window.CH_AUTH), bukan dari storage browser.
    const userId = CardHavenAuth.id();
    document.getElementById("userName").textContent  = CardHavenAuth.username();
    document.getElementById("userEmail").textContent = CardHavenAuth.email();

    if (userId) {
        // Tanpa parameter id — server memakai id dari session.
        fetch(`/CardHaven/interface/page-admin/controller.php?action=getProfileImage`)
            .then(response => response.json()) // Langsung parse ke JSON
            .then(data => {
                if (data.status === 'success' && data.image) {
                    // Bersihkan nama file jika data.image mengandung path lama
                    const fileName = data.image.split('/').pop();

                    // Coba load dari folder assets/image/image-profile sebagai prioritas utama
                    profileImageElement.src = `/CardHaven/assets/image/image-profile/${fileName}`;
                }
            })
            .catch(error => {
                console.error("Fetch profile image error:", error);
            });
    }

    // ... sisa kode logic role dan navigasi segmen URL tetap sama ...
    const role = CardHavenAuth.role();
    // ... dst ...
});
</script>
<script>
(function(){
    const ADMIN_MAIL_API = '/cardhaven/interface/page-profile/controller/MailController.php';
    function adminMailId(){ return CardHavenAuth.id() || null; }

    // Notif tersimpan dalam Bahasa Indonesia; terjemahkan frasa template yang dikenal ke Inggris.
    const ADM_NOTIF_TR = [
        ['PEMBAYARAN BERHASIL','Payment Received'],
        ['Pembayaran untuk pesanan','Payment for order'],
        ['telah kami terima','has been received'],
        ['Pesananmu sedang diproses oleh penjual','Your order is now being processed by the seller'],
        ['Pesanan kamu sedang diproses','Your order is being processed'],
        ['Pesananmu sedang diproses','Your order is being processed'],
        ['Pesanan kamu telah dikirim','Your order has been shipped'],
        ['Pesananmu telah dikirim','Your order has been shipped'],
        ['Pesanan kamu telah sampai','Your order has been delivered'],
        ['Pesananmu telah sampai','Your order has been delivered'],
        ['Pesanan kamu telah dibatalkan','Your order has been cancelled'],
        ['Pesananmu telah dibatalkan','Your order has been cancelled'],
        ['Menunggu pembayaran','Awaiting payment'],
        ['Nomor resi','Tracking number'],
        ['Terima kasih','Thank you'],
        ['sebesar','amounting to'],
        ['Halo','Hello'],
        ['pesanan','order'],
    ];
    function admTranslate(t){ if(!t) return ''; let o=String(t); ADM_NOTIF_TR.forEach(([id,en])=>{ o=o.split(id).join(en); }); return o; }

    let adminMailData = [];

    window.adminLoadMails = function(){
        const uid = adminMailId(); if(!uid) return;
        fetch(`${ADMIN_MAIL_API}?action=getMails&id_pengguna=${uid}`)
            .then(r=>r.json())
            .then(res=>{
                if(res.status !== 'success') return;
                adminMailData = res.data || [];
                const badge = document.getElementById('adminMailBadge');
                if(badge){
                    if((res.unread||0) > 0){ badge.textContent = res.unread; badge.style.display = 'inline-flex'; }
                    else badge.style.display = 'none';
                }
                adminRenderMails();
            })
            .catch(()=>{});
    };

    function adminRenderMails(){
        const box = document.getElementById('adminMailList'); if(!box) return;
        if(!adminMailData.length){
            box.innerHTML = '<p style="text-align:center;color:#888;padding:1.5rem;">No notifications.</p>';
            return;
        }
        box.innerHTML = adminMailData.map(m=>{
            const unread = m.status_notifikasi == 0;
            return `<div onclick="adminReadMail(${m.id_notifikasi})" style="padding:.75rem 1.25rem; border-bottom:1px solid #f4f4f4; cursor:pointer; ${unread?'background:#f0f7ff;':''}">
                <div style="font-weight:600; font-size:.88rem; color:#111; display:flex; align-items:center; gap:6px;">${admTranslate(m.judul)} ${unread?'<span style=\"width:7px;height:7px;border-radius:50%;background:#0088ff;flex-shrink:0;\"></span>':''}</div>
                <div style="font-size:.8rem; color:#666; margin:3px 0;">${admTranslate(m.isi)}</div>
                <div style="font-size:.7rem; color:#999;">${m.tanggal_notifikasi || ''}</div>
            </div>`;
        }).join('');
    }

    window.openAdminMailbox  = function(){ const o=document.getElementById('adminMailOverlay'); if(o) o.style.display='flex'; };
    window.closeAdminMailbox = function(){ const o=document.getElementById('adminMailOverlay'); if(o) o.style.display='none'; };

    window.adminReadMail = function(id){
        const m = adminMailData.find(x=>x.id_notifikasi == id);
        if(!m || m.status_notifikasi != 0) return;
        const fd = new FormData(); fd.append('id_notifikasi', id);
        fetch(`${ADMIN_MAIL_API}?action=markRead`, { method:'POST', body: fd })
            .then(r=>r.json()).then(res=>{ if(res.status==='success') window.adminLoadMails(); });
    };

    window.adminMarkAllRead = function(){
        const uid = adminMailId(); if(!uid) return;
        const fd = new FormData(); fd.append('id_pengguna', uid);
        fetch(`${ADMIN_MAIL_API}?action=markAllRead`, { method:'POST', body: fd })
            .then(r=>r.json()).then(res=>{ if(res.status==='success') window.adminLoadMails(); });
    };

    document.addEventListener('DOMContentLoaded', function(){ if(adminMailId()) window.adminLoadMails(); });
})();
</script>

<!-- Hamburger + overlay untuk drawer sidebar di HP (hanya tampil <=768px via CSS) -->
<script>
(function(){
    function initSidebarDrawer(){
        if (document.getElementById('adminHamburger')) return;

        var btn = document.createElement('button');
        btn.id = 'adminHamburger';
        btn.className = 'admin-hamburger';
        btn.setAttribute('aria-label', 'Toggle menu');
        btn.innerHTML = '<span></span><span></span><span></span>';

        var overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.id = 'adminSidebarOverlay';

        document.body.appendChild(btn);
        document.body.appendChild(overlay);

        function close(){ document.body.classList.remove('sidebar-open'); }
        btn.addEventListener('click', function(e){ e.stopPropagation(); document.body.classList.toggle('sidebar-open'); });
        overlay.addEventListener('click', close);

        // Tutup drawer setelah memilih menu.
        document.querySelectorAll('.sideBar .menuOption a').forEach(function(a){
            a.addEventListener('click', close);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebarDrawer);
    } else {
        initSidebarDrawer();
    }
})();
</script>