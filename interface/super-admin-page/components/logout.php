<style>
/* =========================================
   CARDHAVEN SWEETALERT THEME
========================================= */

.cardhaven-popup{
    background: linear-gradient(
        180deg,
        #F8FBFF 0%,
        #EEF4FF 100%
    ) !important;

    border-radius: 24px !important;
    padding: 1.8rem !important;

    border: 1px solid rgba(25,118,210,.15);

    box-shadow:
        0 12px 30px rgba(13,71,161,.12) !important;
}

.cardhaven-title{
    color:#0D47A1 !important;
    font-size:1.8rem !important;
    font-weight:700 !important;
    margin-bottom:.5rem !important;
}

.cardhaven-text{
    color:#718096 !important;
    font-size:.95rem !important;
    line-height:1.6 !important;
}

/* ==========================
   ICON WARNING
========================== */

.swal2-icon{
    animation:none !important;
}

.swal2-icon-content{
    animation:none !important;
}

.swal2-icon.swal2-warning{
    border-color:#1976D2 !important;
    color:#1976D2 !important;
}

.swal2-icon.swal2-warning .swal2-icon-content{
    font-size:3rem !important;
    font-weight:700 !important;
}

/* Hilangkan animasi bawaan */
.swal2-show,
.swal2-hide{
    animation:none !important;
}

/* ==========================
   BUTTON AREA
========================== */

.swal2-actions{
    margin-top:1.8rem !important;
    gap:18px !important;
}

/* Tombol Logout */

.cardhaven-btn-confirm{
    min-width:160px !important;

    background:linear-gradient(
        180deg,
        #2EA3FF 0%,
        #1976D2 100%
    ) !important;

    color:white !important;
    border:none !important;

    border-radius:999px !important;

    padding:13px 34px !important;

    font-size:1rem !important;
    font-weight:700 !important;

    box-shadow:
        0 6px 15px rgba(25,118,210,.25) !important;

    transition:all .2s ease !important;
}

.cardhaven-btn-confirm:hover{
    transform:translateY(-2px);
    filter:brightness(1.05);
}

/* Tombol Cancel */

.cardhaven-btn-cancel{
    min-width:160px !important;

    background:#FFFFFF !important;

    color:#0D47A1 !important;

    border:2px solid #1976D2 !important;

    border-radius:999px !important;

    padding:13px 34px !important;

    font-size:1rem !important;
    font-weight:700 !important;

    transition:all .2s ease !important;
}

.cardhaven-btn-cancel:hover{
    background:#EEF4FF !important;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const btnLogoutTrigger = document.getElementById("btnLogout");

    if(btnLogoutTrigger){

        btnLogoutTrigger.addEventListener("click", (e) => {

            e.preventDefault();

            Swal.fire({
                title: "Confirm Logout",
                text: "Are you sure you want to logout from your account?",
                icon: "warning",

                showCancelButton: true,

                confirmButtonText: "Logout",
                cancelButtonText: "Cancel",

                background: "transparent",

                backdrop: "rgba(13,71,161,.25)",

                buttonsStyling: false,

                customClass: {
                    popup: "cardhaven-popup",
                    title: "coolveticaa cardhaven-title",
                    htmlContainer: "cardhaven-text",
                    confirmButton: "cardhaven-btn-confirm",
                    cancelButton: "cardhaven-btn-cancel"
                }

            }).then((result) => {

                if(result.isConfirmed){

                    localStorage.clear();
                    sessionStorage.clear();

                    window.location.href = "/CardHaven/home";
                }

            });

        });

    }

});
</script>