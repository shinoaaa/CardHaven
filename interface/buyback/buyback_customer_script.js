const BUYBACK_CONTROLLER = '/cardhaven/interface/buyback/controller_buyback.php';

// Mengambil dari sessionStorage atau localStorage
const idPengguna = sessionStorage.getItem('id_pengguna') || localStorage.getItem('id_pengguna');
const userRole = sessionStorage.getItem('role') || localStorage.getItem('role');
let cardIndexCounter = 1;

function openSubmitModal() {
    document.getElementById('submitModal').style.display = 'flex';
}

function closeSubmitModal() {
    document.getElementById('submitModal').style.display = 'none';
    document.getElementById('formBuyback').reset();
    resetCardFields();
}

function addCardField() {
    cardIndexCounter++;
    const container = document.getElementById('cardInputsContainer');
    const html = `
        <div class="card-input-group" id="cardGroup${cardIndexCounter}" style="border: 2px solid #E1EBFF; padding: 20px; border-radius: 12px; margin-bottom: 15px; background: #fafcff; position: relative;">
            <button type="button" onclick="removeCardField(${cardIndexCounter})" style="position: absolute; right: 15px; top: 15px; background: none; border: none; color: #E74C3C; cursor: pointer; font-weight: bold; font-size: 0.9rem;">&times; Remove</button>
            <h4 style="margin-top: 0; margin-bottom: 15px; color: var(--primary-color); font-size: 1.1rem;">Card ${cardIndexCounter}</h4>
            <div class="form-group">
                <label>Card Name <span class="required">*</span></label>
                <input type="text" name="nama_kartu[]" required placeholder="e.g., Charizard Base Set">
            </div>
            <div class="form-group">
                <label>Your Offer Price (Rp) <span class="required">*</span></label>
                <input type="number" name="harga_beli[]" required placeholder="e.g., 500000">
            </div>
            <div class="form-group">
                <label>Front Photo <span class="required">*</span></label>
                <input type="file" name="foto_depan[]" class="file-input-custom" accept="image/*" required>
            </div>
            <div class="form-group">
                <label>Back Photo <span class="required">*</span></label>
                <input type="file" name="foto_belakang[]" class="file-input-custom" accept="image/*" required>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
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
        data.data.forEach(row => {
            // Deklarasi tombol aksi secara langsung TANPA dibungkus if(status == ...)
            let aksi = `<button class="btn-view-icon" onclick="openDetailModal(${row.id_pembelian})" style="margin: 0 auto;">...</button>`;
            
            let tr = `<tr>
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
    
    // Validasi HTML bawaan (pastikan field required terisi)
    if(!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    formData.append('action', 'submit_buyback');
    formData.append('id_pengguna', idPengguna);

    fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => {
        if(res.status === 'success') {
            closeSubmitModal();
            cardhavenAlert('success', 'Success', res.message, () => {
                loadRiwayat();
            });
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
    fetch(`${BUYBACK_CONTROLLER}?action=get_detail&id_pembelian=${id_pembelian}`)
    .then(res => res.json())
    .then(res => {
        if(res.status === 'success') {
            const data = res.data;
            const pem = data.pembelian;
            
            document.getElementById('modalTxId').innerText = `#${pem.id_pembelian}`;
            document.getElementById('modalStatus').innerHTML = parseStatus(pem.status_pembelian);
            
            let htmlContent = '';
            data.kartu.forEach(k => {
                // Fallback untuk mencegah Rp NaN pada data lama
                const initialAsk = k.harga_beli ? parseInt(k.harga_beli) : parseInt(k.penawaran_customer);
                
                htmlContent += `
                <div style="border: 1px solid #ddd; border-radius: 12px; padding: 15px; margin-bottom: 15px; background: #fafcff;">
                    <h3 style="margin-top: 0; color: var(--primary-color);">${k.nama_kartu}</h3>
                    <div style="font-size: 0.9rem;">
                        <p style="margin: 4px 0;"><strong>Initial Ask:</strong> Rp ${initialAsk.toLocaleString('id-ID')}</p>
                        <p style="margin: 4px 0;"><strong>Your Last Offer:</strong> Rp ${parseInt(k.penawaran_customer).toLocaleString('id-ID')}</p>
                        <p style="margin: 4px 0; color: #E74C3C;"><strong>Admin Offer:</strong> ${k.penawaran_admin ? 'Rp ' + parseInt(k.penawaran_admin).toLocaleString('id-ID') : 'Pending'}</p>
                        <p style="margin: 4px 0; font-weight: 600; color: #E67E22;"><strong>Negotiation Attempts:</strong> ${k.percobaan_penawaran} / 3</p>
                    </div>
                </div>`;
            });
            
            
            document.getElementById('modalContent').innerHTML = htmlContent;
            if (pem.bukti_pembayaran) {
                document.getElementById('modalContent').innerHTML += `
                <div style="background: #E1EBFF; padding: 15px; border-radius: 8px; margin-bottom: 15px; text-align: center;">
                    <h4 style="margin: 0 0 10px 0; color: var(--primary-color);">Payment Proof</h4>
                    <a href="../../${pem.bukti_pembayaran}" target="_blank">
                        <img src="../../${pem.bukti_pembayaran}" style="max-width: 100%; max-height: 250px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    </a>
                </div>`;
            }
            let footerHtml = '';
            
            // Logika tombol utama Customer
            if (pem.status_pembelian == 2) {
                const attempts = data.kartu[0].percobaan_penawaran;
                footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px; background: #27AE60;" onclick="acceptOffer(${pem.id_pembelian})">Accept Offer</button>`;
                
                if (attempts < 3) {
                    footerHtml += `<button class="btn-cancel-outline" style="width: auto; padding: 10px 20px;" onclick="counterOffer(${pem.id_pembelian})">Counter Offer</button>`;
                } else {
                    footerHtml += `<span style="color: #E74C3C; font-weight: bold; align-self: center; margin-right: 10px;">Max attempts reached.</span>`;
                }
            } else if (pem.status_pembelian == 3) {
                footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px; background: #27AE60;" onclick="inputResi(${pem.id_pembelian})">Input Receipt</button>`;
            }else if (pem.status_pembelian == 7) {
                footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px; background: #0088FF;" onclick="completeTransaction(${pem.id_pembelian})">Confirm Payment Received</button>`;
            }

            // Opsi Cancel untuk Customer selama belum masuk pengiriman/selesai (Status 0, 1, atau 2)
            if (pem.status_pembelian <= 2) {
                footerHtml += `<button class="btn-cancel-outline" style="width: auto; padding: 10px 20px; border-color: #E74C3C; color: #E74C3C;" onclick="cancelBuyback(${pem.id_pembelian})">Cancel</button>`;
            }

            document.getElementById('modalFooter').innerHTML = footerHtml;
            document.getElementById('detailModal').style.display = 'flex';
        }
    });
}

function closeDetailModal() {
    document.getElementById('detailModal').style.display = 'none';
}

function counterOffer(id_pembelian) {
    closeDetailModal();
    Swal.fire({
        title: 'Counter Offer',
        input: 'number',
        inputPlaceholder: 'Enter your new price',
        showCancelButton: true,
        confirmButtonText: 'Submit Offer',
        customClass: { confirmButton: "btn-confirm", cancelButton: "btn-cancel-outline" }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            const formData = new URLSearchParams();
            formData.append('action', 'customer_negotiate');
            formData.append('id_pembelian', id_pembelian);
            formData.append('penawaran_customer', result.value);

            fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
            .then(() => loadRiwayat());
        }
    });
}

function acceptOffer(id_pembelian) {
    closeDetailModal();
    const formData = new URLSearchParams();
    formData.append('action', 'update_status');
    formData.append('id_pembelian', id_pembelian);
    formData.append('status', 3);
    formData.append('id_pengguna', idPengguna);

    fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
    .then(() => loadRiwayat());
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