
class MasterFilter {
    constructor(cfg) {
        this.cfg = Object.assign({ perPage: 3, colspan: 5 }, cfg);
        
        // 1. Deteksi Field ID (id_game, id_set, id_rarity, id_produk)
        const match = cfg.api.match(/controller_([a-zA-Z0-9_]+)\.php/);
        const entity = match ? match[1] : 'data';
        this.idField = (entity === 'produk') ? 'id_produk' : `id_${entity}`;

        // 2. Initial State
        this.state = {
            search: '',
            sort_by: this.idField, // Saat awal (None), pakai ID sebagai acuan
            sort_order: 'ASC',
            page: 1,
            status: '' // Default All Status
        };

        // 3. Tambahkan custom filters ke state
        (cfg.filters || []).forEach(f => {
            this.state[f.param] = '';
        });

        this._seq = 0; 
        this.buildToolbar();
        this.load();
    }

    // Fungsi global untuk navigasi halaman
    gotoPage(p) {
        this.state.page = p;
        this.load();
    }

    buildToolbar() {
        const c = this.cfg;
        const tb = document.getElementById(c.toolbarId);
        if (!tb) return;

        const inputStyle = 'padding:4px 8px;border:1.5px solid #cbd5e1;border-radius:8px;font-size:.75rem;color:#334155;background:#fff; height: 30px; box-sizing:border-box; outline:none;';
        
        let html = `<div style="display:flex; flex-wrap:nowrap; gap:6px; align-items:center; margin-bottom:12px; width:100%; overflow-x:auto; padding-bottom:5px;">`;
        
        // --- SEARCH BAR ---
        html += `<input type="text" class="mf-search" placeholder="${c.searchPlaceholder || 'Search...'}" style="${inputStyle} flex:1; min-width:150px;">`;
        // --- STATUS FILTER ---
        html += `<select class="mf-status" style="${inputStyle} cursor:pointer; width:auto; min-width:100px;">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                 </select>`;
        // --- SORT DROPDOWN ---
        html += `<select class="mf-sort" style="${inputStyle} cursor:pointer; width:auto; min-width:110px;">`;
        html += `<option value="${this.idField}">Sort: None</option>`;
        html += c.sortOptions.map(o => `<option value="${o.val}">${o.label}</option>`).join('') + `</select>`;
        
        // --- ORDER BUTTON (UP/DOWN) ---
        html += `<button type="button" class="mf-order" title="Toggle Sort Order" style="${inputStyle} cursor:pointer; font-weight:700; width:35px; padding:0;">↑</button>`;
        
        

        // --- CUSTOM FILTERS (Like 'Game Filter' in Set/Rarity) ---
        (c.filters || []).forEach(f => {
            html += `<select class="mf-custom-filter" data-param="${f.param}" style="${inputStyle} cursor:pointer; width:auto; min-width:110px;">
                <option value="">${f.label}</option>` +
                f.options.map(o => `<option value="${o.val}">${o.label}</option>`).join('') + `</select>`;
        });
        
        html += `</div>`;
        tb.innerHTML = html;

        // --- EVENT LISTENERS ---
        const self = this;
        let timer;

        tb.querySelector('.mf-search').addEventListener('input', function() {
            clearTimeout(timer);
            timer = setTimeout(() => { 
                self.state.search = this.value; 
                self.state.page = 1; 
                self.load(); 
            }, 400);
        });

        tb.querySelector('.mf-sort').addEventListener('change', function() {
            self.state.sort_by = this.value; 
            self.state.page = 1; 
            self.load();
        });

        tb.querySelector('.mf-order').addEventListener('click', function() {
            self.state.sort_order = self.state.sort_order === 'ASC' ? 'DESC' : 'ASC';
            this.textContent = self.state.sort_order === 'ASC' ? '↑' : '↓';
            self.load();
        });

        tb.querySelector('.mf-status').addEventListener('change', function() {
            self.state.status = this.value; 
            self.state.page = 1; 
            self.load();
        });

        tb.querySelectorAll('.mf-custom-filter').forEach(sel => {
            sel.addEventListener('change', function() {
                self.state[this.dataset.param] = this.value;
                self.state.page = 1;
                self.load();
            });
        });
    }

    async load() {
        const c = this.cfg, s = this.state;
        
        // 1. Persiapkan Parameter URL
        const params = new URLSearchParams({ 
            list: '1', 
            search: s.search, 
            sort_by: s.sort_by, 
            sort_order: s.sort_order, 
            page: s.page,
            status: s.status
        });
        
        // 2. Tambahkan custom filters ke URL
        (c.filters || []).forEach(f => {
            if (s[f.param] !== '') params.set(f.param, s[f.param]);
        });

        const tbody = document.getElementById(c.tbodyId);
        if (!tbody) return;

        const seq = ++this._seq;
        try {
            const res = await fetch(`${c.api}?${params.toString()}`);
            const data = await res.json();
            
            // Mencegah data lama menimpa data baru jika request cepat
            if (seq !== this._seq) return;

            if (data.status === 'error') throw new Error(data.message);

            if (!data.data || data.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="${c.colspan}" style="text-align:center;padding:40px;color:#94a3b8;">No records found.</td></tr>`;
            } else {
                const startNo = (data.current_page - 1) * this.cfg.perPage;
                tbody.innerHTML = data.data.map((r, i) => c.renderRow(r, startNo + i + 1)).join('');
            }

            this.renderPagination(data.current_page || 1, data.total_pages || 1);
        } catch (e) {
            console.error("MasterFilter Load Error:", e);
            tbody.innerHTML = `<tr><td colspan="${c.colspan}" style="text-align:center;padding:20px;color:red;">Error loading data.</td></tr>`;
        }
    }

    renderPagination(cur, total) {
        const pag = document.getElementById(this.cfg.pagId);
        if (!pag) return;

        let h = '';
        const addPage = (p, label, active = false, disabled = false) => {
            if (disabled) return `<span class="page-link disabled">${label}</span>`;
            return `<a href="javascript:void(0)" class="page-link ${active ? 'active' : ''}" data-p="${p}">${label}</a>`;
        };

        h += addPage(cur - 1, '&lt;', false, cur === 1);

        const range = 2;
        for (let i = 1; i <= total; i++) {
            if (i === 1 || i === total || (i >= cur - range && i <= cur + range)) {
                h += addPage(i, i, i === cur);
            } else if (i === cur - range - 1 || i === cur + range + 1) {
                h += `<span class="dots">...</span>`;
            }
        }

        h += addPage(cur + 1, '&gt;', false, cur === total);
        pag.innerHTML = h;

        pag.querySelectorAll('a[data-p]').forEach(a => {
            a.onclick = () => this.gotoPage(parseInt(a.dataset.p));
        });
    }
}

// --- HELPER FUNCTIONS ---
function mfEsc(v) { 
    return String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); 
}

function mfStatusPill(aktif) {
    const active = parseInt(aktif) === 1;
    return `<span style="color:${active ? '#27AE60' : '#E74C3C'}; font-weight:700;">${active ? 'Active' : 'Inactive'}</span>`;
}

document.addEventListener('DOMContentLoaded', function () {
    const P = '/CardHaven/interface/product';

    // --- 1. MODUL GAME ---
    if (document.getElementById('game-toolbar')) {
        new MasterFilter({
            api: `${P}/controller_game.php`,
            toolbarId: 'game-toolbar', tbodyId: 'game-tbody', pagId: 'game-pag',
            colspan: 5,
            searchPlaceholder: 'Search name / developer...',
            sortOptions: [
                { val: 'nama_game', label: 'Sort: Name' },
                { val: 'developer', label: 'Sort: Developer' }
            ],
            renderRow: (r, no) => `<tr>
                <td>${no}</td>
                <td>${mfEsc(r.nama_game)}</td>
                <td>${mfEsc(r.developer)}</td>
                <td>${mfStatusPill(r.aktif)}</td>
                <td><div class="btn-action-group">
                    <button class="btn-view-icon" onclick="openDetailModal(${r.id_game})">...</button>
                    <button class="btn-edit-icon" onclick="openEditModal(${r.id_game})"><img src="/cardhaven/assets/image/edit.svg"></button>
                    <button class="btn-delete-icon" onclick="confirmDelete(${r.id_game})"><img src="/cardhaven/assets/image/delete.svg"></button>
                    <label class="switch"><input type="checkbox" ${parseInt(r.aktif) === 1 ? 'checked' : ''} onchange="toggleStatus(${r.id_game}, this.checked, this)"><span class="slider"></span></label>
                </div></td></tr>`
        });
    }

    // --- 2. MODUL SET ---
    if (document.getElementById('set-toolbar')) {
        new MasterFilter({
            api: `${P}/controller_set.php`,
            toolbarId: 'set-toolbar', tbodyId: 'set-tbody', pagId: 'set-pag',
            colspan: 5,
            searchPlaceholder: 'Search set or game name...',
            sortOptions: [
                { val: 'nama_set', label: 'Sort: Name' },
                { val: 'nama_game', label: 'Sort: Game' } // Ganti Code ke Game
            ],
            filters: [], // Filter dropdown Game dihapus
            renderRow: (r, no) => `<tr>
                <td>${no}</td>
                <td>${mfEsc(r.nama_set)}</td>
                <td>${mfEsc(r.nama_game || 'N/A')}</td>
                <td>${mfStatusPill(r.aktif)}</td>
                <td><div class="btn-action-group">
                    <button class="btn-view-icon" onclick="openDetailSetModal(${r.id_set})">...</button>
                    <button class="btn-edit-icon" onclick="openEditSetModal(${r.id_set})"><img src="/cardhaven/assets/image/edit.svg"></button>
                    <button class="btn-delete-icon" onclick="confirmDeleteSet(${r.id_set})"><img src="/cardhaven/assets/image/delete.svg"></button>
                    <label class="switch"><input type="checkbox" ${parseInt(r.aktif) === 1 ? 'checked' : ''} onchange="toggleSetStatus(${r.id_set}, this.checked, this)"><span class="slider"></span></label>
                </div></td></tr>`
        });
    }

    // --- 3. MODUL RARITY ---
    if (document.getElementById('rarity-toolbar')) {
        new MasterFilter({
            api: `${P}/controller_rarity.php`,
            toolbarId: 'rarity-toolbar', tbodyId: 'rarity-tbody', pagId: 'rarity-pag',
            colspan: 5,
            searchPlaceholder: 'Search rarity or game name...',
            sortOptions: [
                { val: 'nama_rarity', label: 'Sort: Name' },
                { val: 'nama_game', label: 'Sort: Game' } // Ganti Code ke Game
            ],
            filters: [], // Filter dropdown Game dihapus
            renderRow: (r, no) => `<tr>
                <td>${no}</td>
                <td>${mfEsc(r.nama_rarity)} (${mfEsc(r.kode_rarity)})</td>
                <td>${mfEsc(r.nama_game || 'N/A')}</td>
                <td>${mfStatusPill(r.aktif)}</td>
                <td><div class="btn-action-group">
                    <button class="btn-view-icon" onclick="openDetailRarity(${r.id_rarity})">...</button>
                    <button class="btn-edit-icon" onclick="openEditRarity(${r.id_rarity})"><img src="/cardhaven/assets/image/edit.svg"></button>
                    <button class="btn-delete-icon" onclick="confirmDeleteRarity(${r.id_rarity})"><img src="/cardhaven/assets/image/delete.svg"></button>
                    <label class="switch"><input type="checkbox" ${parseInt(r.aktif) === 1 ? 'checked' : ''} onchange="toggleRarityStatus(${r.id_rarity}, this.checked, this)"><span class="slider"></span></label>
                </div></td></tr>`
        });
    }

    // --- 4. MODUL PRODUCT ---
    if (document.getElementById('produk-toolbar')) {
        new MasterFilter({
            api: `${P}/controller_produk.php`,
            toolbarId: 'produk-toolbar', tbodyId: 'produk-tbody', pagId: 'produk-pag',
            colspan: 8,
            searchPlaceholder: 'Search product or game...',
            sortOptions: [
                { val: 'nama_produk', label: 'Sort: Name' },
                { val: 'harga_jual', label: 'Sort: Price' },
                { val: 'stok', label: 'Sort: Stock' }
            ],
            renderRow: (r, no) => `<tr>
                <td>${no}</td>
                <td style="text-align:left; font-weight:600;">${mfEsc(r.nama_produk)}</td>
                <td>${mfEsc(r.nama_game || '-')}</td>
                <td>${mfEsc(r.tipe_produk)}</td>
                <td>${parseInt(r.stok)}</td>
                <td style="text-align:right; font-weight:bold;">Rp${Number(r.harga_jual).toLocaleString('id-ID')}</td>
                <td>${mfStatusPill(r.status)}</td>
                <td><div class="btn-action-group">
                    <button class="btn-view-icon" onclick="openDetailProductModal(${r.id_produk})">...</button>
                    <button class="btn-edit-icon" onclick="openEditProductModal(${r.id_produk})"><img src="/cardhaven/assets/image/edit.svg"></button>
                    <button class="btn-delete-icon" onclick="confirmDeleteProduct(${r.id_produk})"><img src="/cardhaven/assets/image/delete.svg"></button>
                    <label class="switch"><input type="checkbox" ${parseInt(r.status) === 1 ? 'checked' : ''} onchange="toggleProductStatus(${r.id_produk}, this.checked, this)"><span class="slider"></span></label>
                </div></td></tr>`
        });
    }

    // --- 5. MODUL METODE PEMBAYARAN ---
    if (document.getElementById('metode-toolbar')) {
        new MasterFilter({
            api: `${P}/controller_metode.php`,
            toolbarId: 'metode-toolbar', 
            tbodyId: 'metode-tbody', 
            pagId: 'metode-pag',
            colspan: 6, // No, Nama, Provider, Biaya, Status, Action
            searchPlaceholder: 'Search method or provider...',
            sortOptions: [
                { val: 'nama_metode', label: 'Sort: Name' },
                { val: 'provider', label: 'Sort: Provider' },
                { val: 'biaya_admin', label: 'Sort: Admin Fee' }
            ],
            // Field ID otomatis terdeteksi sebagai 'id_metode' dari nama file controller
            renderRow: (r, no) => `<tr>
                <td>${no}</td>
                <td style="font-weight:600;">${mfEsc(r.nama_metode)}</td>
                <td>${mfEsc(r.provider || '-')}</td>
                <td style="text-align:right;">Rp${Number(r.biaya_admin || 0).toLocaleString('id-ID')}</td>
                <td>${mfStatusPill(r.aktif)}</td>
                <td><div class="btn-action-group">
                    <button class="btn-view-icon" onclick="openDetailMetode(${r.id_metode})">...</button>
                    <button class="btn-edit-icon" onclick="openEditMetode(${r.id_metode})"><img src="/cardhaven/assets/image/edit.svg"></button>
                    <button class="btn-delete-icon" onclick="confirmDeleteMetode(${r.id_metode})"><img src="/cardhaven/assets/image/delete.svg"></button>
                    <label class="switch">
                        <input type="checkbox" ${parseInt(r.aktif) === 1 ? 'checked' : ''} onchange="toggleMetode(${r.id_metode}, this.checked, this)">
                        <span class="slider"></span>
                    </label>
                </div></td></tr>`
        });
    }
});