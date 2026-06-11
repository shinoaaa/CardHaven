document.addEventListener("DOMContentLoaded", () => {
document.getElementById('signupForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    // Ambil elemen input
    const username = this.querySelector('input[name="username"]');
    const email = this.querySelector('input[name="email"]');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');

    // Ambil elemen error
    const usernameError = document.getElementById('usernameError');
    const emailError = document.getElementById('emailError');
    const passwordError = document.getElementById('passwordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');

    // Reset status error
    resetErrors([username, email, password, confirmPassword], 
                [usernameError, emailError, passwordError, confirmPasswordError]);

    let isValid = true;

    // 1. Validasi Kosong
    if (!username.value.trim()) {
        showError(username, usernameError, "Please enter your username");
        isValid = false;
    }

    if (!email.value.trim()) {
        showError(email, emailError, "Please enter your email");
        isValid = false;
    }

    // 2. Validasi Panjang Password (8-12 karakter)
if (!password.value.trim()) {
    showError(password, passwordError, "Password must be filled");
    isValid = false;
} else if (password.value.length < 8 || password.value.length > 12) {
    showError(password, passwordError, "Password must be 8 - 12 characters long");
    isValid = false;
} else if (!/[!@#$%^&*(),.?":{}|<>]/.test(password.value)) {
    showError(password, passwordError, "Password must contain a special character");
    isValid = false;
}

    // 3. Validasi Konfirmasi Password
if (!confirmPassword.value) {
    showError(confirmPassword, confirmPasswordError, "Please confirm your password");
    isValid = false;
} else if (password.value !== confirmPassword.value) {
    showError(confirmPassword, confirmPasswordError, "Confirm password does not match!");
    isValid = false;
}

    if (!isValid) return;

    // Loading state
const btnSubmit    = this.querySelector('button[type="submit"]');
    const originalText = btnSubmit.innerText;
    btnSubmit.innerText = "Processing...";
    btnSubmit.disabled  = true;

    // Jika valid, kirim data ke PHP
    const formData = new FormData(this);

    try {
        const response = await fetch('/CardHaven/interface/register-page/proses_signup.php', {
            method: 'POST',
            body: formData
        });

        // Ambil text mentah dulu untuk mengecek apakah ada error PHP (HTML)
        const responseText = await response.text();
        
        try {
            // Coba ubah text ke JSON
            const data = JSON.parse(responseText);
            
            if (data.status === 'success') {
    Swal.fire({
        icon:             'success',
        iconColor:        '#0088FF',
        title:            'Registration Successful!',
        text:             'Your account has been created. Please login.',
        showConfirmButton: false,
        timer:            1800,
        background:       '#ffffff',
        customClass: { title: 'coolveticaa' }
    }).then(() => {
        window.location.href = 'home';
    });
            } else {
    btnSubmit.innerText = originalText;
    btnSubmit.disabled  = false;

    if (data.message.toLowerCase().includes('username')) {
        showError(username, usernameError, data.message);
    } else if (data.message.toLowerCase().includes('email')) {
        showError(email, emailError, data.message);
    } else if (data.message.toLowerCase().includes('password')) {
        showError(password, passwordError, data.message);
    } else {
        Swal.fire({
            icon:              'error',
            title:             'Registration Failed',
            text:              data.message,
            confirmButtonText: 'OK',
            buttonsStyling:    false,
            customClass: {
                popup:         'cardhaven-popup',
                title:         'coolveticaa cardhaven-title',
                confirmButton: 'btn-confirm'
            }
        });
    }
}
        } catch (jsonError) {
    btnSubmit.innerText = originalText;
    btnSubmit.disabled  = false;
    console.error("Server Error Response:", responseText);
    Swal.fire({
        icon:              'error',
        title:             'System Error',
        text:              'An error occurred on the server.',
        confirmButtonText: 'OK',
        buttonsStyling:    false,
        customClass: {
            popup:         'cardhaven-popup',
            title:         'coolveticaa cardhaven-title',
            confirmButton: 'btn-confirm'
        }
    });
}

    } catch (error) {
    btnSubmit.innerText = originalText;
    btnSubmit.disabled  = false;
    console.error('Fetch Error:', error);
    Swal.fire({
        icon:              'error',
        title:             'Connection Error',
        text:              'Unable to connect to the server.',
        confirmButtonText: 'OK',
        buttonsStyling:    false,
        customClass: {
            popup:         'cardhaven-popup',
            title:         'coolveticaa cardhaven-title',
            confirmButton: 'btn-confirm'
        }
    });
}
});
});
function showError(inputElement, errorElement, message) {
    inputElement.style.borderColor = "red";
    errorElement.innerText = message;
    errorElement.style.display = "block";
}

function resetErrors(inputs, errors) {
    inputs.forEach(input => {
        if (input) input.style.borderColor = "#0F3891";
    });
    errors.forEach(error => {
        if (error) {
            error.style.display = "none";
            error.innerText     = "";
        }
    });
}