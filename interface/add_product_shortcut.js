// ════════════════════════════════════════════════════════════════════════════
// SHARED "ADD NEW PRODUCT" SHORTCUT
// ────────────────────────────────────────────────────────────────────────────
// Lets staff jump from the Add PO / Add Event / Edit Event modals straight to
// the Product page's Add Product modal, then return to the original modal with
// their input intact. Because dashboard navigation is full page reloads, the
// modal state travels through sessionStorage.
//
// Keys:
//   ch_ap_ctx  — the caller's context: { origin, returnUrl, state, ... }
//   ch_ap_new  — the product that was just saved (nama_produk + id_supplier),
//                so the origin page can look it up again and bring it back.
// ════════════════════════════════════════════════════════════════════════════
(function () {
    const CTX_KEY     = 'ch_ap_ctx';
    const NEW_KEY     = 'ch_ap_new';
    const PRODUCT_URL = '/CardHaven/dashboard/product';

    // Stash caller state + return URL, then go to the Product page. The Product
    // page detects ch_ap_ctx and auto-opens its Add Product modal.
    window.chStartAddProductShortcut = function (ctx) {
        try {
            ctx = ctx || {};
            if (!ctx.returnUrl) {
                ctx.returnUrl = window.location.pathname + window.location.search;
            }
            sessionStorage.setItem(CTX_KEY, JSON.stringify(ctx));
            sessionStorage.removeItem(NEW_KEY); // fresh trip — clear any stale product
        } catch (e) {
            console.error('[AddProductShortcut] failed to save context', e);
        }
        window.location.href = PRODUCT_URL;
    };

    window.chGetReturnCtx = function () {
        try { return JSON.parse(sessionStorage.getItem(CTX_KEY) || 'null'); }
        catch (e) { return null; }
    };

    window.chSetNewProduct = function (info) {
        try { sessionStorage.setItem(NEW_KEY, JSON.stringify(info)); }
        catch (e) { /* ignore */ }
    };

    window.chGetNewProduct = function () {
        try { return JSON.parse(sessionStorage.getItem(NEW_KEY) || 'null'); }
        catch (e) { return null; }
    };

    window.chClearShortcut = function () {
        sessionStorage.removeItem(CTX_KEY);
        sessionStorage.removeItem(NEW_KEY);
    };
})();
