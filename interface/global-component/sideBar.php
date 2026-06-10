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
                <img src="/cardhaven/assets/image/inbox.svg" style="object-fit:fill; width: 1.35rem; height: 1.35rem;">
            </a>
            <a href="javascript:void(0)" id="btnLogout">
                <img src="/cardhaven/assets/image/logout.svg" style="object-fit:fill; width: 1.35rem; height: 1.35rem;">
            </a>
        </div>
    </div>

    <div class="navMenu">
        <h2 class="coolveticaa" style="font-size: 1rem; color: var(--primary-color); margin-bottom: 0.5rem;">Menu</h2>

        <div class="menuOption unselected" id="nav-dashboard">
            <a href="#" style="display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit;">
                <img src="/cardhaven/assets/image/analytics.svg">
                <h2 class="coolveticaa" style="color: var(--highlight)">Dashboard</h2>
            </a>
        </div>
        <div class="menuOption unselected" id="nav-transaction">
            <a href="#" style="display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit;">
                <img src="/cardhaven/assets/image/transaction.svg">
                <h2 class="coolveticaa" style="color: var(--highlight)">Transaction</h2>
            </a>
        </div>
        <div class="menuOption unselected" id="nav-product">
            <a href="#" style="display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit;">
                <img src="/cardhaven/assets/image/product.svg">
                <h2 class="coolveticaa" style="color: var(--highlight)">Product</h2>
            </a>
        </div>
        <div class="menuOption unselected" id="nav-event">
            <a href="#" style="display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit;">
                <img src="/cardhaven/assets/image/event.svg">
                <h2 class="coolveticaa" style="color: var(--highlight)">Event</h2>
            </a>
        </div>
        <div class="menuOption unselected" id="nav-sales">
            <a href="#" style="display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit;">
                <img src="/cardhaven/assets/image/sales-report.svg">
                <h2 class="coolveticaa" style="color: var(--highlight)">Sales Report</h2>
            </a>
        </div>
        <div class="menuOption unselected" id="nav-user">
            <a href="superadmin/user" style="display: flex; align-items: center; gap: .75rem; text-decoration: none; color: inherit;">
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
    const navDashboard = document.getElementById('nav-dashboard');
    const navTransaction = document.getElementById('nav-transaction');
    const navProduct = document.getElementById('nav-product');
    const navEvent = document.getElementById('nav-event');
    const navSales = document.getElementById('nav-sales');
    const navUser = document.getElementById('nav-user');
    const navSetting = document.getElementById('nav-setting');

    document.getElementById("userName").textContent =
        sessionStorage.getItem("username") || localStorage.getItem("username");

    document.getElementById("userEmail").textContent =
        sessionStorage.getItem("userEmail") || localStorage.getItem("userEmail");

        const currentUrl = window.location.href
        console.log(currentUrl);
        
        const role = Number.parseInt(sessionStorage.getItem("role")) || Number.parseInt(localStorage.getItem("role"));
        
        if(role === 2 || role === 1){
            navUser.style.display = 'none'
            navSales.style.display = 'none'
        }
        if(role === 1){
            navEvent.style.display = 'none';
        }

        switch (key) {
            case value:
                break;
        
            default:
                break;
        }
});

</script>