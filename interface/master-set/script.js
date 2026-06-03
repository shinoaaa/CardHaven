// =============================================================
//  MASTER SET – script.js
//  CardHaven | pola: vanilla JS + fetch + FormData
// =============================================================

document.addEventListener("DOMContentLoaded", () => {

    // ---- State ----
    let currentPage   = 1;
    const LIMIT       = 10;
    let deleteTargetId = null;

    // ---- DOM refs ----
    const tableBody    = document.getElementById("setTableBody");
    const pagination   = document.getElementById("pagination");
    const searchInput  = document.getElementById("searchInput");
    const filterGame   = document.getElementById("filterGame");
    const filterStatus = document.getElementById("filterStatus");

    // Modal form
    const modalOverlay = document.getElementById("modalOverlay");
    const modalTitle   = document.getElementById("modalTitle");
    const btnOpenModal = document.getElementById("btnOpenModal");
    const btnCloseModal= document.getElementById("btnCloseModal");
    const btnSubmit    = document.getElementById("btnSubmit");

    const editIdSet     = document.getElementById("editIdSet");
    const inputNamaSet  = document.getElementById("inputNamaSet");
    const inputKodeSet  = document.getElementById("inputKodeSet");
    const inputGame     = document.getElementById("inputGame");
    const inputTanggal  = document.getElementById("inputTanggalRilis");
    const inputStatus   = document.getElementById("inputStatus");
    const groupStatus   = document.getElementById("groupStatus");

    // Error elements (form)
    const errNamaSet = document.getElementById("errNamaSet");
    const errKodeSet = document.getElementById("errKodeSet");
    const errGame    = document.getElementById("errGame");
    const errGeneral = document.getElementById("errGeneral");

    // Modal delete
    const modalDelete       = document.getElementById("modalDelete");
    const btnCloseDelete    = document.getElementById("btnCloseDelete");
    const btnCancelDelete   = document.getElementById("btnCancelDelete");
    const btnConfirmDelete  = document.getElementById("btnConfirmDelete");


    // ==========================================================
    // INIT: load dropdown game + load tabel
    // ==========================================================
    loadGameDropdown();
    loadSets();

    // Debounce search
    let searchTimer;
    searchInput.addEventListener("input", () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            currentPage = 1;
            loadSets();
        }, 400);
    });

    filterGame.addEventListener("change",   () => { currentPage = 1; loadSets(); });
    filterStatus.addEventListener("change", () => { currentPage = 1; loadSets(); });


    // ==========================================================
    // LOAD DROPDOWN GAME (filter + form modal)
    // ==========================================================
    async function loadGameDropdown() {
        try {
            const res  = await fetch("/cardhaven/interface/master-set/get_game_list.php");
            const data = await res.json();

            if (data.status !== "success") return;

            data.data.forEach(g => {
                // dropdown filter
                const optFilter = new Option(g.nama_game, g.id_game);
                filterGame.appendChild(optFilter);

                // dropdown form modal
                const optForm = new Option(g.nama_game, g.id_game);
                inputGame.appendChild(optForm);
            });

        } catch (err) {
            console.error("Gagal load game dropdown:", err);
        }
    }


    // ==========================================================
    // READ – load tabel set
    // ==========================================================
    async function loadSets() {
        tableBody.innerHTML = `<tr><td colspan="6" class="loading-row">Loading...</td></tr>`;
        pagination.innerHTML = "";

        const params = new URLSearchParams({
            page:   currentPage,
            limit:  LIMIT,
            search: searchInput.value.trim(),
            game:   filterGame.value,
            status: filterStatus.value,
        });

        try {
            const res  = await fetch(`/cardhaven/interface/master-set/get_set.php?${params}`);
            const data = await res.json();

            if (data.status !== "success") {
                tableBody.innerHTML = `<tr><td colspan="6" class="loading-row">Gagal memuat data: ${data.message}</td></tr>`;
                return;
            }

            renderTable(data.data);
            renderPagination(data.meta);

        } catch (err) {
            console.error("loadSets error:", err);
            tableBody.innerHTML = `<tr><td colspan="6" class="loading-row">Terjadi kesalahan jaringan.</td></tr>`;
        }
    }

    function renderTable(rows) {
        if (rows.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="6" class="loading-row">Tidak ada data ditemukan.</td></tr>`;
            return;
        }

        tableBody.innerHTML = rows.map(row => {
            const tanggal = row.tanggal_rilis
                ? new Date(row.tanggal_rilis).toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' })
                : '-';

            const badgeClass = row.aktif == 1 ? 'badge-active' : 'badge-inactive';
            const badgeText  = row.aktif == 1 ? 'Active' : 'Inactive';

            return `
            <tr>
                <td>${escHtml(row.nama_set)}</td>
                <td>${escHtml(row.kode_set)}</td>
                <td>${escHtml(row.nama_game)}</td>
                <td>${tanggal}</td>
                <td><span class="badge ${badgeClass}">${badgeText}</span></td>
                <td>
                    <button class="btn-edit"   onclick="openEditModal(${JSON.stringify(row).replace(/"/g, '&quot;')})">Edit</button>
                    <button class="btn-delete" onclick="openDeleteModal(${row.id_set})" ${row.aktif == 0 ? 'disabled style="opacity:0.4;cursor:not-allowed"' : ''}>Delete</button>
                </td>
            </tr>`;
        }).join('');
    }

    function renderPagination(meta) {
        if (meta.total_pages <= 1) return;

        const { page, total_pages } = meta;
        let html = '';

        // Prev
        html += `<button class="page-btn" onclick="goPage(${page - 1})" ${page === 1 ? 'disabled' : ''}>&lt;</button>`;

        // Pages (show max 5 pages around current)
        const start = Math.max(1, page - 2);
        const end   = Math.min(total_pages, page + 2);

        if (start > 1) {
            html += `<button class="page-btn" onclick="goPage(1)">1</button>`;
            if (start > 2) html += `<span class="page-dots">...</span>`;
        }
        for (let i = start; i <= end; i++) {
            html += `<button class="page-btn ${i === page ? 'active' : ''}" onclick="goPage(${i})">${i}</button>`;
        }
        if (end < total_pages) {
            if (end < total_pages - 1) html += `<span class="page-dots">...</span>`;
            html += `<button class="page-btn" onclick="goPage(${total_pages})">${total_pages}</button>`;
        }

        // Next
        html += `<button class="page-btn" onclick="goPage(${page + 1})" ${page === total_pages ? 'disabled' : ''}>&gt;</button>`;

        pagination.innerHTML = html;
    }

    window.goPage = function(p) {
        currentPage = p;
        loadSets();
    };


    // ==========================================================
    // MODAL ADD
    // ==========================================================
    btnOpenModal.addEventListener("click", () => {
        resetForm();
        modalTitle.textContent = "Add New Set";
        editIdSet.value = '';
        groupStatus.style.display = 'none';
        modalOverlay.classList.add("show");
    });

    btnCloseModal.addEventListener("click", () => modalOverlay.classList.remove("show"));
    modalOverlay.addEventListener("click", (e) => {
        if (e.target === modalOverlay) modalOverlay.classList.remove("show");
    });


    // ==========================================================
    // MODAL EDIT (dipanggil dari inline onclick di tabel)
    // ==========================================================
    window.openEditModal = function(row) {
        resetForm();
        modalTitle.textContent = "Edit Set";
        editIdSet.value        = row.id_set;
        inputNamaSet.value     = row.nama_set;
        inputKodeSet.value     = row.kode_set;
        inputGame.value        = row.id_game;
        inputTanggal.value     = row.tanggal_rilis || '';
        inputStatus.value      = row.aktif;
        groupStatus.style.display = 'block'; // status hanya tampil saat edit
        modalOverlay.classList.add("show");
    };


    // ==========================================================
    // SUBMIT (Add / Edit)
    // ==========================================================
    btnSubmit.addEventListener("click", async () => {
        clearErrors();

        const namaSet = inputNamaSet.value.trim();
        const kodeSet = inputKodeSet.value.trim();
        const idGame  = inputGame.value;
        let isValid   = true;

        if (!namaSet) {
            showFieldError(inputNamaSet, errNamaSet, "Nama set tidak boleh kosong.");
            isValid = false;
        }
        if (!kodeSet) {
            showFieldError(inputKodeSet, errKodeSet, "Kode set tidak boleh kosong.");
            isValid = false;
        }
        if (!idGame) {
            showFieldError(inputGame, errGame, "Pilih game terlebih dahulu.");
            isValid = false;
        }
        if (!isValid) return;

        const isEdit = editIdSet.value !== '';
        const url    = isEdit
            ? '/cardhaven/interface/master-set/update_set.php'
            : '/cardhaven/interface/master-set/add_set.php';

        const formData = new FormData();
        if (isEdit) {
            formData.append('id_set',  editIdSet.value);
            formData.append('aktif',   inputStatus.value);
        }
        formData.append('nama_set',      namaSet);
        formData.append('kode_set',      kodeSet);
        formData.append('id_game',       idGame);
        formData.append('tanggal_rilis', inputTanggal.value);
        formData.append('created_by',    1); // TODO: ganti dengan session user id
        formData.append('modified_by',   1); // TODO: ganti dengan session user id

        btnSubmit.disabled   = true;
        btnSubmit.textContent = "Saving...";

        try {
            const res  = await fetch(url, { method: 'POST', body: formData });
            const text = await res.text();
            const data = JSON.parse(text);

            if (data.status === 'success') {
                modalOverlay.classList.remove("show");
                loadSets();
            } else {
                handleFormError(data);
            }
        } catch (err) {
            console.error("Submit error:", err);
            errGeneral.textContent = "Terjadi kesalahan. Silakan coba lagi.";
        } finally {
            btnSubmit.disabled    = false;
            btnSubmit.textContent = "Save";
        }
    });


    // ==========================================================
    // MODAL DELETE
    // ==========================================================
    window.openDeleteModal = function(idSet) {
        deleteTargetId = idSet;
        modalDelete.classList.add("show");
    };

    [btnCloseDelete, btnCancelDelete].forEach(btn => {
        btn.addEventListener("click", () => {
            deleteTargetId = null;
            modalDelete.classList.remove("show");
        });
    });
    modalDelete.addEventListener("click", (e) => {
        if (e.target === modalDelete) {
            deleteTargetId = null;
            modalDelete.classList.remove("show");
        }
    });

    btnConfirmDelete.addEventListener("click", async () => {
        if (!deleteTargetId) return;

        btnConfirmDelete.disabled   = true;
        btnConfirmDelete.textContent = "Processing...";

        const formData = new FormData();
        formData.append('id_set',      deleteTargetId);
        formData.append('modified_by', 1); // TODO: ganti dengan session user id

        try {
            const res  = await fetch('/cardhaven/interface/master-set/delete_set.php', { method: 'POST', body: formData });
            const text = await res.text();
            const data = JSON.parse(text);

            modalDelete.classList.remove("show");
            deleteTargetId = null;

            if (data.status === 'success') {
                loadSets();
            } else {
                alert("Gagal: " + data.message);
            }
        } catch (err) {
            console.error("Delete error:", err);
            alert("Terjadi kesalahan jaringan.");
        } finally {
            btnConfirmDelete.disabled    = false;
            btnConfirmDelete.textContent = "Deactivate";
        }
    });


    // ==========================================================
    // HELPERS
    // ==========================================================
    function resetForm() {
        editIdSet.value    = '';
        inputNamaSet.value = '';
        inputKodeSet.value = '';
        inputGame.value    = '';
        inputTanggal.value = '';
        inputStatus.value  = '1';
        clearErrors();
    }

    function clearErrors() {
        [inputNamaSet, inputKodeSet, inputGame].forEach(el => el.classList.remove("input-error"));
        [errNamaSet, errKodeSet, errGame].forEach(el => el.textContent = '');
        errGeneral.textContent = '';
    }

    function showFieldError(inputEl, errorEl, msg) {
        inputEl.classList.add("input-error");
        errorEl.textContent = msg;
    }

    function handleFormError(data) {
        const targetMap = {
            nama_set: [inputNamaSet, errNamaSet],
            kode_set: [inputKodeSet, errKodeSet],
            id_game:  [inputGame,    errGame],
        };
        if (data.target && targetMap[data.target]) {
            const [inputEl, errorEl] = targetMap[data.target];
            showFieldError(inputEl, errorEl, data.message);
        } else {
            errGeneral.textContent = data.message || "Terjadi kesalahan.";
        }
    }

    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

});
