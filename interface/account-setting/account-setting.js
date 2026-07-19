const controllerUrl = '/CardHaven/interface/account-setting/account-setting-controller.php';
// Identitas dari PHP session (window.CH_AUTH), bukan storage browser.
const userId = CardHavenAuth.id() || null;
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

// Halaman dimulai dalam mode "lihat". Tombol Change Detail membuka mode edit.
let editMode = false;
// Nilai profil tersimpan; dipakai tombol Cancel untuk membatalkan perubahan.
let originalData = { nama: "", email: "", no_telepon: "", fotoSrc: "" };

function setEditMode(on) {
    editMode = on;
    ['nama', 'email', 'no_telepon'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.readOnly = !on;
    });
    const fotoField = document.getElementById('fotoField');
    if (fotoField) fotoField.style.display = on ? 'block' : 'none';
    const btn = document.getElementById('btnEditSave');
    if (btn) btn.textContent = on ? 'Save Changes' : 'Change Detail';
    // Cancel hanya muncul saat edit; Delete Account disembunyikan saat edit.
    const btnCancel = document.getElementById('btnCancel');
    if (btnCancel) btnCancel.style.display = on ? 'inline-block' : 'none';
    const btnDeleteEl = document.getElementById('btnDelete');
    if (btnDeleteEl) btnDeleteEl.style.display = on ? 'none' : 'inline-block';
    if (!on) clearAllAccountErrors();
}

// Batalkan edit: kembalikan nilai tersimpan lalu balik ke mode "lihat".
function handleCancel() {
    setValue('nama', originalData.nama);
    setValue('email', originalData.email);
    setValue('no_telepon', originalData.no_telepon);
    const fotoInput = document.getElementById('fotoFile');
    if (fotoInput) fotoInput.value = '';
    const foto = document.getElementById('fotoProfil');
    if (foto && originalData.fotoSrc) foto.src = originalData.fotoSrc;
    setEditMode(false);
}

// Validasi inline: pesan merah di bawah input + border merah (bukan popup).
function showFieldError(inputId, errorId, message) {
    const input = document.getElementById(inputId);
    const error = document.getElementById(errorId);
    if (input) input.style.borderColor = 'red';
    if (error) { error.innerText = message; error.style.display = 'block'; }
}

function clearFieldError(inputId, errorId) {
    const input = document.getElementById(inputId);
    const error = document.getElementById(errorId);
    if (input) input.style.borderColor = '';
    if (error) { error.style.display = 'none'; error.innerText = ''; }
}

function clearAllAccountErrors() {
    [['nama', 'namaError'], ['email', 'emailError'], ['no_telepon', 'noTeleponError'], ['fotoFile', 'fotoError']]
        .forEach(([i, e]) => clearFieldError(i, e));
}

document.addEventListener("DOMContentLoaded", () => {
    setText("userName", CardHavenAuth.username() || "Guest");
    setText("userEmail", CardHavenAuth.email() || "-");

    loadData();

    const form = document.getElementById("accountForm");
    const btnDeactivate = document.getElementById("btnDeactivate");
    const btnDelete = document.getElementById("btnDelete");

    if (form) form.addEventListener("submit", handleSubmit);
    if (btnDeactivate) btnDeactivate.addEventListener("click", handleDeactivate);
    if (btnDelete) btnDelete.addEventListener("click", handleDelete);

    const btnCancel = document.getElementById("btnCancel");
    if (btnCancel) btnCancel.addEventListener("click", handleCancel);

    // Mulai dalam mode "lihat" (read-only).
    setEditMode(false);

    // Hilangkan error inline begitu user mengetik ulang di field-nya.
    [['nama', 'namaError'], ['email', 'emailError'], ['no_telepon', 'noTeleponError']].forEach(([inpId, errId]) => {
        const inp = document.getElementById(inpId);
        if (inp) inp.addEventListener('input', () => clearFieldError(inpId, errId));
    });

    // Preview foto baru sebelum disimpan.
    const fotoFile = document.getElementById('fotoFile');
    if (fotoFile) {
        fotoFile.addEventListener('change', () => {
            clearFieldError('fotoFile', 'fotoError');
            const f = fotoFile.files[0];
            const foto = document.getElementById('fotoProfil');
            if (f && foto) foto.src = URL.createObjectURL(f);
        });
    }

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
                // id_pengguna tidak dikirim — server memakai id dari session.
                const formData = new FormData();
                formData.append("action", "change_password");
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
        // Tanpa id_pengguna — server memakai id dari session.
        const res = await fetch(`${controllerUrl}?action=get`);
        const data = await res.json();

        if (data.status !== "success") {
            cardhavenAlert('error', 'Failed', data.message || "Failed to fetch user data.");
            return;
        }

        const user = data.data;
        setValue("nama", user.username || "");
        setValue("email", user.email || "");
        setValue("no_telepon", user.no_telepon || "");

        setText("statusAkun", `Status: ${user.status_akun == 1 ? "Active" : "Inactive"}`);
        setText("profileInfo", `${user.username || "-"} • ${user.email || "-"}`);

        const foto = document.getElementById("fotoProfil");
        const fotoSrc = user.foto_profil
            ? `/cardhaven/assets/image/image-profile/${user.foto_profil}`
            : '/cardhaven/assets/image/user.svg';
        if (foto) {
            // DB menyimpan nama file saja; folder ditambahkan di sini.
            foto.src = fotoSrc;
        }

        // Simpan nilai tersimpan agar tombol Cancel bisa mengembalikannya.
        originalData = {
            nama: user.username || "",
            email: user.email || "",
            no_telepon: user.no_telepon || "",
            fotoSrc: fotoSrc
        };
    } catch (err) {
        cardhavenAlert('error', 'Server Error', "Failed to connect to the server.");
        console.error(err);
    }
}

async function handleSubmit(e) {
    e.preventDefault();

    // Klik pertama ("Change Detail") hanya membuka mode edit, belum menyimpan.
    if (!editMode) {
        setEditMode(true);
        return;
    }

    const nama = document.getElementById("nama").value.trim();
    const email = document.getElementById("email").value.trim();
    const no_telepon = document.getElementById("no_telepon").value.trim();
    const fotoInput = document.getElementById("fotoFile");

    clearAllAccountErrors();
    let valid = true;

    if (!nama) {
        showFieldError('nama', 'namaError', "Name cannot be empty");
        valid = false;
    }

    if (!email) {
        showFieldError('email', 'emailError', "Email cannot be empty");
        valid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showFieldError('email', 'emailError', "Invalid email format");
        valid = false;
    }

    const rawPhone = no_telepon.replace(/[\-\s]/g, "");
    if (!no_telepon) {
        showFieldError('no_telepon', 'noTeleponError', "Phone number cannot be empty");
        valid = false;
    } else if (!/^\+?[0-9]{9,15}$/.test(rawPhone) || no_telepon.length > 20) {
        showFieldError('no_telepon', 'noTeleponError', "Invalid phone number! Please enter 9-15 valid digits.");
        valid = false;
    }

    if (fotoInput.files.length > 0 && fotoInput.files[0].size > 2 * 1024 * 1024) {
        showFieldError('fotoFile', 'fotoError', "Profile picture size must be less than 2MB!");
        valid = false;
    }

    if (!valid) return;

    try {
        // id_pengguna tidak dikirim — server memakai id dari session.
        const formData = new FormData();
        formData.append("action", "update");
        formData.append("nama", nama);
        formData.append("email", email);
        formData.append("no_telepon", no_telepon);
        
        if (fotoInput.files.length > 0) {
            formData.append("fotoFile", fotoInput.files[0]);
        }

        const res = await fetch(controllerUrl, { method: "POST", body: formData });
        const data = await res.json();

        if (data.status === "success") {
            // Nama baru sudah disimpan server ke session; cukup reload halaman.
            cardhavenAlert('success', 'Success', data.message, () => location.reload());
        } else if (/email/i.test(data.message || '')) {
            // Error validasi email dari server (mis. "Email already exists") tampil inline.
            showFieldError('email', 'emailError', data.message);
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
            // id_pengguna tidak dikirim — server memakai id dari session.
            const formData = new FormData();
            formData.append("action", "deactivate");

            const res = await fetch(controllerUrl, { method: "POST", body: formData });
            const data = await res.json();

            if (data.status === "success") {
                // Session sudah dihapus server saat akun dinonaktifkan.
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
            // id_pengguna tidak dikirim — server memakai id dari session.
            const formData = new FormData();
            formData.append("action", "delete");

            const res = await fetch(controllerUrl, { method: "POST", body: formData });
            const data = await res.json();

            if (data.status === "success") {
                // Session sudah dihapus server saat akun dihapus.
                cardhavenAlert('success', 'Deleted', data.message, () => {
                    window.location.href = "/CardHaven/login";
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