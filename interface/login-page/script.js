document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById("loginForm");
    const emailInput = document.getElementById("emailInput");
    const passwordInput = document.getElementById("passwordInput");
    const errorEmail = document.getElementById("error-email");
    const errorPass = document.getElementById("error-pass");
    const checkBox = document.getElementById("checkbox");
    const checkText = document.getElementById("checkText");
    const forgotButton = document.getElementById("forgot-button");
    const loginWrap = document.getElementById("login-wrap");
    const forgotWrap = document.getElementById("forgot-wrap");

    const forgotForm = document.getElementById("forgotForm");
    const forgotEmailInput = document.getElementById("forgot-email");
    const forgotCreatedDateInput = document.getElementById("forgot-created-date");
    const forgotPasswordInput = document.getElementById("forgot-password");
    const forgotConfirmPasswordInput = document.getElementById("forgot-confirm-password");

    const forgotErrorEmail = document.getElementById("forgot-error-email");
    const forgotErrorCreatedDate = document.getElementById("forgot-error-created-date");
    const forgotErrorPassword = document.getElementById("forgot-error-password");
    const forgotErrorConfirmPassword = document.getElementById("forgot-error-confirm-password");

    const passwordSection = document.getElementById("password-section");
    const forgotSubmit = document.getElementById("forgot-submit");
    const backToLogin = document.getElementById("back-to-login");

    let clicked = false;
    let forgotClicked = false;
    let verifyStepDone = false;

    const rememberMe = () => {
        if (!clicked) {
            checkBox.style.backgroundColor = "#0088FF";
            checkBox.style.color = "white";
            clicked = true;
        } else {
            checkBox.style.backgroundColor = "";
            checkBox.style.color = "#0088FF";
            clicked = false;
        }
    };

    if (checkBox) checkBox.addEventListener("click", rememberMe);
    if (checkText) checkText.addEventListener("click", rememberMe);

    // ==========================================
    // LOGIKA LOGIN
    // ==========================================
    loginForm.addEventListener("submit", async function (e) {
        e.preventDefault();
        resetErrors([emailInput, passwordInput], [errorEmail, errorPass]);

        const email = emailInput.value.trim();
        const password = passwordInput.value.trim();
        let isValid = true;

        if (!email) {
            showError(emailInput, errorEmail, "Please enter your email");
            isValid = false;
        }
        if (!password) {
            showError(passwordInput, errorPass, "Please enter your password");
            isValid = false;
        }
        if (!isValid) return;

        const formData = new FormData(this);
        formData.append("remember", clicked);

        // Ubah tombol submit saat proses (UX Enhancement)
        const btnSubmit = this.querySelector('button[type="submit"]');
        const originalText = btnSubmit.innerText;
        btnSubmit.innerText = "Processing...";
        btnSubmit.disabled = true;

        try {
            const response = await fetch("/CardHaven/interface/login-page/login.php", {
                method: "POST",
                body: formData
            });

            const responseText = await response.text();

            try {
                const data = JSON.parse(responseText);

                if (data.status === "success") {
                    const storage = clicked ? localStorage : sessionStorage;
                    storage.setItem("userEmail", email);
                    storage.setItem("role", data.role);
                    storage.setItem("id_pengguna", data.id_pengguna);
                    storage.setItem("username", data.username);

                    // Animasi Sukses Login
                    Swal.fire({
                        icon: 'success',
                        iconColor: '#0088FF',
                        title: 'Login Berhasil!',
                        text: 'Selamat datang kembali di CardHaven.',
                        showConfirmButton: false,
                        timer: 1500,
                        background: '#ffffff',
                        customClass: { title: 'coolveticaa' }
                    }).then(() => {
                        if (data.role == 2) {
                            window.location.replace("/CardHaven/superadmin");
                        } else {
                            window.location.replace("/CardHaven/home");
                        }
                    });

                } else {
                    btnSubmit.innerText = originalText;
                    btnSubmit.disabled = false;

                    if (data.target === "email") {
                        showError(emailInput, errorEmail, data.message);
                    } else if (data.target === "password") {
                        showError(passwordInput, errorPass, data.message);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Login Gagal',
                            text: data.message,
                            confirmButtonColor: '#E74C3C',
                            customClass: { title: 'coolveticaa' }
                        });
                    }
                }
            } catch (jsonError) {
                btnSubmit.innerText = originalText;
                btnSubmit.disabled = false;
                console.error("Server Error Response:", responseText);
                Swal.fire({ icon: 'error', title: 'System Error', text: 'Terjadi kesalahan pada server.', confirmButtonColor: '#E74C3C', customClass: { title: 'coolveticaa' } });
            }
        } catch (error) {
            btnSubmit.innerText = originalText;
            btnSubmit.disabled = false;
            console.error("Fetch Error:", error);
            Swal.fire({ icon: 'error', title: 'Connection Error', text: 'Tidak dapat terhubung ke server.', confirmButtonColor: '#E74C3C', customClass: { title: 'coolveticaa' } });
        }
    });

    // ==========================================
    // LOGIKA NAVIGASI FORGOT PASSWORD
    // ==========================================
    if (forgotButton) {
        forgotButton.addEventListener("click", () => {
            loginWrap.style.display = "none";
            forgotWrap.style.display = "flex";
            forgotClicked = true;
        });
    }

    if (backToLogin) {
        backToLogin.addEventListener("click", () => {
            forgotWrap.style.display = "none";
            loginWrap.style.display = "flex";
            forgotClicked = false;
            resetForgotState();
        });
    }

    // ==========================================
    // LOGIKA SUBMIT FORGOT PASSWORD
    // ==========================================
    if (forgotForm) {
        forgotForm.addEventListener("submit", async (e) => {
            e.preventDefault();

            resetErrors(
                [forgotEmailInput, forgotCreatedDateInput, forgotPasswordInput, forgotConfirmPasswordInput],
                [forgotErrorEmail, forgotErrorCreatedDate, forgotErrorPassword, forgotErrorConfirmPassword]
            );

            // TAHAP 1: VERIFIKASI
            if (!verifyStepDone) {
                const email = forgotEmailInput.value.trim();
                const createdDate = forgotCreatedDateInput.value.trim();
                let isValid = true;

                if (!email) {
                    showError(forgotEmailInput, forgotErrorEmail, "Email tidak boleh kosong");
                    isValid = false;
                }
                if (!createdDate) {
                    showError(forgotCreatedDateInput, forgotErrorCreatedDate, "Created date tidak boleh kosong");
                    isValid = false;
                }
                if (!isValid) return;

                forgotSubmit.innerText = "Processing...";
                forgotSubmit.disabled = true;

                const formData = new FormData();
                formData.append("action", "verify");
                formData.append("email", email);
                formData.append("created_date", createdDate);

                try {
                    const response = await fetch("/CardHaven/interface/login-page/api.php", {
                        method: "POST",
                        body: formData
                    });
                    const responseText = await response.text();

                    try {
                        const data = JSON.parse(responseText);
                        if (data.status === "success") {
                            verifyStepDone = true;
                            passwordSection.style.display = "block";
                            forgotSubmit.textContent = "Update Password";
                            forgotSubmit.disabled = false;
                            forgotPasswordInput.focus();
                        } else {
                            forgotSubmit.textContent = "Verify";
                            forgotSubmit.disabled = false;

                            if (data.target === "email") {
                                showError(forgotEmailInput, forgotErrorEmail, data.message);
                            } else if (data.target === "created_date") {
                                showError(forgotCreatedDateInput, forgotErrorCreatedDate, data.message);
                            } else {
                                Swal.fire({ icon: 'error', title: 'Verifikasi Gagal', text: data.message, confirmButtonColor: '#E74C3C', customClass: { title: 'coolveticaa' } });
                            }
                        }
                    } catch (jsonError) {
                        forgotSubmit.textContent = "Verify";
                        forgotSubmit.disabled = false;
                        console.error("Server Error Response:", responseText);
                        Swal.fire({ icon: 'error', title: 'System Error', text: 'Terjadi kesalahan pada server.', confirmButtonColor: '#E74C3C', customClass: { title: 'coolveticaa' } });
                    }
                } catch (error) {
                    forgotSubmit.textContent = "Verify";
                    forgotSubmit.disabled = false;
                    console.error("Fetch Error:", error);
                    Swal.fire({ icon: 'error', title: 'Connection Error', text: 'Tidak dapat terhubung ke server.', confirmButtonColor: '#E74C3C', customClass: { title: 'coolveticaa' } });
                }
                return;
            }

            // TAHAP 2: RESET PASSWORD
            const password = forgotPasswordInput.value.trim();
            const confirmPassword = forgotConfirmPasswordInput.value.trim();
            let isValid = true;

            if (!password.trim()) {
                showError(forgotPasswordInput, forgotErrorPassword, "Password cannot be empty");
                isValid = false;
            } else if (password.length < 8 || password.length > 12) {
                showError(forgotPasswordInput, forgotErrorPassword, "Password must be 8 - 12 characters long");
                isValid = false;
            } else if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                showError(forgotPasswordInput, forgotErrorPassword, "Password must contain a special character");
                isValid = false;
            }

            if (!confirmPassword) {
                showError(forgotConfirmPasswordInput, forgotErrorConfirmPassword, "Confirm password cannot be empty");
                isValid = false;
            } else if (password !== confirmPassword) {
                showError(forgotConfirmPasswordInput, forgotErrorConfirmPassword, "Confirm password does not match");
                isValid = false;
            }

            if (!isValid) return;

            forgotSubmit.innerText = "Processing...";
            forgotSubmit.disabled = true;

            const formData = new FormData();
            formData.append("action", "reset");
            formData.append("password", password);
            formData.append("confirm_password", confirmPassword);

            try {
                const response = await fetch("/CardHaven/interface/login-page/api.php", {
                    method: "POST",
                    body: formData
                });
                const responseText = await response.text();

                try {
                    const data = JSON.parse(responseText);
                    if (data.status === "success") {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: data.message,
                            confirmButtonColor: '#0088FF',
                            customClass: { title: 'coolveticaa' }
                        }).then(() => {
                            resetForgotState();
                            forgotWrap.style.display = "none";
                            loginWrap.style.display = "flex";
                        });
                    } else {
                        forgotSubmit.textContent = "Update Password";
                        forgotSubmit.disabled = false;

                        if (data.target === "password") {
                            showError(forgotPasswordInput, forgotErrorPassword, data.message);
                        } else if (data.target === "confirm_password") {
                            showError(forgotConfirmPasswordInput, forgotErrorConfirmPassword, data.message);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Gagal', text: data.message, confirmButtonColor: '#E74C3C', customClass: { title: 'coolveticaa' } });
                        }
                    }
                } catch (jsonError) {
                    forgotSubmit.textContent = "Update Password";
                    forgotSubmit.disabled = false;
                    console.error("Server Error Response:", responseText);
                    Swal.fire({ icon: 'error', title: 'System Error', text: 'An error occurred on the server.', confirmButtonColor: '#E74C3C', customClass: { title: 'coolveticaa' } });
                }
            } catch (error) {
                forgotSubmit.textContent = "Update Password";
                forgotSubmit.disabled = false;
                console.error("Fetch Error:", error);
                Swal.fire({ icon: 'error', title: 'Connection Error', text: 'Unable to connect to the server.', confirmButtonColor: '#E74C3C', customClass: { title: 'coolveticaa' } });
            }
        });
    }

    function resetForgotState() {
        if (forgotForm) forgotForm.reset();
        if (passwordSection) passwordSection.style.display = "none";
        if (forgotSubmit) {
            forgotSubmit.textContent = "Verify";
            forgotSubmit.disabled = false;
        }
        verifyStepDone = false;

        resetErrors(
            [forgotEmailInput, forgotCreatedDateInput, forgotPasswordInput, forgotConfirmPasswordInput],
            [forgotErrorEmail, forgotErrorCreatedDate, forgotErrorPassword, forgotErrorConfirmPassword]
        );
    }
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
            error.innerText = "";
        }
    });
}