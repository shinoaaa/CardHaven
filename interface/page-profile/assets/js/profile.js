const userId = CardHavenAuth.id() || null;

document.addEventListener('DOMContentLoaded', () => {
    if (!userId) {
        Swal.fire("Session Expired", "Please login first", "error").then(() => {
            window.location.href = "/CardHaven/login";
        });
        return;
    }

    fetchProfileData();

    // Preview foto sebelum upload
    document.getElementById('editFoto').addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                document.getElementById('editFotoPreview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
});

function fetchProfileData() {
    fetch(`/cardhaven/interface/page-profile/controller/ProfileController.php?action=getProfile`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const user = data.data;

                document.getElementById('displayUsername').innerText = user.username;
                document.getElementById('displayEmail').innerText = user.email;
                document.getElementById('displayPhone').innerText = user.no_telepon || '-';
                document.getElementById('displayJoinDate').innerText = user.created_date;
                document.getElementById('displayCartCount').innerText = user.cart_count;
                document.getElementById('displayExpenditure').innerText = 'Rp. ' + parseInt(user.total_expenditure).toLocaleString('id-ID');

                // ✅ Update avatar profile card
                
                const avatarSrc = user.foto_profil
                    ? `/cardhaven/assets/image/image-profile/${user.foto_profil}`   // DB simpan nama file saja
                    : '/cardhaven/assets/image/user.svg'; // fallback default
                
                document.getElementById('displayAvatar').src = avatarSrc;

                // Binding modal
                document.getElementById('editCustomerId').value = userId;
                document.getElementById('editUsername').value = user.username;
                document.getElementById('editEmail').value = user.email;
                document.getElementById('editNoTelp').value = user.no_telepon || '';
                document.getElementById('editFotoPreview').src = avatarSrc; // preview di modal juga
            }
        }).catch(err => console.error("Error UI Data: ", err));
}

function openEditProfile() {
    document.getElementById('modalOverlay').style.display = 'block';
    document.getElementById('modalCustomerEdit').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('modalOverlay').style.display = 'none';
    document.getElementById('modalCustomerEdit').style.display = 'none';

    // Reset file input supaya preview tidak nyangkut
    document.getElementById('editFoto').value = '';
}

// Nomor telepon valid: hanya angka/spasi/+ - ( ), diawali angka atau '+',
// dan minimal 8 digit. Menolak input yang hanya karakter unik/spesial.
function isValidPhone(phone) {
    const digits = (phone.match(/\d/g) || []).length;
    return /^\+?[0-9][0-9\s\-()]*$/.test(phone) && digits >= 8 && digits <= 15;
}

function submitEditCustomer() {
    const phone = (document.getElementById('editNoTelp')?.value || '').trim();
    if (phone && !isValidPhone(phone)) {
        Swal.fire('Invalid Phone', 'Invalid phone number. Use digits only (8–15 digits).', 'error');
        return;
    }

    cardhavenConfirm(
        "Update Account",
        "Are you sure you want to update your profile data?",
        "Yes, Update",
        () => {
            const formData = new FormData(document.getElementById('customerEditForm'));

            fetch('/cardhaven/interface/page-profile/controller/ProfileController.php?action=updateProfile', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    Swal.fire('Success', 'Your profile updated successfully!', 'success');
                    closeEditModal();
                    fetchProfileData(); // Re-render UI + avatar
                } else {
                    Swal.fire('Error', res.msg, 'error');
                }
            })
            .catch(err => {
                console.error("Error update profile:", err);
                Swal.fire('Error', 'Something went wrong.', 'error');
            });
        }
    );
}