const controllerUrl = '/CardHaven/interface/account-setting/account-setting-controller.php';
const userId = sessionStorage.getItem("id_pengguna") || localStorage.getItem("id_pengguna");
const pwModal = document.getElementById("pwModal");
const btnOpenPwModal = document.getElementById("btnOpenPwModal");
const btnClosePwModal = document.getElementById("btnClosePwModal");
const pwForm = document.getElementById("pwForm");

if (!userId) {
    window.location.href = "../../login-page/";
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
}

function setValue(id, value) {
    const el = document.getElementById(id);
    if (el) el.value = value;
}

document.addEventListener("DOMContentLoaded", () => {
    setText("userName", sessionStorage.getItem("username") || sessionStorage.getItem("nama") || "Guest");
    setText("userEmail", sessionStorage.getItem("userEmail") || "-");

    loadData();

    const form = document.getElementById("accountForm");
    const btnDeactivate = document.getElementById("btnDeactivate");
    const btnDelete = document.getElementById("btnDelete");

    if (form) form.addEventListener("submit", handleSubmit);
    if (btnDeactivate) btnDeactivate.addEventListener("click", handleDeactivate);
    if (btnDelete) btnDelete.addEventListener("click", handleDelete);

    if (btnOpenPwModal) {
        btnOpenPwModal.onclick = () => { pwModal.style.display = "flex"; };
    }

    if (btnClosePwModal) {
        btnClosePwModal.onclick = () => {
            pwModal.style.display = "none";
            if (pwForm) pwForm.reset();
        };
    }

    window.onclick = (event) => {
        if (event.target === pwModal) {
            pwModal.style.display = "none";
            if (pwForm) pwForm.reset();
        }
    };

    if (pwForm) {
        pwForm.addEventListener("submit", async (e) => {
            e.preventDefault();

            const curPw = document.getElementById("current_password").value;
            const newPw = document.getElementById("new_password").value;
            const cfPw = document.getElementById("confirm_new_password").value;

            if (newPw !== cfPw) {
                cardhavenAlert('error', 'Error', "New password confirmation does not match!");
                return;
            }

            // 8-12 karakter + kombinasi huruf, angka, dan simbol.
            if (newPw.length < 8 || newPw.length > 12) {
                cardhavenAlert('error', 'Error', "New password must be 8-12 characters long.");
                return;
            }
            if (!/[A-Za-z]/.test(newPw) || !/[0-9]/.test(newPw) || !/[!@#$%^&*(),.?":{}|<>_\-]/.test(newPw)) {
                cardhavenAlert('error', 'Error', "New password must be a combination of letters, numbers, and a symbol.");
                return;
            }

            try {
                const formData = new FormData();
                formData.append("action", "change_password");
                formData.append("id_pengguna", userId);
                formData.append("current_password", curPw);
                formData.append("new_password", newPw);

                const res = await fetch(controllerUrl, { method: "POST", body: formData });
                const data = await res.json();

                if (data.status === "success") {
                    cardhavenAlert('success', 'Success', data.message, () => {
                        pwModal.style.display = "none";
                        pwForm.reset();
                    });
                } else {
                    cardhavenAlert('error', 'Failed', data.message);
                }
            } catch (err) {
                console.error("Error updating password:", err);
                cardhavenAlert('error', 'Server Error', "A server error occurred. Please try again.");
            }
        });
    }
});

async function loadData() {
    try {
        const res = await fetch(`${controllerUrl}?action=get&id_pengguna=${encodeURIComponent(userId)}`);
        const data = await res.json();

        if (data.status !== "success") {
            cardhavenAlert('error', 'Failed', data.message || "Failed to fetch user data.");
            return;
        }

        const user = data.data;
        setValue("nama", user.username || "");
        setValue("email", user.email || "");
        setText("statusAkun", `Status: ${user.status_akun == 1 ? "Active" : "Inactive"}`);
        setText("profileInfo", `${user.username || "-"} • ${user.email || "-"}`);

        const foto = document.getElementById("fotoProfil");
        if (foto && user.foto_profil) {
            foto.src = `/cardhaven/image-profile/${user.foto_profil}`;
        }
    } catch (err) {
        cardhavenAlert('error', 'Server Error', "Failed to connect to the server.");
        console.error(err);
    }
}

async function handleSubmit(e) {
    e.preventDefault();
    const nama = document.getElementById("nama").value.trim();
    const email = document.getElementById("email").value.trim();

    try {
        const formData = new FormData();
        formData.append("action", "update");
        formData.append("id_pengguna", userId);
        formData.append("nama", nama);
        formData.append("email", email);

        const res = await fetch(controllerUrl, { method: "POST", body: formData });
        const data = await res.json();

        if (data.status === "success") {
            sessionStorage.setItem("username", nama);
            cardhavenAlert('success', 'Success', data.message, () => location.reload());
        } else {
            cardhavenAlert('error', 'Failed', data.message);
        }
    } catch (err) { 
        cardhavenAlert('error', 'Server Error', "Error connecting to server.");
    }
}

async function handleDeactivate() {
    cardhavenConfirm("Deactivate Account", "Are you sure you want to deactivate this account?", "Yes, Deactivate", async () => {
        try {
            const formData = new FormData();
            formData.append("action", "deactivate");
            formData.append("id_pengguna", userId);

            const res = await fetch(controllerUrl, { method: "POST", body: formData });
            const data = await res.json();

            if (data.status === "success") {
                sessionStorage.clear();
                localStorage.clear();
                cardhavenAlert('success', 'Deactivated', data.message, () => {
                    window.location.href = "/CardHaven";
                });
            } else {
                cardhavenAlert('error', 'Failed', data.message);
            }
        } catch (err) {
            cardhavenAlert('error', 'Server Error', "Failed to connect to the server.");
        }
    });
}

async function handleDelete() {
    cardhavenConfirm("Delete Account", "Are you sure you want to delete this account? You will be logged out.", "Yes, Delete", async () => {
        try {
            const formData = new FormData();
            formData.append("action", "delete");
            formData.append("id_pengguna", userId);

            const res = await fetch(controllerUrl, { method: "POST", body: formData });
            const data = await res.json();

            if (data.status === "success") {
                sessionStorage.clear();
                localStorage.clear();
                cardhavenAlert('success', 'Deleted', data.message, () => {
                    window.location.href = "../../login-page/";
                });
            } else {
                cardhavenAlert('error', 'Failed', data.message);
            }
        } catch (err) {
            cardhavenAlert('error', 'Server Error', "Failed to connect to the server.");
        }
    });
}

// === Card showcase interaction ===
document.addEventListener("DOMContentLoaded", () => {
    const showcase = document.querySelector('.card-showcase');
    const cards = document.querySelectorAll('.card-float');

    if (!showcase || cards.length === 0) return;

    showcase.addEventListener('mousemove', (e) => {
        const rect = showcase.getBoundingClientRect();
        const x = (e.clientX - rect.left) / rect.width - 0.5;
        const y = (e.clientY - rect.top) / rect.height - 0.5;

        cards.forEach((card, index) => {
            const depth = (index + 1) * 15;
            const moveX = x * depth;
            const moveY = y * depth;
            const rotateX = y * -10;
            const rotateY = x * 10;
            const baseRotation = getComputedStyle(card).getPropertyValue('--rot') || '0deg';

            card.style.transform = `
                translate(${moveX}px, ${moveY}px)
                rotateX(${rotateX}deg)
                rotateY(${rotateY}deg)
                rotate(${baseRotation})
            `;
        });
    });

    showcase.addEventListener('mouseleave', () => {
        cards.forEach((card) => {
            const baseRotation = getComputedStyle(card).getPropertyValue('--rot') || '0deg';
            card.style.transform = `rotate(${baseRotation})`;
        });
    });

    cards.forEach((card) => {
        card.addEventListener('mouseenter', () => {
            card.style.transform += ' scale(1.08) translateY(-10px)';
        });
    });
});