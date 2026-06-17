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
    // 1. Inisialisasi Tampilan Awal
    setText("userName", sessionStorage.getItem("username") || sessionStorage.getItem("nama") || "Guest");
    setText("userEmail", sessionStorage.getItem("userEmail") || "-");

    // 2. Load Data dari Database
    loadData();

    // 3. Referensi Elemen Utama
    const form = document.getElementById("accountForm");
    const btnDeactivate = document.getElementById("btnDeactivate");
    const btnDelete = document.getElementById("btnDelete");

    // 4. Referensi Elemen Modal Change Password
    const pwModal = document.getElementById("pwModal");
    const pwForm = document.getElementById("pwForm");
    const btnOpenPwModal = document.getElementById("btnOpenPwModal");
    const btnClosePwModal = document.getElementById("btnClosePwModal");

    // --- EVENT LISTENERS PROFIL ---

    if (form) {
        form.addEventListener("submit", handleSubmit);
    }

    if (btnDeactivate) {
        btnDeactivate.addEventListener("click", handleDeactivate);
    }

    if (btnDelete) {
        btnDelete.addEventListener("click", handleDelete);
    }

    // --- EVENT LISTENERS MODAL CHANGE PASSWORD ---

    // Buka Modal
    if (btnOpenPwModal) {
        btnOpenPwModal.onclick = () => {
            pwModal.style.display = "flex";
        };
    }

    // Tutup Modal (Tombol X atau Cancel)
    if (btnClosePwModal) {
        btnClosePwModal.onclick = () => {
            pwModal.style.display = "none";
            if (pwForm) pwForm.reset();
        };
    }

    // Tutup Modal jika klik di luar area modal (Overlay)
    window.onclick = (event) => {
        if (event.target === pwModal) {
            pwModal.style.display = "none";
            if (pwForm) pwForm.reset();
        }
    };

    // Handle Submit Change Password
    if (pwForm) {
        pwForm.addEventListener("submit", async (e) => {
            e.preventDefault();

            const curPw = document.getElementById("current_password").value;
            const newPw = document.getElementById("new_password").value;
            const cfPw = document.getElementById("confirm_new_password").value;

            // Validasi client-side
            if (newPw !== cfPw) {
                alert("New password confirmation does not match!");
                return;
            }

            if (newPw.length < 6) {
                alert("New password must be at least 6 characters long.");
                return;
            }

            try {
                const formData = new FormData();
                formData.append("action", "change_password");
                formData.append("id_pengguna", userId);
                formData.append("current_password", curPw);
                formData.append("new_password", newPw);

                const res = await fetch(controllerUrl, {
                    method: "POST",
                    body: formData
                });

                const data = await res.json();

                if (data.status === "success") {
                    alert("Password updated successfully!");
                    pwModal.style.display = "none";
                    pwForm.reset();
                } else {
                    // Menampilkan pesan error dari server (misal: password lama salah)
                    alert(data.message || "Failed to update password");
                }
            } catch (err) {
                console.error("Error updating password:", err);
                alert("A server error occurred. Please try again.");
            }
        });
    }
});

async function loadData() {
    try {
        const res = await fetch(`${controllerUrl}?action=get&id_pengguna=${encodeURIComponent(userId)}`);
        const data = await res.json();

        if (data.status !== "success") {
            alert(data.message || "Gagal ambil data");
            return;
        }

        const user = data.data;
        setValue("nama", user.username || "");
        setValue("email", user.email || "");
        setText("statusAkun", `Status: ${user.status_akun == 1 ? "Aktif" : "Nonaktif"}`);
        setText("profileInfo", `${user.username || "-"} • ${user.email || "-"}`);

        const foto = document.getElementById("fotoProfil");
        if (foto && user.foto_profil) {
            foto.src = `/cardhaven/image-profile/${user.foto_profil}`;
        }
    } catch (err) {
        alert("Gagal konek ke server");
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
            alert("Profile updated!");
            location.reload();
        } else {
            alert(data.message);
        }
    } catch (err) { alert("Error connecting to server"); }
}

async function handleDeactivate() {
    if (!confirm("Yakin mau nonaktifkan akun ini?")) return;

    try {
        const formData = new FormData();
        formData.append("action", "deactivate");
        formData.append("id_pengguna", userId);

        const res = await fetch(controllerUrl, {
            method: "POST",
            body: formData
        });

        const data = await res.json();

        if (data.status === "success") {
            sessionStorage.clear();
            localStorage.clear();
            alert(data.message || "Akun dinonaktifkan");
            window.location.href = "home";
        } else {
            alert(data.message || "Gagal menonaktifkan akun");
        }
    } catch (err) {
        alert("Gagal konek ke server");
        console.error(err);
    }
}

async function handleDelete() {
    if (!confirm("Yakin mau hapus akun? Akun akan dinonaktifkan dan kamu akan logout.")) return;

    try {
        const formData = new FormData();
        formData.append("action", "delete");
        formData.append("id_pengguna", userId);

        const res = await fetch(controllerUrl, {
            method: "POST",
            body: formData
        });

        const data = await res.json();

        if (data.status === "success") {
            sessionStorage.clear();
            localStorage.clear();
            alert(data.message || "Akun dinonaktifkan");
            window.location.href = "../../login-page/";
        } else {
            alert(data.message || "Gagal menghapus akun");
        }
    } catch (err) {
        alert("Gagal konek ke server");
        console.error(err);
    }
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