<div class="profile-card">
    <div class="profile-left">
        <div class="profile-avatar-wrapper">
            <img src="/cardhaven/image-profile/profil_97_1782695090.jpg" alt="Prode" id="displayAvatar" class="profile-avatar">
        </div>
        <div class="profile-action-pill">
            <a href="/CardHaven/home" class="action-icon">
                <img src="/cardhaven/assets/image/home.svg" alt="Home">
            </a>
            <button onclick="openMailbox()" class="action-icon mail-btn">
                <img src="/cardhaven/assets/image/inbox.svg" style="filter: brightness(0) invert(1);">
                <span id="unreadBadge" class="mail-badge" style="display: none;"></span>
            </button>
            <button onclick="openEditProfile()" class="action-icon">
                <img src="/cardhaven/assets/image/edit.svg" alt="Edit">
            </button>
        </div>
    </div>
    
    <div class="profile-center">
        <h2 id="displayUsername" class="profile-name">Loading...</h2>
        <p id="displayEmail" class="profile-email">Loading...</p>
    </div>

    <div class="profile-right">
        <div class="info-grid">
            <div class="info-item">
                <img src="/cardhaven/assets/image/telephone.svg" alt="Phone" class="info-icon">
                <span id="displayPhone" class="info-text">-</span>
            </div>
            <div class="info-item">
                <img src="/cardhaven/assets/image/calendar.svg" alt="Date" class="info-icon">
                <span id="displayJoinDate" class="info-text">-</span>
            </div>
            <div class="info-item">
                <img src="/cardhaven/assets/image/cart.svg" alt="Cart" class="info-icon">
                <span class="info-text"><span id="displayCartCount" class="text-orange">0</span> Product in Cart</span>
            </div>
            <div class="info-item expenditure-item">
                <img src="/cardhaven/assets/image/rupiah.svg" alt="Rupiah" class="info-icon">
                <div class="expenditure-text">
                    <span class="info-text">Total Spent</span>
                    <span id="displayExpenditure" class="text-orange">Rp. 0</span>
                </div>
            </div>
        </div>
    </div>
</div>