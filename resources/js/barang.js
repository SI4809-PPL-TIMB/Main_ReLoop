// Form pasang/ubah barang: langkah per layar, validasi ringan per langkah, pratinjau foto.
const form = document.getElementById('form-barang');
// Satu langkah bisa berisi beberapa <section data-step="n">; jumlah langkah = nilai data-step terbesar.
const sections = [...form.querySelectorAll('[data-step]')];
const total = Math.max(...sections.map((s) => Number(s.dataset.step)));
const bagian = (i) => sections.filter((s) => Number(s.dataset.step) === i + 1);
const judul = (i) => bagian(i).find((s) => s.dataset.judul)?.dataset.judul ?? '';
const next = document.getElementById('next');
let now = 0;

function show(i) {
    now = i;
    sections.forEach((el) => el.classList.toggle('hidden', Number(el.dataset.step) !== i + 1));
    document.getElementById('bar-isi').style.width = `${((i + 1) / total) * 100}%`;
    document.getElementById('bar-teks').textContent = `Langkah ${i + 1} dari ${total}`;
    document.getElementById('bar').setAttribute('aria-valuenow', i + 1);
    document.getElementById('prev').classList.toggle('hidden', i === 0);
    next.classList.toggle('hidden', i === total - 1);
    if (i < total - 1) next.textContent = `Lanjut: ${judul(i + 1)}`;
    document.getElementById('kirim').classList.toggle('hidden', i !== total - 1);
    window.scrollTo(0, 0);
}

const cek = {
    foto: () => (!form.querySelector('[data-lama] input:not(:checked)') && !fotoBaru.length ? 'Tambahkan minimal 1 foto.' : !form.querySelector('[name=category_id]:checked') ? 'Pilih kategori barang.' : null),
    info: () => (form.title.value.trim().length < 5 ? 'Judul minimal 5 karakter.' : !form.price.value.trim() ? 'Harga wajib diisi.' : null),
    kondisi: () => (!form.querySelector('[name=condition]:checked') ? 'Pilih kondisi barang.' : null),
};

function langkahValid(i) {
    document.querySelectorAll('[data-js-err]').forEach((e) => e.remove());
    let valid = true;
    bagian(i).forEach((section) => {
        const pesan = cek[section.dataset.cek]?.();
        if (pesan) {
            section.insertAdjacentHTML('beforeend', `<p data-js-err role="alert" class="text-sm text-red-600">${pesan}</p>`);
            valid = false;
        }
    });
    return valid;
}

next.addEventListener('click', () => langkahValid(now) && show(now + 1));
document.getElementById('prev').addEventListener('click', () => show(now - 1));
document.getElementById('back').addEventListener('click', (e) => { if (now > 0) { e.preventDefault(); show(now - 1); } });
form.addEventListener('submit', (e) => {
    if (e.submitter?.value === 'draft') return;
    const gagal = [...Array(total).keys()].find((i) => !langkahValid(i));
    if (gagal !== undefined) { e.preventDefault(); show(gagal); }
});

// Foto: kumpulkan pilihan ke DataTransfer supaya bisa ditambah bertahap dan dihapus satu per satu.
const input = document.getElementById('foto-input');
const grid = document.getElementById('fotos');
const maks = Number(grid.dataset.maks);
let fotoBaru = [];
const lama = () => grid.querySelectorAll('[data-lama] input:not(:checked)').length;

function renderFoto() {
    grid.querySelectorAll('[data-baru]').forEach((el) => el.remove());
    const dt = new DataTransfer();
    fotoBaru.forEach((f, i) => {
        dt.items.add(f);
        grid.querySelector('#foto-add').insertAdjacentHTML('beforebegin', `<div data-baru class="relative aspect-square overflow-hidden rounded-xl bg-slate-100"><img src="${URL.createObjectURL(f)}" alt="" class="size-full object-cover"><button type="button" data-hapus="${i}" aria-label="Hapus foto" class="absolute right-1 top-1 grid size-5 place-items-center rounded-full bg-ink/80 text-white"><svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button></div>`);
    });
    input.files = dt.files;
    grid.querySelector('#foto-add').hidden = lama() + fotoBaru.length >= maks;
}
// Tambah > pilih sumber: Ambil foto (kamera langsung, capture) atau galeri.
const sheet = document.getElementById('sumber-foto');
const kamera = document.getElementById('foto-kamera');
const galeri = document.getElementById('foto-galeri');
const kameraError = document.getElementById('foto-kamera-error');

async function izinKameraDitolak() {
    try {
        return (await navigator.permissions?.query({ name: 'camera' }))?.state === 'denied';
    } catch {
        return false; // browser tanpa Permissions API untuk kamera: biarkan input capture yang menangani
    }
}

document.getElementById('foto-add').addEventListener('click', () => sheet.showModal());
sheet.addEventListener('click', (e) => { if (e.target === sheet || e.target.closest('[data-tutup]')) sheet.close(); });
sheet.querySelector('[data-kamera]').addEventListener('click', async () => {
    sheet.close();
    kameraError.hidden = !(await izinKameraDitolak());
    if (kameraError.hidden) kamera.click();
});
document.querySelectorAll('[data-galeri]').forEach((b) => b.addEventListener('click', () => {
    sheet.close();
    kameraError.hidden = true;
    galeri.click();
}));
[kamera, galeri].forEach((sumber) => sumber.addEventListener('change', () => {
    fotoBaru = [...fotoBaru, ...sumber.files].slice(0, Math.max(0, maks - lama()));
    sumber.value = '';
    renderFoto();
}));
grid.addEventListener('click', (e) => {
    const x = e.target.closest('[data-hapus]');
    if (x) { fotoBaru.splice(Number(x.dataset.hapus), 1); renderFoto(); }
});
grid.addEventListener('change', (e) => e.target.name === 'remove_photos[]' && renderFoto());

// Setelah error server, buka langkah yang berisi pesan error pertama.
const err = form.querySelector('[data-err]');
show(err ? Number(err.closest('[data-step]').dataset.step) - 1 : 0);
renderFoto();
