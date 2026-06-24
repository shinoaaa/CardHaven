<div class="nav-bar">
    <div class="nav-content">
        <div class="nav-logo">
            <img src="/cardhaven/assets/image/logo.svg" style="object-fit: cover; width: 100%; height: 100%;">
        </div>
        <div class="nav-menu">
            <div class="nav-search">
                <input type="text" style="height: 85%; width: 80%; border: 1px solid var(--primary-color); border-radius: 9999px;" placeholder="Type Product Name">
                <div style="height: 85%; aspect-ratio: 1/1; background-color: var(--primary-color); border-radius: 9999px; display: flex; justify-content: center; align-items: center;">
                    <img src="/cardhaven/assets/image/search.svg" style="object-fit: cover; width: 60%; height: 60%;">
                </div>
            </div>
            <div class="nav-profile">
                <button id="btn-sign" style="height: 60%; width: 35%; border-radius: 9999px; background: var(--bg-gradient); color: white; font-size: 1.25rem;">
                    <a class="coolveticaa" href="register" style="color: white;">
                        Sign In
                    </a>
                </button>
                <div style="height: 100%; display: flex; align-items: center; gap: 0.75rem;">
                    <h3 class="coolveticaa" id="namaUser" style="color: var(--primary-color); font-size: 1.25rem; margin-right: 0.75rem;"></h3>
                    <div style="height: 100%; aspect-ratio: 1/1; background-color: blue; border-radius: 9999px; overflow: hidden; border: 1px solid var(--primary-color);">
                        <img src="https://i.pinimg.com/736x/5e/14/90/5e149094251c9316fc696e7aeba7b2b1.jpg" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div style="height: 60%; aspect-ratio: 1/1;">
                        <a href="/cardhaven/interface/cart/index.php" style="height: 60%; aspect-ratio: 1/1; display: block;">
                            <img src="/cardhaven/assets/image/cart.svg" style="object-fit: cover; width: 100%; height: 100%; cursor: pointer;" title="Keranjang Belanja">
                        </a>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    const isUser = localStorage.getItem('username') || sessionStorage.getItem('username');
    const signBtn = document.getElementById('btn-sign');
    const namaUser = document.getElementById('namaUser');

    if(isUser){
        signBtn.style.display = 'none';
        namaUser.textContent = isUser
    }

</script>