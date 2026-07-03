// interface/page-profile/assets/js/buyback.js
// Riwayat Buyback + detail modal untuk halaman Profile (tab "Buy Back").
// Perilaku disamakan dengan interface/buyback/customer.php (tanpa form submit).
// Self-contained: tidak menyentuh file customer.php / buyback_customer_script.js.

const BUYBACK_CONTROLLER = '/cardhaven/interface/buyback/controller_buyback.php';
const idPengguna = sessionStorage.getItem('id_pengguna') || localStorage.getItem('id_pengguna');
const userRole   = sessionStorage.getItem('role') || localStorage.getItem('role') || '0';

function parseStatus(status) {
    const statuses = ["Pending Submission", "Under Review", "Price Negotiation", "Offer Accepted", "Card Shipped", "Card Received", "Quality Checked", "Payment Sent", "Completed", "Rejected", "Cancelled"];
    return statuses[status] || "Unknown";
}

function loadRiwayat() {
    const tbody = document.querySelector('#tableRiwayat tbody');
    if (!tbody || !idPengguna) return;

    fetch(`${BUYBACK_CONTROLLER}?action=get_buyback_list&role=0&id_pengguna=${idPengguna}`)
    .then(res => res.json())
    .then(data => {
        tbody.innerHTML = '';
        if (!data.data || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No BuyBack records yet.</td></tr>';
            return;
        }
        data.data.forEach((row, index) => {
            let tanggal = 'N/A';
            if (row.tanggal_pembelian) {
                const tglMentah = row.tanggal_pembelian.substring(0, 10);
                const [tahun, bulan, hari] = tglMentah.split('-');
                tanggal = `${hari}-${bulan}-${tahun}`;
            }
            const aksi = `<button class="btn-view-icon" onclick="openDetailModal(${row.id_pembelian})" style="margin: 0 auto;">...</button>`;
            tbody.innerHTML += `<tr>
                <td>${index + 1}</td>
                <td>#${row.id_pembelian}</td>
                <td>${tanggal}</td>
                <td>Rp ${parseInt(row.total_harga).toLocaleString('id-ID')}</td>
                <td>${parseStatus(row.status_pembelian)}</td>
                <td class="btn-action-group">${aksi}</td>
            </tr>`;
        });
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

function openDetailModal(id_pembelian) {
    fetch(`${BUYBACK_CONTROLLER}?action=get_detail&id_pembelian=${id_pembelian}&role=${userRole}&id_pengguna=${idPengguna}`)
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            const data = res.data;
            const pem = data.pembelian;
            document.getElementById('modalTxId').innerText = `#${pem.id_pembelian}`;
            document.getElementById('modalStatus').innerHTML = parseStatus(pem.status_pembelian);

            let htmlContent = '';

            // Status final: offer tidak relevan lagi
            const FINAL_STATUSES = [8, 9, 10];
            const isFinal = FINAL_STATUSES.includes(parseInt(pem.status_pembelian));
            const isNegotiating = pem.status_pembelian == 2;

            let allDecided = true;
            let hasCounter = false;

            data.kartu.forEach(k => {
                const adminHasOffer = k.penawaran_admin != null;
                const priceMatch    = adminHasOffer && (parseFloat(k.penawaran_admin) === parseFloat(k.penawaran_customer));
                const isPending     = isNegotiating && adminHasOffer && !priceMatch;
                const isAgreed      = adminHasOffer && priceMatch;

                let actualAttempts = Math.max(0, parseInt(k.percobaan_penawaran) - 1);
                const maxAttempts  = actualAttempts >= 3;

                if (isNegotiating) {
                    if (isPending) allDecided = false;
                }

                let adminOfferLabel;
                if (!adminHasOffer) {
                    adminOfferLabel = isFinal
                        ? `<span style="color:#9ca3af;">-</span>`
                        : `<span style="color:#9ca3af;">Waiting for admin...</span>`;
                } else if (isAgreed) {
                    adminOfferLabel = `<span style="color:#27AE60;font-weight:600;">Rp ${parseInt(k.penawaran_admin).toLocaleString('id-ID')}</span>`;
                } else {
                    adminOfferLabel = `<span style="color:#E74C3C;font-weight:600;">Rp ${parseInt(k.penawaran_admin).toLocaleString('id-ID')}</span>`;
                }

                const borderColor = isPending ? '#fbbf24' : '#e5e7eb';

                htmlContent += `
                <div style="border: 1.5px solid ${borderColor}; border-radius: 12px; padding: 15px; margin-bottom: 15px; background: #fff;">
                    <h3 style="margin:0 0 10px 0; color: var(--primary-color);">${k.nama_kartu}</h3>

                    <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                        <div style="flex: 1;">
                            <p style="margin: 0 0 5px 0; font-size: 0.8rem; color: #666;">Front Photo:</p>
                            <a href="/CardHaven/${k.foto_depan}" target="_blank">
                                <img src="/CardHaven/${k.foto_depan}" style="width: 100%; height: auto; object-fit: cover; border-radius: 6px; border: 1px solid #ccc;">
                            </a>
                        </div>
                        <div style="flex: 1;">
                            <p style="margin: 0 0 5px 0; font-size: 0.8rem; color: #666;">Back Photo:</p>
                            <a href="/CardHaven/${k.foto_belakang}" target="_blank">
                                <img src="/CardHaven/${k.foto_belakang}" style="width: 100%; height: auto; object-fit: cover; border-radius: 6px; border: 1px solid #ccc;">
                            </a>
                        </div>
                    </div>

                    <div style="font-size: 0.9rem; margin-bottom: 12px; padding-top: 10px; border-top: 1px dashed #e5e7eb;">
                        <p style="margin: 4px 0;"><strong>Your Ask:</strong> Rp ${parseInt(k.penawaran_customer).toLocaleString('id-ID')}</p>
                        <p style="margin: 4px 0;"><strong>Admin Offer:</strong> ${adminOfferLabel}</p>
                        <p style="margin: 4px 0;"><strong>Attempts:</strong> <span style="color:#E67E22;font-weight:600;">${actualAttempts} / 3</span></p>
                    </div>`;

                if (isPending) {
                    htmlContent += `<div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                        <button onclick="acceptItemOffer(${pem.id_pembelian}, ${k.id_kartu}, ${k.penawaran_admin})"
                            class="btn-confirm" style="width:auto; height:32px; font-size:0.8rem; padding:0 15px; margin:0; background:#27AE60;">
                            ✓ Accept
                        </button>
                        ${!maxAttempts
                            ? `<button onclick="counterItemOffer(${pem.id_pembelian}, ${k.id_kartu})"
                                class="btn-cancel-outline" style="width:auto; height:32px; font-size:0.8rem; padding:0 15px; margin:0; border-width:1.5px; color:#7c3aed; border-color:#7c3aed;">
                                ⟳ Counter Offer
                                </button>`
                            : `<span style="color:#E74C3C; font-weight:bold; font-size:0.8rem;">Max attempts reached. You can only Accept.</span>`
                        }
                    </div>`;
                } else if (isAgreed) {
                    htmlContent += `<p style="color:#27AE60; font-weight:bold; margin:0;">✓ Price Agreed</p>`;
                }

                htmlContent += `</div>`;
            });

            hasCounter = !allDecided;

            // Foto bukti bayar (Status 7) — pakai path absolut agar tidak bergantung URL halaman
            if (pem.bukti_pembayaran) {
                htmlContent += `
                <div style="background: #E1EBFF; padding: 15px; border-radius: 8px; margin-bottom: 15px; text-align: center;">
                    <h4 style="margin: 0 0 10px 0; color: var(--primary-color);">Payment Proof</h4>
                    <a href="/cardhaven/${pem.bukti_pembayaran}" target="_blank">
                        <img src="/cardhaven/${pem.bukti_pembayaran}" style="max-width: 100%; max-height: 250px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    </a>
                </div>`;
            }

            // Alamat retur HANYA muncul saat Rejected (9) setelah barang dikirim (ada no_resi)
            if (pem.status_pembelian == 9 && pem.no_resi) {
                htmlContent += `
                <div style="background: #fff7ed; border: 1px solid #fed7aa; padding: 12px 15px; border-radius: 8px; margin-bottom: 10px; font-size: 0.9rem;">
                    <strong style="color: #c2410c;">📦 Card Return</strong><br>
                    ${pem.alamat
                        ? `<span style="color: #065f46;">Return address submitted. Waiting for shipment.</span>`
                        : `<span style="color: #9a3412;">Please provide your return address so we can send the card back.</span>`
                    }
                </div>`;
            }

            document.getElementById('modalContent').innerHTML = htmlContent;

            let footerHtml = '';

            if (pem.status_pembelian == 2) {
                if (!allDecided) {
                    footerHtml += `<button class="btn-confirm" disabled style="width:auto; padding:10px 20px; opacity:0.4; cursor:not-allowed;" title="Respond to all card offers first">Respond to All Cards First</button>`;
                } else {
                    footerHtml += `<button class="btn-confirm" style="width:auto; padding:10px 20px; background:#27AE60;" onclick="updateStatus(${pem.id_pembelian}, 3, 'All prices agreed! Proceed to shipping.')">Proceed to Shipping</button>`;
                }
            } else if (pem.status_pembelian == 3) {
                footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px; background: #27AE60;" onclick="inputResi(${pem.id_pembelian})">Input Receipt</button>`;
            } else if (pem.status_pembelian == 7) {
                footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px; background: #0088FF;" onclick="completeTransaction(${pem.id_pembelian})">Confirm Payment Received</button>`;
            } else if (pem.status_pembelian == 9 && pem.no_resi) {
                if (!pem.alamat) {
                    footerHtml += `<button class="btn-confirm" style="width: auto; padding: 10px 20px; background: #E67E22;" onclick="inputAddress(${pem.id_pembelian})">Provide Return Address</button>`;
                } else {
                    footerHtml += `<span style="color: #27AE60; font-weight: bold;">✓ Return Address Submitted</span>`;
                }
            }

            // Cancel untuk status awal (0-2)
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
        inputPlaceholder: 'Enter your counter price (Rp)',
        showCancelButton: true,
        confirmButtonText: 'Save Price',
        inputValidator: (value) => {
            const val = value ? value.toString().trim() : '';
            if (!val || isNaN(val) || Number(val) <= 0) {
                return 'Please enter a valid price!';
            }
        }
    }).then(res => {
        if (res.isConfirmed && res.value) {
            const formData = new URLSearchParams();
            formData.append('action', 'customer_negotiate_item');
            formData.append('id_pembelian', idP);
            formData.append('id_kartu', idK);
            formData.append('penawaran_customer', res.value);
            formData.append('id_pengguna', idPengguna);

            fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(result => {
                if (result.status === 'success') {
                    openDetailModal(idP);
                    loadRiwayat();
                } else {
                    Swal.fire('Error', result.message || 'Failed to submit counter offer.', 'error');
                }
            });
        }
    });
}

function acceptItemOffer(idP, idK, price) {
    const formData = new URLSearchParams();
    formData.append('action', 'customer_accept_item');
    formData.append('id_pembelian', idP);
    formData.append('id_kartu', idK);
    formData.append('harga_final', price);
    formData.append('id_pengguna', idPengguna);

    fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(result => {
        if (result.status === 'success') {
            openDetailModal(idP);
        } else {
            Swal.fire('Error', result.message || 'Failed to accept offer.', 'error');
        }
    });
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
        document.getElementById('detailModal').style.display = 'flex';
    });
}

function completeTransaction(id_pembelian) {
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
        if (res.isConfirmed && res.value) {
            const formData = new URLSearchParams();
            formData.append('action', 'update_address');
            formData.append('id_pembelian', id_pembelian);
            formData.append('alamat_retur', res.value);
            formData.append('id_pengguna', idPengguna);

            fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    Swal.fire('Success', result.message, 'success').then(() => {
                        openDetailModal(id_pembelian);
                    });
                } else {
                    Swal.fire('Error', result.message, 'error');
                }
            });
        } else {
            document.getElementById('detailModal').style.display = 'flex';
        }
    });
}

// Update status generik (mis. "Proceed to Shipping"). Di script customer fungsi ini
// tidak ada, jadi ditambahkan di sini agar tombol status-2 berfungsi dengan benar.
function updateStatus(id_pembelian, statusBaru, message) {
    closeDetailModal();
    const formData = new URLSearchParams();
    formData.append('action', 'update_status');
    formData.append('id_pembelian', id_pembelian);
    formData.append('status', statusBaru);
    formData.append('id_pengguna', idPengguna);

    fetch(BUYBACK_CONTROLLER, { method: 'POST', body: formData })
    .then(() => {
        Swal.fire({ icon: 'success', title: 'Success', text: message, timer: 1500, showConfirmButton: false });
        loadRiwayat();
    });
}

document.addEventListener('DOMContentLoaded', loadRiwayat);
