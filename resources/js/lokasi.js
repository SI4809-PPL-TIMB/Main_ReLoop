const form = document.getElementById('form-lokasi');
const LEVELS = ['provinsi', 'kabupaten', 'kecamatan', 'kelurahan'];
const rows = [...document.querySelectorAll('#daerah > div')];
const hidden = document.getElementById('kelurahan_id');
const pick = LEVELS.map((l) => JSON.parse(form.dataset.pilihan || 'null')?.[l] ?? null);
const cache = {};

const getJson = (url) => fetch(url, { headers: { Accept: 'application/json' } }).then((r) => (r.ok ? r.json() : Promise.reject(r)));
const esc = (s) => s.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

async function opsi(lv) {
    const parent = lv ? pick[lv - 1].id : '';
    const url = `${form.dataset.urlAnak}/${LEVELS[lv]}?parent=${parent}`;
    return (cache[url] ??= await getJson(url));
}

async function render(open) {
    for (const [lv, row] of rows.entries()) {
        const [btn, list] = row.children;
        const v = btn.querySelector('[data-v]');
        btn.disabled = lv > 0 && !pick[lv - 1];
        btn.setAttribute('aria-expanded', lv === open);
        v.textContent = pick[lv]?.nama ?? 'Pilih';
        v.classList.toggle('text-slate-400', !pick[lv]);
        list.hidden = lv !== open;
        list.innerHTML = '';
        if (lv !== open) continue;
        for (const o of await opsi(lv)) {
            list.insertAdjacentHTML('beforeend', `<label class="flex cursor-pointer items-center justify-between rounded-xl px-3 py-2.5 has-[:checked]:bg-slate-50"><span class="text-sm font-medium">${esc(o.nama)}</span><input type="radio" name="lv${lv}" value="${o.id}" data-nama="${esc(o.nama)}" ${pick[lv]?.id === o.id ? 'checked' : ''}></label>`);
        }
    }
    hidden.value = pick[3]?.id ?? '';
}

document.getElementById('daerah').addEventListener('click', (e) => {
    const b = e.target.closest('button');
    if (b) render(b.getAttribute('aria-expanded') === 'true' ? -1 : rows.indexOf(b.parentElement));
});
document.getElementById('daerah').addEventListener('change', (e) => {
    const lv = Number(e.target.name.slice(2));
    pick[lv] = { id: Number(e.target.value), nama: e.target.dataset.nama };
    pick.fill(null, lv + 1);
    render(lv < 3 ? lv + 1 : -1);
});

function setLokasi(h) {
    LEVELS.forEach((l, i) => (pick[i] = h[l]));
    render(-1);
}

form.addEventListener('submit', (e) => {
    if (!hidden.value) {
        e.preventDefault();
        render(pick.findIndex((p) => !p));
    }
});

// Cari wilayah
const q = document.getElementById('cari');
const hasil = document.getElementById('cari-hasil');
let timer;
q.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(async () => {
        if (q.value.trim().length < 2) return (hasil.hidden = true);
        const rowsData = await getJson(`${form.dataset.urlCari}?q=${encodeURIComponent(q.value.trim())}`);
        hasil.innerHTML = rowsData.length
            ? rowsData.map((h, i) => `<li><button type="button" data-i="${i}" class="w-full px-4 py-2.5 text-left text-sm hover:bg-slate-50">${esc(h.label)}</button></li>`).join('')
            : '<li class="px-4 py-3 text-sm text-slate-500">Lokasi tidak ditemukan. Coba nama desa atau kecamatan lain.</li>';
        hasil.hidden = false;
        hasil._data = rowsData;
    }, 250);
});
hasil.addEventListener('click', (e) => {
    const b = e.target.closest('button');
    if (!b) return;
    setLokasi(hasil._data[b.dataset.i]);
    hasil.hidden = true;
    q.value = '';
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
            r.ok ? setLokasi(await r.json()) : gagal((await r.json()).message ?? 'Lokasi tidak terbaca. Pilih daerah secara manual.');
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

render(-1);
