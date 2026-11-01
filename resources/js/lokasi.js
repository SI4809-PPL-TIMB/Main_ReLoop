const form = document.getElementById('form-lokasi');
const LEVELS = ['provinsi', 'kabupaten', 'kecamatan', 'kelurahan'];
const JUDUL = ['Pilih provinsi', 'Pilih kota/kabupaten', 'Pilih kecamatan', 'Pilih desa/kelurahan'];
const pick = LEVELS.map((l) => JSON.parse(form.dataset.pilihan || 'null')?.[l] ?? null);
const cache = {};

const hidden = document.getElementById('kelurahan_id');
const nilai = document.getElementById('daerah-nilai');
const sheet = document.getElementById('sheet');
const daftar = document.getElementById('sheet-daftar');
const judul = document.getElementById('sheet-judul');
const jejak = document.getElementById('sheet-jejak');
const back = document.getElementById('sheet-back');
const q = document.getElementById('cari');
let step = 0;

const getJson = (url) => fetch(url, { headers: { Accept: 'application/json' } }).then((r) => (r.ok ? r.json() : Promise.reject(r)));
const esc = (s) => s.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
const chev = '<svg class="size-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>';
const baris = (html, attrs) => `<li><button type="button" ${attrs} class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-3 text-left text-sm font-medium hover:bg-slate-50">${html}</button></li>`;

function tampilkanNilai() {
    const nama = pick.filter(Boolean).map((p) => p.nama).reverse().join(', ');
    nilai.textContent = pick[3] ? nama : 'Pilih daerah';
    nilai.classList.toggle('text-slate-400', !pick[3]);
    hidden.value = pick[3]?.id ?? '';
}

async function render() {
    q.value = '';
    judul.textContent = JUDUL[step];
    back.hidden = step === 0;
    jejak.textContent = pick.slice(0, step).map((p) => p.nama).join(' › ');
    daftar.innerHTML = '<li class="px-3 py-3 text-sm text-slate-500">Memuat…</li>';
    const parent = step ? pick[step - 1].id : '';
    const url = `${form.dataset.urlAnak}/${LEVELS[step]}?parent=${parent}`;
    const data = (cache[url] ??= await getJson(url));
    daftar.innerHTML = data.map((o) => baris(`<span>${esc(o.nama)}</span>${pick[step]?.id === o.id ? '<span class="text-xs font-semibold text-brand-700">Dipilih</span>' : step < 3 ? chev : ''}`, `data-id="${o.id}" data-nama="${esc(o.nama)}"`)).join('');
    daftar.scrollTop = 0;
}

function buka() {
    step = pick[3] ? 3 : Math.max(pick.findIndex((p) => !p), 0);
    sheet.showModal();
    render();
}

function setLokasi(h) {
    LEVELS.forEach((l, i) => (pick[i] = h[l]));
    tampilkanNilai();
}

daftar.addEventListener('click', (e) => {
    const b = e.target.closest('button');
    if (!b) return;
    if (b.dataset.i !== undefined) { // hasil pencarian
        setLokasi(daftar._hasil[b.dataset.i]);
        return sheet.close();
    }
    pick[step] = { id: Number(b.dataset.id), nama: b.dataset.nama };
    pick.fill(null, step + 1);
    if (step < 3) {
        step++;
        return render();
    }
    tampilkanNilai();
    sheet.close();
});

back.addEventListener('click', () => { step--; render(); });
document.getElementById('sheet-tutup').addEventListener('click', () => sheet.close());
sheet.addEventListener('click', (e) => { if (e.target === sheet) sheet.close(); }); // klik backdrop
document.getElementById('daerah-btn').addEventListener('click', buka);

form.addEventListener('submit', (e) => {
    if (!hidden.value) {
        e.preventDefault();
        buka();
    }
});

// Cari wilayah di dalam sheet
let timer;
q.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(async () => {
        if (q.value.trim().length < 2) return render();
        const hasil = await getJson(`${form.dataset.urlCari}?q=${encodeURIComponent(q.value.trim())}`);
        daftar._hasil = hasil;
        daftar.innerHTML = hasil.length
            ? hasil.map((h, i) => baris(`<span>${esc(h.label)}</span>`, `data-i="${i}"`)).join('')
            : '<li class="px-3 py-3 text-sm text-slate-500">Lokasi tidak ditemukan. Coba nama desa atau kecamatan lain.</li>';
    }, 250);
});

// Gunakan lokasi saat ini
const gps = document.getElementById('gps');
const gpsError = document.getElementById('gps-error');
const gpsLabel = gps.textContent;
const gagal = (msg) => { gpsError.textContent = msg; gpsError.hidden = false; };

gps.addEventListener('click', () => {
    gpsError.hidden = true;
    if (!navigator.geolocation) return gagal('Browser tidak mendukung lokasi. Pilih daerah secara manual.');
    gps.disabled = true;
    gps.textContent = 'Mencari lokasi…';
    navigator.geolocation.getCurrentPosition(async ({ coords }) => {
        document.getElementById('latitude').value = coords.latitude;
        document.getElementById('longitude').value = coords.longitude;
        try {
            const r = await fetch(form.dataset.urlGps, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name=_token]').value },
                body: JSON.stringify({ latitude: coords.latitude, longitude: coords.longitude }),
            });
            r.ok ? (setLokasi(await r.json()), sheet.close()) : gagal((await r.json()).message ?? 'Lokasi tidak terbaca. Pilih daerah secara manual.');
        } catch {
            gagal('Lokasi tidak terbaca. Pilih daerah secara manual.');
        }
        gps.disabled = false;
        gps.textContent = gpsLabel;
    }, () => {
        gagal('Izin lokasi ditolak. Aktifkan izin lokasi di pengaturan browser, atau pilih daerah secara manual di bawah.');
        gps.disabled = false;
        gps.textContent = gpsLabel;
    }, { timeout: 10000 });
});

tampilkanNilai();
