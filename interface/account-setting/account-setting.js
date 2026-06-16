const controllerUrl = '/CardHaven/interface/account-setting/account-setting-controller.php';
const userId = sessionStorage.getItem("id_pengguna") || localStorage.getItem("id_pengguna");

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
    if (form) {
        form.addEventListener("submit", handleSubmit);
    }

    const btnDeactivate = document.getElementById("btnDeactivate");
    if (btnDeactivate) {
        btnDeactivate.addEventListener("click", handleDeactivate);
    }

    const btnDelete = document.getElementById("btnDelete");
    if (btnDelete) {
        btnDelete.addEventListener("click", handleDelete);
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
    const password = document.getElementById("password").value.trim();
    const confirmPassword = document.getElementById("confirmPassword").value.trim();

    if (!nama || !email) {
        alert("Nama dan email wajib diisi");
        return;
    }

    if (password || confirmPassword) {
        if (password !== confirmPassword) {
            alert("Password dan konfirmasi password tidak sama");
            return;
        }
    }

    try {
        const formData = new FormData();
        formData.append("action", "update");
        formData.append("id_pengguna", userId);
        formData.append("nama", nama);
        formData.append("email", email);
        formData.append("password", password);
        formData.append("confirm_password", confirmPassword);

        const res = await fetch(controllerUrl, {
            method: "POST",
            body: formData
        });

        const data = await res.json();

        if (data.status === "success") {
            sessionStorage.setItem("username", nama);
            sessionStorage.setItem("nama", nama);
            sessionStorage.setItem("userEmail", email);

            alert(data.message || "Data berhasil diupdate");
            location.reload();
        } else {
            alert(data.message || "Update gagal");
        }
    } catch (err) {
        alert("Gagal konek ke server");
        console.error(err);
    }
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