<style>

@keyframes cardhavenShow {
    from {
        opacity: 0;
        transform: scale(.9) translateY(-15px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

@keyframes cardhavenHide {
    from {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
    to {
        opacity: 0;
        transform: scale(.95) translateY(-10px);
    }
}

.swal2-show.cardhaven-popup {
    animation: cardhavenShow .35s cubic-bezier(.16,1,.3,1) forwards !important;
}

.swal2-hide.cardhaven-popup {
    animation: cardhavenHide .25s ease forwards !important;
}

.cardhaven-popup {
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

.cardhaven-title {
    color: #0D47A1 !important;
    font-size: 1.8rem !important;
    font-weight: 700 !important;
    margin-bottom: .5rem !important;
}

.cardhaven-text {
    color: #718096 !important;
    font-size: .95rem !important;
    line-height: 1.6 !important;
}

/* ICON */

.swal2-icon.swal2-warning {
    border-color: #1976D2 !important;
    color: #1976D2 !important;
}

.swal2-icon.swal2-warning .swal2-icon-content {
    font-size: 3rem !important;
    font-weight: 700 !important;
}

/* ==========================
   BUTTON AREA
========================== */

.swal2-actions {
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;

    gap: 14px !important;

    width: 100% !important;

    margin-top: 1.8rem !important;
}

/* Tombol Confirm */

.btn-confirm {
    flex: 1 !important;
    max-width: 170px !important;
    min-width: 170px !important;
}

/* Tombol Cancel */

.btn-cancel-outline {
    flex: 1 !important;

    max-width: 170px !important;
    min-width: 170px !important;

    background: #FFFFFF !important;

    color: #0D47A1 !important;

    border: 2px solid #1976D2 !important;

    border-radius: 999px !important;

    padding: 12px 24px !important;

    font-size: 1rem !important;
    font-weight: 700 !important;

    cursor: pointer !important;

    transition: all .25s ease !important;
}

.btn-cancel-outline:hover {
    background: #EEF4FF !important;
    transform: translateY(-2px);
}

.btn-cancel-outline:active {
    transform: scale(.97);
}
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const btnLogoutTrigger = document.getElementById("btnLogout");

    if (btnLogoutTrigger) {

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

                showClass: {
                    popup: 'swal2-show cardhaven-popup'
                },

                hideClass: {
                    popup: 'swal2-hide cardhaven-popup'
                },

                customClass: {
                    popup: "cardhaven-popup",
                    title: "coolveticaa cardhaven-title",
                    htmlContainer: "cardhaven-text",
                    confirmButton: "btn-confirm",
                    cancelButton: "btn-cancel-outline"
                }

            }).then((result) => {

                if (result.isConfirmed) {

                    localStorage.clear();
                    sessionStorage.clear();

                    window.location.href = "/CardHaven/home";
                }

            });

        });

    }

});
</script>