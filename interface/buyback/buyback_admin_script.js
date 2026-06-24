const BUYBACK_CONTROLLER = '/cardhaven/interface/buyback/controller_buyback.php';

// Mengambil dari sessionStorage atau localStorage
const idPengguna = sessionStorage.getItem('id_pengguna') || localStorage.getItem('id_pengguna');
const userRole = sessionStorage.getItem('role') || localStorage.getItem('role');

if (!idPengguna || userRole != '2') {
    window.location.href = '../login-page/index.php';
}

function loadDaftar() {
    fetch(`${BUYBACK_CONTROLLER}?action=get_buyback_list&role=2&id_pengguna=${idPengguna}`)
    .then(res => res.json())
    .then(data => {
        const tbody = document.querySelector('#tableAdmin tbody');
        tbody.innerHTML = '';
        data.data.forEach(row => {
            let btnAksi = `<button class="btn-view-icon" onclick="openDetailModal(${row.id_pembelian})" style="margin: 0 auto;">...</button>`;
            
            let tr = `<tr>
                <td>#${row.id_pembelian}</td>
                <td>${row.username}</td>
                <td>${row.tanggal_pembelian}</td>
                <td>Rp ${parseInt(row.total_harga).toLocaleString('id-ID')}</td>
                <td>${parseStatus(row.status_pembelian)}</td>
                <td class="btn-action-group">${btnAksi}</td>
            </tr>`;
            tbody.innerHTML += tr;
        });
    });
}

function viewDetail(id_pembelian, status) {
    if(status == 0) { 
        updateStatus(id_pembelian, 1, "Reviewing submission...");
    } else if (status == 1) {
        cardhavenConfirm("Price Review", "Is the price approved?", "Approve", 
            () => updateStatus(id_pembelian, 3, "Offer Accepted"), 
            () => negosiasiHarga(id_pembelian)
        );
    } else if (status == 4) { 
        cardhavenConfirm("Receive Package", "Confirm package has arrived at the shop?", "Yes, Receive", 
            () => updateStatus(id_pembelian, 5, "Card Received")
        );
    } else if (status == 5) { 
        cardhavenConfirm("Quality Inspection", "Is the card's physical condition as described?", "Match", 
            () => updateStatus(id_pembelian, 6, "Quality Checked"),
            () => updateStatus(id_pembelian, 9, "Rejected")
        );
    } else if (status == 6) { 
        updateStatus(id_pembelian, 7, "Payment Sent");
    } else if (status == 7) { 
        updateStatus(id_pembelian, 8, "Completed");
    }
}

function negosiasiHarga(id_pembelian) {
    closeDetailModal();
    Swal.fire({
        title: 'Price Negotiation',
        input: 'number',
        inputPlaceholder: 'Enter admin price counter-offer',
        showCancelButton: true,
        confirmButtonText: 'Submit Counter-Offer',
        customClass: { confirmButton: "btn-confirm", cancelButton: "btn-cancel-outline" }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            const formData = new URLSearchParams();
            formData.append('action', 'admin_negotiate');
            formData.append('id_pembelian', id_pembelian);
            formData.append('penawaran_admin', result.value);
            formData.append('id_pengguna', idPengguna);

            fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
            .then(() => loadDaftar());
        }
    });
}

function updateStatus(id_pembelian, statusBaru, message) {
    closeDetailModal();
    const formData = new URLSearchParams();
    formData.append('action', 'update_status');
    formData.append('id_pembelian', id_pembelian);
    formData.append('status', statusBaru);
    formData.append('id_pengguna', idPengguna);

    fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
    .then(() => {
        Swal.fire({icon: 'success', title: 'Success', text: message, timer: 1500, showConfirmButton: false});
        loadDaftar();
    });
}

function parseStatus(status) {
    const statuses = ["Pending Submission", "Under Review", "Price Negotiation", "Offer Accepted", "Card Shipped", "Card Received", "Quality Checked", "Payment Sent", "Completed", "Rejected", "Cancelled"];
    return `<span style="font-weight: bold; color: var(--primary-color)">${statuses[status] || "Unknown"}</span>`;
}

document.addEventListener('DOMContentLoaded', loadDaftar);
function openDetailModal(id_pembelian) {
    fetch(`${BUYBACK_CONTROLLER}?action=get_detail&id_pembelian=${id_pembelian}`)
    .then(res => res.json())
    .then(res => {
        if(res.status === 'success') {
            const data = res.data;
            const pem = data.pembelian;
            
            document.getElementById('modalTxId').innerText = `#${pem.id_pembelian}`;
            document.getElementById('modalStatus').innerHTML = parseStatus(pem.status_pembelian);
            
            let htmlContent = `
                <div style="background: #E1EBFF; padding: 10px 15px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9rem;">
                    <strong>Customer:</strong> ${pem.username}<br>
                    <strong>Receipt:</strong> ${pem.no_resi || '-'}
                </div>
            `;
            
            data.kartu.forEach(k => {
                htmlContent += `
                <div style="border: 1px solid #ddd; border-radius: 12px; padding: 15px; margin-bottom: 15px; background: #fff;">
                    <h3 style="margin-top: 0; color: var(--primary-color);">${k.nama_kartu}</h3>
                    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                        <a href="../../${k.foto_depan}" target="_blank" style="width: 48%;"><img src="../../${k.foto_depan}" style="width: 100%; border-radius: 8px;"></a>
                        <a href="../../${k.foto_belakang}" target="_blank" style="width: 48%;"><img src="../../${k.foto_belakang}" style="width: 100%; border-radius: 8px;"></a>
                    </div>
                    <div style="font-size: 0.9rem;">
                        <p style="margin: 4px 0;"><strong>Customer Ask:</strong> Rp ${parseInt(k.penawaran_customer).toLocaleString('id-ID')}</p>
                        <p style="margin: 4px 0; color: #E74C3C;"><strong>Admin Offer:</strong> ${k.penawaran_admin ? 'Rp ' + parseInt(k.penawaran_admin).toLocaleString('id-ID') : 'Pending'}</p>
                        <p style="margin: 4px 0; font-weight: 600; color: #E67E22;"><strong>Customer Attempts:</strong> ${k.percobaan_penawaran} / 3</p>
                    </div>
                </div>`;
            });
            document.getElementById('modalContent').innerHTML = htmlContent;

            let footerHtml = '';
            const status = pem.status_pembelian;
            
            if (status == 0) {
                footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px;" onclick="updateStatus(${pem.id_pembelian}, 1, 'Reviewing started')">Start Review</button>`;
                footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px; background: #E74C3C;" onclick="updateStatus(${pem.id_pembelian}, 9, 'Rejected')">Reject</button>`;
            } else if (status == 1) {
                footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px; background: #27AE60;" onclick="updateStatus(${pem.id_pembelian}, 3, 'Offer Accepted')">Approve Price</button>`;
                footerHtml += `<button class="btn-cancel-outline" style="width: auto; padding: 10px 20px;" onclick="negosiasiHarga(${pem.id_pembelian})">Counter Offer</button>`;
                footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px; background: #E74C3C;" onclick="updateStatus(${pem.id_pembelian}, 9, 'Rejected')">Reject</button>`;
            } else if (status == 4) {
                footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px; background: #27AE60;" onclick="updateStatus(${pem.id_pembelian}, 5, 'Received')">Receive Package</button>`;
            } else if (status == 5) {
                footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px;" onclick="updateStatus(${pem.id_pembelian}, 6, 'Verified')">Verify Quality</button>`;
                footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px; background: #E74C3C;" onclick="updateStatus(${pem.id_pembelian}, 9, 'Quality Failed & Rejected')">Reject</button>`;
            } else if (status == 6) {
                // GANTI TOMBOL SEND PAYMENT MENJADI INI:
                footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px;" onclick="uploadPayment(${pem.id_pembelian})">Upload Payment Proof</button>`;
            } 
            // HAPUS blok "else if (status == 7)" karena Admin tidak lagi mengklik Complete

            document.getElementById('modalFooter').innerHTML = footerHtml;
            document.getElementById('detailModal').style.display = 'flex';
        }
    });
}

function closeDetailModal() {
    document.getElementById('detailModal').style.display = 'none';
}

function uploadPayment(id_pembelian) {
    closeDetailModal();
    Swal.fire({
        title: 'Upload Payment Proof',
        input: 'file',
        inputAttributes: {
            'accept': 'image/*',
            'aria-label': 'Upload payment proof'
        },
        showCancelButton: true,
        confirmButtonText: 'Send Payment',
        customClass: { confirmButton: "btn-confirm", cancelButton: "btn-cancel-outline" }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            const formData = new FormData();
            formData.append('action', 'admin_send_payment');
            formData.append('id_pembelian', id_pembelian);
            formData.append('id_pengguna', idPengguna);
            formData.append('bukti_pembayaran', result.value); // result.value berisi objek File gambar

            fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                if(res.status === 'success') {
                    Swal.fire('Success', res.message, 'success');
                    loadDaftar();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            });
        } else if (result.isDismissed) {
            document.getElementById('detailModal').style.display = 'flex';
        }
    });
}