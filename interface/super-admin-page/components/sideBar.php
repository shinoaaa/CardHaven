<div style="width: 100%; height: 100%; display: flex; align-items: center; flex-direction: column;">
    <div class="logo-wrap">
        <img src="/cardhaven/assets/image/logo.svg">
    </div>

    <div class="profile-employee">
        <div class="photo-Profile">
            <img src="https://i.pinimg.com/736x/e8/2b/43/e82b43056d04e86c577a443485049d9b.jpg" style="object-fit: cover; width: 100%; height: 100%;">
        </div>
        <div class="userTag">
            <h2 class="coolveticaa" style="color: white; font-size: .65rem;">Super Admin</h2>
        </div>
        <div style="margin-top: 1rem;">
            <h2 id="userName" class="coolveticaa" style="font-size: 1rem; color: var(--primary-color);"></h2>
            <h3 id="userEmail" style="font-size: 0.75rem; opacity: 55%; margin: 0.25rem 0 0 0;"></h3>
        </div>
        <div style="width: 100%; margin-top: 0.5rem; display: flex; justify-content: center; gap: .75rem;">
            <a href="">
                <img src="/cardhaven/assets/image/inbox.svg" style="object-fit:fill; width: 1.15rem; height: 1.15rem;">
            </a>
            <a href="javascript:void(0)" id="btnLogout">
                <img src="/cardhaven/assets/image/logout.svg" style="object-fit:fill; width: 1.15rem; height: 1.15rem;">
            </a>
        </div>
    </div>

    <div class="navMenu">
        <h2 class="coolveticaa" style="font-size: 1rem; color: var(--primary-color); margin-bottom: 0.5rem;">Menu</h2>

        <div class="menuOption unselected">
            <a href="superadmin/jogja" style="display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit;">
                <img src="/cardhaven/assets/image/analytics.svg">
                <h2 class="coolveticaa" style="color: var(--highlight)">Dashboard</h2>
            </a>
        </div>
        <div class="menuOption unselected">
            <a href="#" style="display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit;">
                <img src="/cardhaven/assets/image/transaction.svg">
                <h2 class="coolveticaa" style="color: var(--highlight)">Transaction</h2>
            </a>
        </div>
        <div class="menuOption selectedOption">
            <a href="#" style="display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit;">
                <img src="/cardhaven/assets/image/product.svg">
                <h2 class="coolveticaa" style="color: var(--highlight)">Product</h2>
            </a>
        </div>
        <div class="menuOption unselected">
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
    document.getElementById("userName").textContent =
        sessionStorage.getItem("username") || localStorage.getItem("username");

    document.getElementById("userEmail").textContent =
        sessionStorage.getItem("userEmail") || localStorage.getItem("userEmail");
});
</script>