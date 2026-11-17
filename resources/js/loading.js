/**
 * Feedback loading untuk halaman render-server (Blade).
 *
 * - Tombol submit: `data-loading-text="Menyimpan…"` → spinner + label, form dikunci dari submit ganda.
 * - Region: elemen pemicu (atau leluhurnya) ber-`data-busy-target="#id"` menandai region itu aria-busy;
 *   di dalam region, `[data-busy-show]` (mis. skeleton) ditampilkan dan `[data-busy-hide]` disembunyikan.
 * - Navigasi biasa: garis progres tipis di atas, baru muncul bila halaman belum terbuka setelah 250 ms
 *   (koneksi cepat tidak berkedip).
 * Semua status dipulihkan pada `pageshow` (termasuk kembali lewat tombol Back / bfcache).
 * Script inline (checkout, Snap) memakai `window.ReloopLoading` untuk pola yang sama.
 */

const NAV_DELAY = 250;
const NAV_TIMEOUT = 20000; // navigasi yang dibatalkan/terhenti: indikator tidak boleh tersangkut
const SPINNER = '<svg class="reloop-spin size-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">'
    + '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3"/>'
    + '<path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>';

let navTimer;
let navTimeout;

function live() {
    let el = document.getElementById('reloop-live');
    if (!el) {
        el = Object.assign(document.createElement('div'), { id: 'reloop-live', className: 'sr-only' });
        el.setAttribute('aria-live', 'polite');
        document.body.append(el);
    }
    return el;
}

function announce(text) {
    live().textContent = text;
}

function navBar() {
    let el = document.getElementById('reloop-nav');
    if (!el) {
        el = Object.assign(document.createElement('div'), { id: 'reloop-nav', className: 'reloop-nav', hidden: true });
        el.setAttribute('role', 'progressbar');
        el.setAttribute('aria-label', 'Memuat halaman');
        document.body.append(el);
    }
    return el;
}

function startNav(label = 'Memuat halaman…') {
    clearTimeout(navTimer);
    clearTimeout(navTimeout);
    navTimer = setTimeout(() => {
        navBar().hidden = false;
        // Region yang sedang memuat sudah mengumumkan labelnya sendiri.
        if (!document.querySelector('[aria-busy="true"]:not(form)')) announce(label);
    }, NAV_DELAY);
    navTimeout = setTimeout(stopNav, NAV_TIMEOUT);
}

function stopNav() {
    clearTimeout(navTimer);
    clearTimeout(navTimeout);
    const el = document.getElementById('reloop-nav');
    if (el) el.hidden = true;
}

function busyRegion(trigger) {
    const host = trigger.closest('[data-busy-target]');
    if (!host) return;
    host.closest('dialog[open]')?.close();
    showRegion(document.querySelector(host.dataset.busyTarget));
}

/** Tandai region sedang memuat: skeleton tampil, konten lama disembunyikan. */
function showRegion(region) {
    if (!region || region.getAttribute('aria-busy') === 'true') return;
    region.setAttribute('aria-busy', 'true');
    region.querySelectorAll('[data-busy-show], [data-busy-hide]').forEach((el) => {
        el.dataset.busyWasHidden = String(el.hidden);
        el.hidden = el.hasAttribute('data-busy-hide');
    });
    announce(region.dataset.busyLabel || 'Memuat…');
}

function idleRegions() {
    document.querySelectorAll('[aria-busy="true"]').forEach((region) => {
        region.removeAttribute('aria-busy');
        region.querySelectorAll('[data-busy-was-hidden]').forEach((el) => {
            el.hidden = el.dataset.busyWasHidden === 'true';
            delete el.dataset.busyWasHidden;
        });
    });
}

/**
 * Tombol/link masuk mode proses: dinonaktifkan, spinner + label proses. Aman dipanggil berulang.
 * text null/undefined: hanya dinonaktifkan. text '' : spinner saja (tombol ikon, label tetap dari aria-label).
 */
function busy(button, text = button?.dataset.loadingText) {
    if (!button || button.dataset.busyHtml !== undefined) return;
    button.dataset.busyHtml = button.innerHTML;
    button.disabled = true;
    button.setAttribute('aria-disabled', 'true');
    if (button.tagName === 'A') button.style.pointerEvents = 'none';
    if (text != null) {
        button.classList.add('reloop-busy');
        button.innerHTML = SPINNER;
        if (text) {
            button.append(Object.assign(document.createElement('span'), { textContent: text }));
            announce(text);
        }
    }
}

/** Kembalikan tombol ke keadaan semula (sukses tanpa pindah halaman, gagal, atau dibatalkan). */
function idle(button) {
    if (!button || button.dataset.busyHtml === undefined) return;
    button.innerHTML = button.dataset.busyHtml;
    delete button.dataset.busyHtml;
    button.classList.remove('reloop-busy');
    button.disabled = false;
    button.removeAttribute('aria-disabled');
    button.style.pointerEvents = '';
}

function resetAll() {
    stopNav();
    announce('');
    idleRegions();
    document.querySelectorAll('[data-busy-html]').forEach(idle);
    document.querySelectorAll('form[aria-busy="true"]').forEach((form) => form.removeAttribute('aria-busy'));
}

document.addEventListener('submit', (event) => {
    const form = event.target;
    // Handler lain (checkout async, validasi JS, Livewire wire:submit) sudah mengambil alih.
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented || form.matches('[data-login-form]')) return;
    if (form.target && form.target !== '_self') return;

    if (form.getAttribute('aria-busy') === 'true') {
        event.preventDefault(); // cegah submit ganda
        return;
    }
    form.setAttribute('aria-busy', 'true');

    const submitter = event.submitter ?? form.querySelector('[type="submit"]');
    const buttons = [...form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]')]
        .concat(form.id ? [...document.querySelectorAll(`[form="${form.id}"][type="submit"]`)] : []);
    // Form tanpa tombol (mis. unggah foto saat file dipilih) memakai elemen ber-data-loading-text di dalamnya.
    const label = submitter ?? form.querySelector('[data-loading-text]');
    // Nonaktifkan setelah data form dibentuk, supaya nilai tombol (name/value) tetap ikut terkirim.
    setTimeout(() => {
        buttons.forEach((b) => (b === label ? busy(b) : busy(b, null)));
        if (label && !buttons.includes(label)) busy(label);
    });

    busyRegion(submitter ?? form);
    startNav();
});

document.addEventListener('click', (event) => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    const link = event.target.closest('a[href]');
    if (!link || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;

    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
    const url = new URL(link.href, location.href);
    if (url.origin !== location.origin) return;
    if (url.pathname === location.pathname && url.search === location.search && url.hash) return;

    if (link.dataset.loadingText !== undefined) busy(link);
    busyRegion(link);
    startNav();
});

window.addEventListener('pageshow', resetAll);

window.ReloopLoading = { busy, idle, announce, startNav, stopNav, region: (selector) => showRegion(document.querySelector(selector)) };
