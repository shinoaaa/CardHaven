/**
 * user_master_filter.js
 * Engine khusus untuk Manajemen User (Admin, SuperAdmin, Customer) & Supplier.
 */
class UserMasterFilter {
    constructor(cfg) {
        this.cfg = Object.assign({ perPage: 7, colspan: 7 }, cfg);
        
        // 1. Deteksi Field ID & Status secara otomatis
        const isSupplier = cfg.api.toLowerCase().includes('supp');
        this.idField = isSupplier ? 'id_supplier' : 'id_pengguna';
        this.statusField = isSupplier ? 'aktif' : 'status_akun';

        // 2. Initial State
        this.state = {
            search: '',
            sort_by: this.idField, 
            sort_order: 'ASC',
            page: 1,
            status: ''
        };

        this._seq = 0; 
        this.buildToolbar();
        this.load();
    }

    gotoPage(p) {
        this.state.page = p;
        this.load();
    }

    buildToolbar() {
        const c = this.cfg;
        const tb = document.getElementById(c.toolbarId);
        if (!tb) return;

        const baseStyle = 'border:1.5px solid #D0DAF0; border-radius:9999px; font-size:.8rem; color:#334155; background-color:#fff; height: 34px; box-sizing:border-box; outline:none;';
        
        let html = `<div style="display:flex; flex-wrap:nowrap; gap:8px; align-items:center; margin-bottom:15px; width:100%; overflow-x:auto; padding-bottom:5px;">`;
        
        // Search
        html += `<input type="text" class="mf-search" placeholder="${c.searchPlaceholder}" style="${baseStyle} padding:6px 14px; flex:1; min-width:200px;">`;
        html += `<select class="mf-status" style="${baseStyle} padding:6px 36px 6px 14px; cursor:pointer; min-width:110px; width: auto; flex-shrink: 0;">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                 </select>`;
        
        // Sort Dropdown
        html += `<select class="mf-sort" style="${baseStyle} padding:6px 36px 6px 14px; cursor:pointer; min-width:120px; width: auto; flex-shrink: 0;">`;
        html += `<option value="${this.idField}">Sort: None</option>`;
        html += c.sortOptions.map(o => `<option value="${o.val}">${o.label}</option>`).join('') + `</select>`;
        
        // Order Direction (↑↓)
        html += `<button type="button" class="mf-order sort-btn" title="Change Ascending/Descending"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="${this.state.sort_order === 'ASC' ? 'M12 19V5M5 12l7-7 7 7' : 'M12 5v14M19 12l-7 7-7-7'}"/></svg></button>`;
        
        // Status Filter
        
        
        html += `</div>`;
        tb.innerHTML = html;

        const self = this;
        let timer;
        tb.querySelector('.mf-search').addEventListener('input', function() {
            clearTimeout(timer);
            timer = setTimeout(() => { self.state.search = this.value; self.state.page = 1; self.load(); }, 400);
        });
        tb.querySelector('.mf-sort').addEventListener('change', function() {
            self.state.sort_by = this.value; self.state.page = 1; self.load();
        });
        tb.querySelector('.mf-order').addEventListener('click', function() {
            self.state.sort_order = self.state.sort_order === 'ASC' ? 'DESC' : 'ASC';
            this.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="${self.state.sort_order === 'ASC' ? 'M12 19V5M5 12l7-7 7 7' : 'M12 5v14M19 12l-7 7-7-7'}"/></svg>`;
            self.load();
        });
        tb.querySelector('.mf-status').addEventListener('change', function() {
            self.state.status = this.value; self.state.page = 1; self.load();
        });
    }

    async load() {
        const c = this.cfg, s = this.state;
        const params = new URLSearchParams({ 
            list: '1', 
            search: s.search, 
            sort_by: s.sort_by, 
            sort_order: s.sort_order, 
            page: s.page,
            status: s.status
        });

        const tbody = document.getElementById(c.tbodyId);
        if (!tbody) return;
        const seq = ++this._seq;

        try {
            const res = await fetch(`${c.api}?${params.toString()}`);
            const data = await res.json();
            
            if (seq !== this._seq) return; 

            if (!data.data || data.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="${c.colspan}" style="text-align:center;padding:30px;color:#94a3b8;">No data available.</td></tr>`;
            } else {
                const startNo = (data.current_page - 1) * c.perPage;
                tbody.innerHTML = data.data.map((r, i) => c.renderRow(r, startNo + i + 1)).join('');
            }
            this.renderPagination(data.current_page || 1, data.total_pages || 1);
        } catch (e) {
            console.error('UserMasterFilter Error:', e);
            tbody.innerHTML = `<tr><td colspan="${c.colspan}" style="text-align:center;padding:20px;color:red;">Error connecting to server.</td></tr>`;
        }
    }

    renderPagination(cur, total) {
        const pag = document.getElementById(this.cfg.pagId);
        if (!pag) return;
        let h = '';
        const addP = (p, lbl, active = false, dis = false) => 
            dis ? `<span class="page-link disabled">${lbl}</span>` : `<a href="javascript:void(0)" class="page-link ${active?'active':''}" data-p="${p}">${lbl}</a>`;

        h += addP(cur - 1, '&lt;', false, cur === 1);
        for (let i = 1; i <= total; i++) {
            if (i === 1 || i === total || (i >= cur - 2 && i <= cur + 2)) {
                h += addP(i, i, i === cur);
            } else if (i === cur - 3 || i === cur + 3) {
                h += `<span class="dots">...</span>`;
            }
        }
        h += addP(cur + 1, '&gt;', false, cur === total);
        pag.innerHTML = h;
        pag.querySelectorAll('a[data-p]').forEach(a => a.onclick = () => this.gotoPage(parseInt(a.dataset.p)));
    }
}
function resolveProfilePath(filename) {
    if (!filename) return '/cardhaven/assets/image/user.svg';

    return `/cardhaven/assets/image/image-profile/${filename}`;
}


function handleImageError(img) {
    const defaultImg = '/cardhaven/assets/image/user.svg';

    // Foto profil hanya tersimpan di assets/image/image-profile/. Kalau gagal dimuat,
    // langsung pakai avatar default.
    if (img.src !== window.location.origin + defaultImg) {
        img.src = defaultImg;
    }
}

// Global Helper (agar bisa dipakai di scriptAdmin.js dkk)
window.mfEsc = (v) => String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
window.mfStatusPill = (val) => {
    const active = parseInt(val) === 1;
    return `<span style="color:${active ? '#27AE60' : '#E74C3C'}; font-weight:700;">${active ? 'Active' : 'Inactive'}</span>`;
};