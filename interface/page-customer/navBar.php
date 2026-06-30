<div class="nav-bar">
    <div class="nav-content">
        <div class="nav-logo">
                <a href="/CardHaven/home">
                <img src="/cardhaven/assets/image/logo.svg" style="object-fit: cover; width: 100%; height: 100%;">
            </a>
            </div>
        <div class="nav-menu">
            <div class="nav-search">
                <input type="text" style="height: 85%; width: 80%; border: 1px solid var(--primary-color); border-radius: 9999px;" placeholder="Type Product Name">
                    <div style="height: 85%; aspect-ratio: 1/1; background-color: var(--primary-color); border-radius: 9999px; display: flex; justify-content: center; align-items: center;">
                        <img src="/cardhaven/assets/image/search.svg" style="object-fit: cover; width: 60%; height: 60%;">
                    </div>
                </a>
            </div>
            <div class="nav-profile" style="position: relative;"> 
                <button onclick="window.location.replace('/CardHaven/register')" 
                class="sign-in-button coolveticaa" id="btn-sign" style="height: 70%; width: 30%; border-radius: 9999px; background: var(--bg-gradient); color: white; font-size: 1.25rem; display: flex; align-items: center; justify-content: center; font-size: 1.05rem;">
                    Sign In
                </button>
                <div style="height: 100%; display: flex; align-items: center; gap: 0.75rem;">
                    <h3 class="coolveticaa" id="namaUser" style="color: var(--primary-color); font-size: 1.25rem; margin-right: 0.75rem;"></h3>
                    
                    <div id="avatar-trigger" style="height: 100%; aspect-ratio: 1/1; background-color: blue; border-radius: 9999px; overflow: hidden; border: 1px solid var(--primary-color); cursor: pointer;">
                        <img src="https://i.pinimg.com/736x/5e/14/90/5e149094251c9316fc696e7aeba7b2b1.jpg" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    
                    <div style="height: 80%; aspect-ratio: 1/1; display: flex; align-items: center;">
                        <a href="/CardHaven/home/cart" style="height: 60%; aspect-ratio: 1/1; display: block;">
                            <img src="/cardhaven/assets/image/cart.svg" style="object-fit: cover; width: 100%; height: 100%; cursor: pointer;" title="Keranjang Belanja">
                        </a>
                    </div>
                </div>

                <div id="popup-profile" class="modal-popup coolveticaa">
                    <div class="popup-body">
                        <div class="profile-summary">
                            <div class="profile-avatar-large">
                                <img src="https://i.pinimg.com/736x/5e/14/90/5e149094251c9316fc696e7aeba7b2b1.jpg" alt="Avatar">
                            </div>
                            <div class="profile-info">
                                <h4 id="popNamaUser">Username</h4>
                                <p>TCG Player</p>
                            </div>
                        </div>
                        
                        <hr class="popup-divider">
                        
                        <ul class="popup-menu-list">
                            <li>
                                <a href="/CardHaven/profilepage"> <img src="/cardhaven/assets/image/user.svg" class="menu-icon" alt="icon">
                                    <span>My Profile</span>
                                </a>
                            </li>
                            <li>
                                <button id="btn-trigger-mailbox" class="menu-item-btn">
                                    <img src="/cardhaven/assets/image/inbox.svg" class="menu-icon" alt="icon">
                                    <span>Mailbox</span>
                                    <span class="badge-notif">2</span> </button>
                            </li>
                        </ul>

                        <hr class="popup-divider">

                        <button id="btn-logout" class="btn-logout-popup">
                            <img src="/cardhaven/assets/image/logout.svg" class="menu-icon" alt="icon">
                            Logout
                        </button>
                    </div>
                </div>

                <div id="popup-mailbox" class="modal-popup coolveticaa">
                    <div class="popup-header">
                        <button id="btn-back-to-profile" class="btn-back">
                            <img src="/cardhaven/assets/image/arrow-back.svg" alt="back">
                        </button>
                        <h4>Mailbox</h4>
                        <div style="width: 24px;"></div> </div>
                    <div class="popup-body mailbox-content">
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
    /* CSS Tambahan Pop Up */
    .modal-popup {
        display: none; /* Tersembunyi default */
        position: absolute;
        top: 110%; /* Muncul tepat di bawah navbar profile */
        right: 0;
        width: 280px;
        background-color: #ffffff;
        border: 2px solid var(--primary-color, #333);
        border-radius: 16px;
        box-shadow: 0px 8px 24px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        overflow: hidden;
        animation: popupFadeIn 0.2s ease-out;
    }

    @keyframes popupFadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .popup-body {
        padding: 1rem;
    }

    /* Profile Summary */
    .profile-summary {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 0.5rem;
    }

    .profile-avatar-large {
        width: 50px;
        height: 50px;
        border-radius: 9999px;
        overflow: hidden;
        border: 1px solid var(--primary-color, #333);
    }

    .profile-avatar-large img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-info h4 {
        margin: 0;
        font-size: 1.1rem;
        color: var(--primary-color, #333);
    }

    .profile-info p {
        margin: 0;
        font-size: 0.85rem;
        color: #777;
    }

    .popup-divider {
        border: 0;
        border-top: 1px solid #eef0f2;
        margin: 0.75rem 0;
    }

    /* Menu List */
    .popup-menu-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .popup-menu-list li {
        margin-bottom: 0.25rem;
    }

    .popup-menu-list a, .menu-item-btn {
        display: flex;
        align-items: center;
        width: 100%;
        padding: 0.6rem 0.75rem;
        color: #333;
        text-decoration: none;
        border-radius: 8px;
        transition: background 0.2s;
        border: none;
        background: transparent;
        text-align: left;
        font-size: 1rem;
        cursor: pointer;
        font-family: inherit;
    }

    .popup-menu-list a:hover, .menu-item-btn:hover {
        background-color: #f5f5f5;
        color: var(--primary-color, #000);
    }

    .menu-icon {
        width: 20px;
        height: 20px;
        margin-right: 0.75rem;
        object-fit: contain;
    }

    .badge-notif {
        margin-left: auto;
        background-color: red;
        color: white;
        font-size: 0.75rem;
        padding: 2px 6px;
        border-radius: 9999px;
    }

    /* Logout Button */
    .btn-logout-popup {
        display: flex;
        align-items: center;
        width: 100%;
        padding: 0.6rem 0.75rem;
        color: #ff3b30;
        background: transparent;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1rem;
        font-family: inherit;
        transition: background 0.2s;
    }

    .btn-logout-popup:hover {
        background-color: #ffebe6;
    }

    /* Mailbox Header */
    .popup-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #eef0f2;
        background-color: #fafafa;
    }

    .popup-header h4 {
        margin: 0;
        font-size: 1.1rem;
        color: var(--primary-color, #333);
    }

    .btn-back {
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-back img {
        width: 20px;
        height: 20px;
    }

    /* Mailbox Items */
    .mailbox-content {
        max-height: 250px;
        overflow-y: auto;
        padding: 0.5rem 0;
    }

    .mail-item {
        display: flex;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f9f9f9;
        transition: background 0.2s;
        cursor: pointer;
    }

    .mail-item:hover {
        background-color: #f9f9f9;
    }

    .mail-item.unread {
        background-color: #f0f7ff;
    }

    .mail-icon {
        width: 24px;
        height: 24px;
        object-fit: contain;
        margin-top: 2px;
    }

    .mail-details h5 {
        margin: 0 0 2px 0;
        font-size: 0.9rem;
        color: #111;
    }

    .mail-details p {
        margin: 0 0 4px 0;
        font-size: 0.8rem;
        color: #666;
        line-height: 1.2;
    }

    .mail-time {
        font-size: 0.7rem;
        color: #999;
    }

    .sign-in-button:hover{
        cursor: pointer;
    }
</style>

<script>
    const isUser = localStorage.getItem('username') || sessionStorage.getItem('username');
    const signBtn = document.getElementById('btn-sign');
    const namaUser = document.getElementById('namaUser');
    const popNamaUser = document.getElementById('popNamaUser');

    // Element Pop up & Trigger
    const avatarTrigger = document.getElementById('avatar-trigger');
    const popupProfile = document.getElementById('popup-profile');
    const popupMailbox = document.getElementById('popup-mailbox');
    
    const btnTriggerMailbox = document.getElementById('btn-trigger-mailbox');
    const btnBackToProfile = document.getElementById('btn-back-to-profile');
    const btnLogout = document.getElementById('btn-logout');

    // Cek Session Login
    if(isUser){
        signBtn.style.display = 'none'; // Sembunyikan tombol Sign In jika sudah login
        namaUser.textContent = isUser;  // Tampilkan nama di navbar
        if(popNamaUser) popNamaUser.textContent = isUser; // Tampilkan nama di dalam pop-up
    } else {
        // JIKA BELUM LOGIN:
        namaUser.textContent = "Guest"; // Set nama default jadi Guest
        if(popNamaUser) popNamaUser.textContent = "Guest";
        // Tombol Sign In tetap muncul, foto profil tetap muncul dan bisa diklik
    }

    if(!isUser) avatarTrigger.style.cursor = 'default';

    avatarTrigger.addEventListener('click', function(e) {
        if(!isUser) return;
        e.stopPropagation();
        if (popupProfile.style.display === 'block' || popupMailbox.style.display === 'block') {
            popupProfile.style.display = 'none';
            popupMailbox.style.display = 'none';
        } else {
            popupProfile.style.display = 'block';
        }
    });

    async function fetchUserData() {
        const idPengguna = localStorage.getItem('id_pengguna') || sessionStorage.getItem('id_pengguna');
        if (!idPengguna) return;

        try {
            const response = await fetch(`/cardhaven/interface/page-customer/controller.php?id_pengguna=${idPengguna}`);
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

            const result = await response.json();

            if (result.status === 'success' && result.data) {
                // ✅ Pakai variabel yang benar (result.data, bukan user)
                const avatarSrc = result.data.foto_profil
                    ? `/cardhaven/${result.data.foto_profil}`
                    : '/cardhaven/assets/image/user.svg';

                // ✅ Set ke semua elemen avatar di navbar
                const avatarImgs = document.querySelectorAll('#avatar-trigger img, .profile-avatar-large img');
                avatarImgs.forEach(img => img.src = avatarSrc);
            }
        } catch (error) {
            console.error('Error fetching user data:', error);
        }
    }

    // ✅ Panggil saat login
    if (isUser) {
        fetchUserData();
    }

    btnTriggerMailbox.addEventListener('click', function(e) {
        e.stopPropagation();
        popupProfile.style.display = 'none';
        popupMailbox.style.display = 'block';
    });

    btnBackToProfile.addEventListener('click', function(e) {
        e.stopPropagation();
        popupMailbox.style.display = 'none';
        popupProfile.style.display = 'block';
    });

    document.addEventListener('click', function(e) {
        if (!popupProfile.contains(e.target) && !popupMailbox.contains(e.target) && e.target !== avatarTrigger) {
            popupProfile.style.display = 'none';
            popupMailbox.style.display = 'none';
        }
    });

    btnLogout.addEventListener('click', function() {
            
        cardhavenConfirm(
            "Confirm Logout", 
            "Are you sure you want to logout from your account?", 
            "Logout", 
            () => {
                localStorage.clear();
                sessionStorage.clear();
                window.location.href = "/CardHaven/login";
            }
        );
    });
</script>
<script src="/cardhaven/interface/global_alert.js"></script>
