// function cardhavenConfirm(title, text, confirmText, callback) {
//     Swal.fire({
//         title,
//         text,
//         icon: "warning",

//         width: 420,

//         iconColor: "#0D47A1",

//         showCancelButton: true,

//         confirmButtonText: confirmText,
//         cancelButtonText: "Cancel",

//         buttonsStyling: false,

//         backdrop: "rgba(13,71,161,.25)",

//         customClass: {
//             popup: "cardhaven-popup",
//             title: "coolveticaa cardhaven-title",
//             htmlContainer: "cardhaven-text",
//             confirmButton: "btn-confirm",
//             cancelButton: "btn-cancel-outline"
//         }
//     }).then(result => {
//         if (result.isConfirmed && callback) {
//             callback();
//         }
//     });
// }

function cardhavenConfirm(title, text, confirmText, callback, cancelCallback) {
    Swal.fire({
        title,
        text,
        icon: "warning",
        width: 420,
        iconColor: "#0D47A1",
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: "Cancel",
        buttonsStyling: false,
        backdrop: "rgba(13,71,161,.25)",
        customClass: {
            popup: "cardhaven-popup",
            title: "coolveticaa cardhaven-title",
            htmlContainer: "cardhaven-text",
            confirmButton: "btn-confirm",
            cancelButton: "btn-cancel-outline"
        }
    }).then(result => {
        if (result.isConfirmed && callback) {
            callback(); // Jalan kalau di-ACC
        } else if (result.isDismissed && cancelCallback) {
            cancelCallback(); // Jalan kalau di-Cancel
        }
    });
}

// Toast notification (top-right, auto-dismiss, no OK button needed)
function cardhavenToast(iconType, title, timer = 2500) {
    return Swal.fire({
        icon: iconType,
        title,

        toast: true,
        position: "top-end",

        showConfirmButton: false,
        showCloseButton: true,   // tombol X biar bisa ditutup manual
        timer,
        timerProgressBar: true,

        iconColor: "#0D47A1",

        didOpen: (toastEl) => {
            toastEl.addEventListener("mouseenter", Swal.stopTimer);
            toastEl.addEventListener("mouseleave", Swal.resumeTimer);
        },

        customClass: {
            popup: "cardhaven-toast",
            title: "coolveticaa cardhaven-toast-title"
        }
    });
}

function cardhavenAlert(iconType, title, text, callback = null) {
    Swal.fire({
        icon: iconType,
        title,
        text,

        width: 420,

        iconColor: "#0D47A1",

        buttonsStyling: false,

        confirmButtonText: "OK",

        backdrop: "rgba(13,71,161,.25)",

        customClass: {
            popup: "cardhaven-popup",
            title: "coolveticaa cardhaven-title",
            htmlContainer: "cardhaven-text",
            confirmButton: "btn-confirm"
        }
    }).then(result => {
        if (result.isConfirmed && callback) {
            callback();
        }
    });
}

document.addEventListener("DOMContentLoaded", () => {
    const toastMsg = sessionStorage.getItem('ch_toast_msg');
    const toastIcon = sessionStorage.getItem('ch_toast_icon');
    if (toastMsg && typeof cardhavenToast === 'function') {
        cardhavenToast(toastIcon || 'success', toastMsg, 2500);
        sessionStorage.removeItem('ch_toast_msg');
        sessionStorage.removeItem('ch_toast_icon');
    }
});