/**
 * master_filter.js
 * Mesin generik untuk Search + Sort + Filter + Pagination pada card Master Data
 * (Game, Set, Rarity, Payment Method). Kolom search/sort/filter mengikuti kolom
 * yang tampil di masing-masing master.
 */
class MasterFilter {
    constructor(cfg) {
        this.cfg = Object.assign({ perPage: 3 }, cfg);
        
        // Otomatis mendeteksi nama kolom ID dari nama file controller (contoh: controller_game.php -> id_game)
        const match = cfg.api.match(/controller_([a-zA-Z0-9_]+)\.php/);
        this.idField = match ? `id_${match[1]}` : 'id';

        // Set urutan awal (default) menggunakan ID dari database
        this.state = { search: '', sort_by: this.idField, sort_order: 'ASC', page: 1 };
        
        (cfg.filters || []).forEach(f => { this.state[f.param] = ''; });
        this._seq = 0; 
        this.buildToolbar();
        this.load();
    }

    // Helper untuk mengubah halaman dari fungsi luar/global
    gotoPage(p) {
        this.state.page = p;
        this.load();
    }

    buildToolbar() {
        const c = this.cfg;
        const tb = document.getElementById(c.toolbarId);
        if (!tb) return;

        const inputStyle = 'padding:4px 8px;border:1.5px solid #cbd5e1;border-radius:8px;font-size:.75rem;color:#334155;background:#fff; height: 30px; box-sizing:border-box; outline:none; white-space:nowrap;';
        
        // Flexbox tanpa wrap agar semuanya tetap dalam satu baris tunggal
        let html = `<div style="display:flex; flex-wrap:nowrap; gap:6px; align-items:center; margin-bottom:12px; width:100%; overflow-x:auto; padding-bottom:4px;">`;
        
        // Search bar (flex: 1 agar memenuhi sisa ruang)
        html += `<input type="text" class="mf-search" placeholder="${c.searchPlaceholder || 'Search...'}" style="${inputStyle} flex:1; min-width:100px;">`;
        
        // Pilihan Sort: Value "Sort: None" diarahkan ke kolom ID database masing-masing master
        html += `<select class="mf-sort" style="${inputStyle} cursor:pointer; width:auto; min-width:90px;">`;
        html += `<option value="${this.idField}">Sort: None</option>`;
        html += c.sortOptions.map(o => `<option value="${o.val}">${o.label}</option>`).join('') + `</select>`;
        
        // Tombol arah panah (Order)
        html += `<button type="button" class="mf-order" title="Toggle sort order" style="${inputStyle}cursor:pointer;font-weight:700;width:30px;padding:4px;flex-shrink:0;">↑</button>`;
        
        // Bagian Dropdown Filter (Jika ada)
        (c.filters || []).forEach(f => {
            html += `<select class="mf-filter" data-param="${f.param}" style="${inputStyle}cursor:pointer; width:auto; min-width:90px;">
                <option value="">${f.label}</option>` +
                f.options.map(o => `<option value="${o.val}">${o.label}</option>`).join('') + `</select>`;
        });
        
        html += `</div>`;
        tb.innerHTML = html;

        const self = this;
        let timer;
        tb.querySelector('.mf-search').addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(() => { self.state.search = this.value; self.state.page = 1; self.load(); }, 350);
        });
        tb.querySelector('.mf-sort').addEventListener('change', function () {
            self.state.sort_by = this.value; self.state.page = 1; self.load();
        });
        tb.querySelector('.mf-order').addEventListener('click', function () {
            self.state.sort_order = self.state.sort_order === 'ASC' ? 'DESC' : 'ASC';
            this.textContent = self.state.sort_order === 'ASC' ? '↑' : '↓';
            self.load();
        });
        tb.querySelectorAll('.mf-filter').forEach(sel => sel.addEventListener('change', function () {
            self.state[this.dataset.param] = this.value; self.state.page = 1; self.load();
        }));
    }

    async load() {
        const c = this.cfg, s = this.state;
        const params = new URLSearchParams({ list: '1', search: s.search, sort_by: s.sort_by, sort_order: s.sort_order, page: s.page });
        (c.filters || []).forEach(f => { if (s[f.param] !== '') params.set(f.param, s[f.param]); });

        const tbody = document.getElementById(c.tbodyId);
        if (!tbody) return;
        const seq = ++this._seq;
        try {
            const res = await fetch(`${c.api}?${params.toString()}`);
            const data = JSON.parse(await res.text());
            if (seq !== this._seq) return; 
            
            let rows = data.data || [];
            
            // Mengurutkan status Aktif (1) di depan, Inaktif (0) di belakang. 
            // Karena menggunakan stable sort bawaan JS, urutan ID asli dari database tetap terjaga di dalam kelompoknya.
            rows.sort((a, b) => parseInt(b.aktif ?? 1) - parseInt(a.aktif ?? 1));

            if (!rows.length) {
                tbody.innerHTML = `<tr><td colspan="${c.colspan}" style="text-align:center;color:#aaa;padding:20px;">No data found.</td></tr>`;
            } else {
                const start = (data.current_page - 1) * c.perPage;
                tbody.innerHTML = rows.map((r, i) => c.renderRow(r, start + i + 1)).join('');
            }
            this.renderPagination(data.current_page || 1, data.total_pages || 1);
        } catch (e) {
            console.error('MasterFilter load error:', e);
        }
    }

    renderPagination(cur, total) {
        const pag = document.getElementById(this.cfg.pagId);
        if (!pag) return;

        let h = '';
        if (cur > 1) {
            h += `<a href="javascript:void(0)" class="page-link" data-p="${cur - 1}">&lt;</a>`;
        } else {
            h += `<span class="page-link disabled">&lt;</span>`;
        }

        const range = 3;
        if (cur > (range + 2)) {
            h += `<a href="javascript:void(0)" class="page-link" data-p="1">1</a><span class="dots">...</span>`;
        } else if (cur > (range + 1)) {
            h += `<a href="javascript:void(0)" class="page-link" data-p="1">1</a>`;
        }

        const start = Math.max(1, cur - range);
        const end = Math.min(total, cur + range);
        for (let i = start; i <= end; i++) {
            const activeClass = (i === cur) ? 'active' : '';
            h += `<a href="javascript:void(0)" class="page-link ${activeClass}" data-p="${i}">${i}</a>`;
        }

        if (cur < (total - range - 1)) {
            h += `<span class="dots">...</span><a href="javascript:void(0)" class="page-link" data-p="${total}">${total}</a>`;
        } else if (cur < (total - range)) {
            h += `<a href="javascript:void(0)" class="page-link" data-p="${total}">${total}</a>`;
        }

        if (cur < total) {
            h += `<a href="javascript:void(0)" class="page-link" data-p="${cur + 1}">&gt;</a>`;
        } else {
            h += `<span class="page-link disabled">&gt;</span>`;
        }

        pag.innerHTML = h;

        const self = this;
        pag.querySelectorAll('a[data-p]').forEach(a => a.addEventListener('click', function () {
            self.state.page = parseInt(this.dataset.p);
            self.load();
        }));
    }
}

function mfEsc(v) {
    return String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
function mfStatusPill(aktif) {
    return parseInt(aktif) === 1
        ? '<span style="color:#27AE60;font-weight:bold;">Active</span>'
        : '<span style="color:#E74C3C;font-weight:bold;">Inactive</span>';
}

// ── Inisialisasi per-master (Aman & Tidak ada yang berubah/hilang) ──
document.addEventListener('DOMContentLoaded', function () {
    const P = '/CardHaven/interface/product';

    if (document.getElementById('game-toolbar')) {
        const mfGame = new MasterFilter({
            api: `${P}/controller_game.php`, toolbarId: 'game-toolbar', tbodyId: 'game-tbody', pagId: 'game-pag', colspan: 5,
            searchPlaceholder: 'Search name / developer…',
            sortOptions: [{ val: 'nama_game', label: 'Sort: Name' }, { val: 'developer', label: 'Sort: Developer' }, { val: 'aktif', label: 'Sort: Status' }],
            filters: [],
            renderRow: (r, no) => `<tr>
                <td>${no}</td>
                <td>${mfEsc(r.nama_game)}</td>
                <td>${mfEsc(r.developer)}</td>
                <td>${mfStatusPill(r.aktif)}</td>
                <td><div class="btn-action-group">
                    <button class="btn-view-icon" onclick="openDetailModal(${r.id_game})">...</button>
                    <button class="btn-edit-icon" onclick="openEditModal(${r.id_game})"><img src="/cardhaven/assets/image/edit.svg" alt=""></button>
                    <button class="btn-delete-icon" onclick="confirmDelete(${r.id_game})"><img src="/cardhaven/assets/image/delete.svg" alt=""></button>
                    <label class="switch"><input type="checkbox" ${parseInt(r.aktif) === 1 ? 'checked' : ''} onchange="toggleStatus(${r.id_game}, this.checked, this)"><span class="slider"></span></label>
                </div></td></tr>`
        });
        window.loadGamePage = (page) => mfGame.gotoPage(page);
    }

    if (document.getElementById('metode-toolbar')) {
        const mfMetode = new MasterFilter({
            api: `${P}/controller_metode.php`, toolbarId: 'metode-toolbar', tbodyId: 'metode-tbody', pagId: 'metode-pag', colspan: 6,
            searchPlaceholder: 'Search name / provider…',
            sortOptions: [{ val: 'nama_metode', label: 'Sort: Name' }, { val: 'provider', label: 'Sort: Provider' }, { val: 'biaya_admin', label: 'Sort: Admin Fee' }, { val: 'aktif', label: 'Sort: Status' }],
            filters: [],
            renderRow: (r, no) => `<tr>
                <td>${no}</td>
                <td>${mfEsc(r.nama_metode)}</td>
                <td>${mfEsc(r.provider || '-')}</td>
                <td>Rp. ${Number(r.biaya_admin || 0).toLocaleString('id-ID')}</td>
                <td>${mfStatusPill(r.aktif)}</td>
                <td><div class="btn-action-group">
                    <button class="btn-view-icon" onclick="openDetailMetode(${r.id_metode})">...</button>
                    <button class="btn-edit-icon" onclick="openEditMetode(${r.id_metode})"><img src="/cardhaven/assets/image/edit.svg" alt=""></button>
                    <button class="btn-delete-icon" onclick="confirmDeleteMetode(${r.id_metode})"><img src="/cardhaven/assets/image/delete.svg" alt=""></button>
                    <label class="switch"><input type="checkbox" ${parseInt(r.aktif) === 1 ? 'checked' : ''} onchange="toggleMetode(${r.id_metode}, this.checked, this)"><span class="slider"></span></label>
                </div></td></tr>`
        });
        window.loadMetodePage = (page) => mfMetode.gotoPage(page);
    }

    if (document.getElementById('set-toolbar') || document.getElementById('rarity-toolbar')) {
        fetch(`${P}/controller_set.php?get_games=1`).then(r => r.json()).then(res => {
            const gameOpts = (res.data || []).map(g => ({ val: String(g.id_game), label: g.nama_game }));
            const gameFilter = { param: 'id_game', label: 'All Games', options: gameOpts };

            if (document.getElementById('set-toolbar')) {
                const mfSet = new MasterFilter({
                    api: `${P}/controller_set.php`, toolbarId: 'set-toolbar', tbodyId: 'set-tbody', pagId: 'set-pag', colspan: 5,
                    searchPlaceholder: 'Search name / code…',
                    sortOptions: [{ val: 'nama_set', label: 'Sort: Name' }, { val: 'kode_set', label: 'Sort: Code' }, { val: 'aktif', label: 'Sort: Status' }],
                    filters: [gameFilter],
                    renderRow: (r, no) => `<tr>
                        <td>${no}</td>
                        <td>${mfEsc(r.nama_set)}</td>
                        <td>${mfEsc(r.nama_game || 'N/A')}</td>
                        <td>${mfStatusPill(r.aktif)}</td>
                        <td><div class="btn-action-group">
                            <button class="btn-view-icon" onclick="openDetailSetModal(${r.id_set})">...</button>
                            <button class="btn-edit-icon" onclick="openEditSetModal(${r.id_set})"><img src="/cardhaven/assets/image/edit.svg" alt=""></button>
                            <button class="btn-delete-icon" onclick="confirmDeleteSet(${r.id_set})"><img src="/cardhaven/assets/image/delete.svg" alt=""></button>
                            <label class="switch"><input type="checkbox" ${parseInt(r.aktif) === 1 ? 'checked' : ''} onchange="toggleSetStatus(${r.id_set}, this.checked, this)"><span class="slider"></span></label>
                        </div></td></tr>`
                });
                window.loadSetPage = (page) => mfSet.gotoPage(page);
            }

            if (document.getElementById('rarity-toolbar')) {
                const mfRarity = new MasterFilter({
                    api: `${P}/controller_rarity.php`, toolbarId: 'rarity-toolbar', tbodyId: 'rarity-tbody', pagId: 'rarity-pag', colspan: 5,
                    searchPlaceholder: 'Search name / code…',
                    sortOptions: [{ val: 'nama_rarity', label: 'Sort: Name' }, { val: 'kode_rarity', label: 'Sort: Code' }, { val: 'aktif', label: 'Sort: Status' }],
                    filters: [gameFilter],
                    renderRow: (r, no) => `<tr>
                        <td>${no}</td>
                        <td>${mfEsc(r.nama_rarity)}${r.kode_rarity ? ' (' + mfEsc(r.kode_rarity) + ')' : ''}</td>
                        <td>${mfEsc(r.nama_game || 'N/A')}</td>
                        <td>${mfStatusPill(r.aktif)}</td>
                        <td><div class="btn-action-group">
                            <button class="btn-view-icon" onclick="openDetailRarity(${r.id_rarity})">...</button>
                            <button class="btn-edit-icon" onclick="openEditRarity(${r.id_rarity})"><img src="/cardhaven/assets/image/edit.svg" alt=""></button>
                            <button class="btn-delete-icon" onclick="confirmDeleteRarity(${r.id_rarity})"><img src="/cardhaven/assets/image/delete.svg" alt=""></button>
                            <label class="switch"><input type="checkbox" ${parseInt(r.aktif) === 1 ? 'checked' : ''} onchange="toggleRarityStatus(${r.id_rarity}, this.checked, this)"><span class="slider"></span></label>
                        </div></td></tr>`
                });
                window.loadRarityPage = (page) => mfRarity.gotoPage(page);
            }
        }).catch(e => console.error('Load game filter options error:', e));
    }
});