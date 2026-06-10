function cardhavenConfirm(title, text, confirmText, callback) {
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
            callback();
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
    }).then(() => {
        if (callback) callback();
    });
}