<script>
document.addEventListener("DOMContentLoaded", () => {
    const btnLogoutTrigger = document.getElementById("btnLogout");

    if (btnLogoutTrigger) {
        btnLogoutTrigger.addEventListener("click", (e) => {
            e.preventDefault();
            
            cardhavenConfirm(
                "Confirm Logout", 
                "Are you sure you want to logout from your account?", 
                "Logout", 
                () => {
                    localStorage.clear();
                    sessionStorage.clear();
                    window.location.href = "/CardHaven/home";
                }
            );
        });
    }
});
</script>