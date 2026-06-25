const BUYBACK_CONTROLLER = '/cardhaven/interface/buyback/controller_buyback.php';

const idPengguna = sessionStorage.getItem('id_pengguna') || localStorage.getItem('id_pengguna');
const userRole = sessionStorage.getItem('role') || localStorage.getItem('role');
let cardIndexCounter = 1;

if (!idPengguna || userRole != '0') {
    window.location.href = '../login-page/index.php';
}

function fetchBankDetails() {
    fetch(`${BUYBACK_CONTROLLER}?action=get_user_bank&id_pengguna=${idPengguna}`)
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success' && res.data) {
            document.getElementById('bankProvider').value = res.data.provider || '';
            document.getElementById('bankNoRek').value = res.data.no_rekening || '';
        }
    });
}

// Fungsi untuk me-render gambar ke tag <img> saat user memilih file
function previewImage(input, imgId) {
    const imgElement = document.getElementById(imgId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imgElement.src = e.target.result;
            imgElement.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        imgElement.src = '';
        imgElement.style.display = 'none';
    }
}

function resetForm() {
    const form = document.getElementById('formBuyback');
    if (form) {
        form.reset();
        form.querySelectorAll('.error-message').forEach(el => el.innerText = '');
        form.querySelectorAll('.modal-input').forEach(el => el.style.borderColor = '#ccc');
        // Sembunyikan semua preview gambar
        form.querySelectorAll('img[id^="preview"]').forEach(img => {
            img.src = '';
            img.style.display = 'none';
        });
    }
    resetCardFields();
    fetchBankDetails(); // Kembalikan data rekening bawaan
}

function addCardField() {
    cardIndexCounter++;
    const container = document.getElementById('cardInputsContainer');
    const html = `
        <div class="card-input-group" id="cardGroup${cardIndexCounter}" style="border: 2px solid #E1EBFF; padding: 20px; border-radius: 12px; margin-bottom: 15px; background: #fafcff; position: relative;">
            <button type="button" onclick="removeCardField(${cardIndexCounter})" style="position: absolute; right: 15px; top: 15px; background: none; border: none; color: #E74C3C; cursor: pointer; font-weight: bold; font-size: 0.9rem;">&times; Remove</button>
            <h4 style="margin-top: 0; margin-bottom: 15px; color: var(--primary-color); font-size: 1.1rem;">Card ${cardIndexCounter}</h4>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Card Name <span class="required">*</span></label>
                    <input type="text" name="nama_kartu[]" class="modal-input" placeholder="e.g., Charizard Base Set">
                    <div class="error-message" style="color: #E74C3C; font-size: 0.75rem; margin-top: 4px;"></div>
                </div>
                <div class="form-group">
                    <label>Your Offer Price (Rp) <span class="required">*</span></label>
                    <input type="number" name="harga_beli[]" class="modal-input" placeholder="e.g., 500000">
                    <div class="error-message" style="color: #E74C3C; font-size: 0.75rem; margin-top: 4px;"></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                <div class="form-group">
                    <label>Front Photo <span class="required">*</span></label>
                    <input type="file" name="foto_depan[]" class="file-input-custom modal-input" accept="image/*" onchange="previewImage(this, 'previewFront${cardIndexCounter}')">
                    <div class="error-message" style="color: #E74C3C; font-size: 0.75rem; margin-top: 4px;"></div>
                    <img id="previewFront${cardIndexCounter}" src="" style="max-width: 100%; max-height: 200px; display: none; margin-top: 10px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                </div>
                <div class="form-group">
                    <label>Back Photo <span class="required">*</span></label>
                    <input type="file" name="foto_belakang[]" class="file-input-custom modal-input" accept="image/*" onchange="previewImage(this, 'previewBack${cardIndexCounter}')">
                    <div class="error-message" style="color: #E74C3C; font-size: 0.75rem; margin-top: 4px;"></div>
                    <img id="previewBack${cardIndexCounter}" src="" style="max-width: 100%; max-height: 200px; display: none; margin-top: 10px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
}

function closeSubmitModal() {
    document.getElementById('submitModal').style.display = 'none';
    document.getElementById('formBuyback').reset();
    resetCardFields();
}


function removeCardField(id) {
    document.getElementById(`cardGroup${id}`).remove();
}

function resetCardFields() {
    const container = document.getElementById('cardInputsContainer');
    // Sisakan hanya elemen pertama, hapus sisanya
    while (container.children.length > 1) {
        container.removeChild(container.lastChild);
    }
    cardIndexCounter = 1;
}

if (!idPengguna || userRole != '0') {
    window.location.href = '../login-page/index.php';
}
function loadRiwayat() {
    fetch(`${BUYBACK_CONTROLLER}?action=get_buyback_list&role=0&id_pengguna=${idPengguna}`)
    .then(res => res.json())
    .then(data => {
        const tbody = document.querySelector('#tableRiwayat tbody');
        tbody.innerHTML = '';
        data.data.forEach((row, index) => { // Tambahkan index
            let aksi = `<button class="btn-view-icon" onclick="openDetailModal(${row.id_pembelian})" style="margin: 0 auto;">...</button>`;
            let tr = `<tr>
                <td>${index + 1}</td>
                <td>#${row.id_pembelian}</td>
                <td>${row.tanggal_pembelian}</td>
                <td>Rp ${parseInt(row.total_harga).toLocaleString('id-ID')}</td>
                <td>${parseStatus(row.status_pembelian)}</td>
                <td class="btn-action-group">${aksi}</td>
            </tr>`;
            tbody.innerHTML += tr;
        });
    });
}
function submitBuyback() {
    const form = document.getElementById('formBuyback');
    let isValid = true;

    // Bersihkan error sebelumnya
    document.querySelectorAll('#formBuyback .error-message').forEach(el => el.innerText = '');
    document.querySelectorAll('#formBuyback .modal-input').forEach(el => el.style.borderColor = '#ccc');

    // Fungsi trigger error ala Master Game
    const showError = (element, message) => {
        element.style.borderColor = '#E74C3C';
        const errorDiv = element.nextElementSibling;
        if (errorDiv && errorDiv.classList.contains('error-message')) {
            errorDiv.innerText = message;
        }
        isValid = false;
    };

    // Validasi Rekening Bank
    const provider = document.getElementById('bankProvider');
    const noRek = document.getElementById('bankNoRek');
    if (!provider.value.trim()) showError(provider, "Provider Bank/E-Wallet wajib diisi.");
    if (!noRek.value.trim()) showError(noRek, "Nomor Rekening wajib diisi.");

    // Validasi Per Kartu
    const cardNames = document.getElementsByName('nama_kartu[]');
    const cardPrices = document.getElementsByName('harga_beli[]');
    const cardFronts = document.getElementsByName('foto_depan[]');
    const cardBacks = document.getElementsByName('foto_belakang[]');

    for (let i = 0; i < cardNames.length; i++) {
        if (!cardNames[i].value.trim()) showError(cardNames[i], "Please enter the card name!");
        if (!cardPrices[i].value) showError(cardPrices[i], "Please enter the price!");
        if (cardPrices[i].value <= 0) showError(cardPrices[i], "Please enter a valid price!");
        if (cardFronts[i].files.length === 0) showError(cardFronts[i], "Please upload the front side photo of the card!");
        if (cardBacks[i].files.length === 0) showError(cardBacks[i], "Please upload the back side photo of the card!");
    }

    if (!isValid) return; // Hentikan proses jika ada error

    const formData = new FormData(form);
    formData.append('action', 'submit_buyback');
    formData.append('id_pengguna', idPengguna);

    fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            resetForm();
            cardhavenAlert('success', 'Success', res.message, () => loadRiwayat());
        } else {
            cardhavenAlert('error', 'Failed', res.message);
        }
    });
}

function inputResi(id_pembelian) {
    closeDetailModal();
    Swal.fire({
        title: 'Input Receipt Number',
        input: 'text',
        inputPlaceholder: 'Enter shipping tracking number',
        showCancelButton: true,
        confirmButtonText: 'Submit',
        customClass: { confirmButton: "btn-confirm", cancelButton: "btn-cancel-outline" }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            const formData = new URLSearchParams();
            formData.append('action', 'update_status');
            formData.append('id_pembelian', id_pembelian);
            formData.append('status', 4);
            formData.append('no_resi', result.value);
            formData.append('id_pengguna', idPengguna);

            fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
            .then(() => loadRiwayat());
        }
    });
}

function parseStatus(status) {
    const statuses = ["Pending Submission", "Under Review", "Price Negotiation", "Offer Accepted", "Card Shipped", "Card Received", "Quality Checked", "Payment Sent", "Completed", "Rejected", "Cancelled"];
    return statuses[status] || "Unknown";
}

document.addEventListener('DOMContentLoaded', loadRiwayat);
function openDetailModal(id_pembelian) {
    fetch(`${BUYBACK_CONTROLLER}?action=get_detail&id_pembelian=${id_pembelian}${BUYBACK_CONTROLLER}?action=get_detail&id_pembelian=${id_pembelian}&role=${userRole}&id_pengguna=${idPengguna}`)
    .then(res => res.json())
    .then(res => {
        if(res.status === 'success') {
            const data = res.data;
            const pem = data.pembelian;
            document.getElementById('modalTxId').innerText = `#${pem.id_pembelian}`;
            document.getElementById('modalStatus').innerHTML = parseStatus(pem.status_pembelian);
            
            let htmlContent = '';
            let allCardsAccepted = true;

            data.kartu.forEach(k => {
                const isNegotiating = (pem.status_pembelian == 2 && k.penawaran_admin != null && k.penawaran_customer != k.penawaran_admin);
                if (k.penawaran_customer != k.penawaran_admin) allCardsAccepted = false;

                htmlContent += `
                <div style="border: 1px solid #ddd; border-radius: 12px; padding: 15px; margin-bottom: 15px; background: #fff;">
                    <h3 style="margin:0 0 10px 0; color: var(--primary-color);">${k.nama_kartu}</h3>
                    <div style="font-size: 0.9rem; margin-bottom: 12px;">
                        <p style="margin: 4px 0;"><strong>Your Ask:</strong> Rp ${parseInt(k.penawaran_customer).toLocaleString('id-ID')}</p>
                        <p style="margin: 4px 0; color: #E74C3C;"><strong>Admin Offer:</strong> ${k.penawaran_admin ? 'Rp ' + parseInt(k.penawaran_admin).toLocaleString('id-ID') : 'Waiting...'}</p>
                        <p style="margin: 4px 0;"><strong>Attempts:</strong> <span style="color: #E67E22; font-weight: 600;">${k.percobaan_penawaran} / 3</span></p>
                    </div>`;
                
                // Tampilkan tombol negosiasi seragam PER KARTU
                if (isNegotiating) {
                    htmlContent += `
                    <div style="display: flex; gap: 8px;">
                        <button onclick="acceptItemOffer(${pem.id_pembelian}, ${k.id_kartu}, ${k.penawaran_admin})" class="btn-confirm" style="width: auto; height: 32px; font-size: 0.8rem; padding: 0 15px; margin: 0; background: #27AE60;">Accept Price</button>
                        ${k.percobaan_penawaran < 3 ? `<button onclick="counterItemOffer(${pem.id_pembelian}, ${k.id_kartu})" class="btn-cancel-outline" style="width: auto; height: 32px; font-size: 0.8rem; padding: 0 15px; margin: 0; border-width: 1.5px;">Counter Offer</button>` : '<span style="color:#E74C3C; font-weight:bold; font-size:0.8rem; display:flex; align-items:center;">Max Attempts Reached</span>'}
                    </div>`;
                } else if (k.penawaran_admin && k.penawaran_customer == k.penawaran_admin) {
                    htmlContent += `<p style="color: #27AE60; font-weight: bold; margin: 0;">✓ Price Agreed</p>`;
                }

                htmlContent += `</div>`;
            });

            // Tampilkan foto bukti bayar jika ada (Status 7)
            if (pem.bukti_pembayaran) {
                htmlContent += `
                <div style="background: #E1EBFF; padding: 15px; border-radius: 8px; margin-bottom: 15px; text-align: center;">
                    <h4 style="margin: 0 0 10px 0; color: var(--primary-color);">Payment Proof</h4>
                    <a href="../../${pem.bukti_pembayaran}" target="_blank">
                        <img src="../../${pem.bukti_pembayaran}" style="max-width: 100%; max-height: 250px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    </a>
                </div>`;
            }

            document.getElementById('modalContent').innerHTML = htmlContent;

            let footerHtml = '';
            
            // Jika status Negotiation (2), customer harus submit setelah memilih accept/counter per kartu
            if (pem.status_pembelian == 2) {
                if (allCardsAccepted) {
                    footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px; background: #27AE60;" onclick="updateStatus(${pem.id_pembelian}, 3, 'All prices agreed!')">Proceed to Shipping</button>`;
                } else {
                    footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px;" onclick="updateStatus(${pem.id_pembelian}, 1, 'Counters sent to Admin')">Submit Counter Offers</button>`;
                }
            } else if (pem.status_pembelian == 3) {
                footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px; background: #27AE60;" onclick="inputResi(${pem.id_pembelian})">Input Receipt</button>`;
            } else if (pem.status_pembelian == 7) {
                footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px; background: #0088FF;" onclick="completeTransaction(${pem.id_pembelian})">Confirm Payment Received</button>`;
            } else if (pem.status_pembelian == 9) {
                // Tampilkan opsi input alamat retur JIKA admin menolak di tahap Quality Check
                if (pem.status_pembelian == 9) {
                    if (pem.alamat) {
                        footerHtml += `<span style="color: #E67E22; font-weight: bold;">Return Address Submitted. Please wait for shipping.</span>`;
                    } else {
                        footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px; background: #E67E22;" onclick="inputAddress(${pem.id_pembelian})">Provide Return Address</button>`;
                    }
                }
            }
            
            // Opsi Cancel untuk status awal
            if (pem.status_pembelian <= 2) {
                footerHtml += `<button class="btn-cancel-outline" style="width: auto; padding: 10px 20px; border-color: #E74C3C; color: #E74C3C; border-width: 2px;" onclick="cancelBuyback(${pem.id_pembelian})">Cancel Submission</button>`;
            }

            document.getElementById('modalFooter').innerHTML = footerHtml;
            document.getElementById('detailModal').style.display = 'flex';
        }
    });
}

function closeDetailModal() {
    document.getElementById('detailModal').style.display = 'none';
}
function counterItemOffer(idP, idK) {
    Swal.fire({
        title: 'Counter Offer for this Card',
        input: 'number',
        showCancelButton: true
    }).then(res => {
        if(res.isConfirmed && res.value) {
            const formData = new URLSearchParams();
            formData.append('action', 'customer_negotiate_item');
            formData.append('id_pembelian', idP); // Rujukan untuk verifikasi
            formData.append('id_kartu', idK);
            formData.append('penawaran_customer', res.value);
            formData.append('id_pengguna', idPengguna); // Kredensial otorisasi
            
            fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData }).then(() => openDetailModal(idP));
        }
    });
}
function acceptItemOffer(idP, idK, price) {
    const formData = new URLSearchParams();
    formData.append('action', 'customer_accept_item');
    formData.append('id_pembelian', idP); // Rujukan untuk verifikasi
    formData.append('id_kartu', idK);
    formData.append('harga_final', price);
    formData.append('id_pengguna', idPengguna); // Kredensial otorisasi
    
    fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData }).then(() => openDetailModal(idP));
}
function cancelBuyback(id_pembelian) {
    closeDetailModal();
    cardhavenConfirm("Cancel Transaction", "Are you sure you want to cancel this submission?", "Yes, Cancel", () => {
        const formData = new URLSearchParams();
        formData.append('action', 'update_status');
        formData.append('id_pembelian', id_pembelian);
        formData.append('status', 10); // Status 10 = Cancelled
        formData.append('id_pengguna', idPengguna);

        fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
        .then(() => loadRiwayat());
    }, () => {
        // Jika batal dibatalkan, buka kembali modal
        document.getElementById('detailModal').style.display = 'flex';
    });

}function completeTransaction(id_pembelian) {
    closeDetailModal();
    cardhavenConfirm("Confirm Receipt", "Have you verified that the money is in your bank account?", "Yes, Complete", () => {
        const formData = new URLSearchParams();
        formData.append('action', 'update_status');
        formData.append('id_pembelian', id_pembelian);
        formData.append('status', 8); // Status 8 = Completed
        formData.append('id_pengguna', idPengguna);

        fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
        .then(() => loadRiwayat());
    }, () => {
        document.getElementById('detailModal').style.display = 'flex';
    });
}

function inputAddress(id_pembelian) {
    closeDetailModal();
    Swal.fire({
        title: 'Input Return Address',
        input: 'textarea',
        inputPlaceholder: 'Enter your full address for card return shipment...',
        showCancelButton: true,
        confirmButtonText: 'Submit Address',
        customClass: { confirmButton: "btn-confirm", cancelButton: "btn-cancel-outline" }
    }).then(res => {
        if(res.isConfirmed && res.value) {
            const formData = new URLSearchParams();
            formData.append('action', 'update_address');
            formData.append('id_pembelian', id_pembelian);
            formData.append('alamat_retur', res.value);
            fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(result => {
                if(result.status === 'success') {
                    Swal.fire('Success', result.message, 'success').then(() => {
                        openDetailModal(id_pembelian); // Refresh modal agar muncul pesannya
                    });
                }
            });
        } else {
            document.getElementById('detailModal').style.display = 'flex';
        }
    });
}

function inputAddress(id_pembelian) {
    closeDetailModal();
    Swal.fire({
        title: 'Input Return Address',
        input: 'textarea',
        inputPlaceholder: 'Enter your full address for card return shipment...',
        showCancelButton: true,
        confirmButtonText: 'Submit Address',
        customClass: { confirmButton: "btn-confirm", cancelButton: "btn-cancel-outline" }
    }).then(res => {
        if(res.isConfirmed && res.value) {
            const formData = new URLSearchParams();
            formData.append('action', 'update_address');
            formData.append('id_pembelian', id_pembelian);
            formData.append('alamat_retur', res.value);
            formData.append('id_pengguna', idPengguna); // INI WAJIB DITAMBAHKAN UNTUK KEAMANAN

            fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(result => {
                if(result.status === 'success') {
                    Swal.fire('Success', result.message, 'success').then(() => {
                        openDetailModal(id_pembelian); // Refresh modal agar muncul pesannya
                    });
                } else {
                    Swal.fire('Error', result.message, 'error');
                }
            });
        } else {
            // Jika user batal input, kembalikan modal detail
            document.getElementById('detailModal').style.display = 'flex';
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadRiwayat();
    fetchBankDetails(); // Agar provider dan rekening muncul seketika saat halaman dimuat
});