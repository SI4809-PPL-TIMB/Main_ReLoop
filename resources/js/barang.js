// Form pasang/ubah barang: langkah per layar, validasi ringan per langkah, pratinjau foto.
const form = document.getElementById('form-barang');
const steps = [...form.querySelectorAll('[data-step]')];
let now = 0;

function show(i) {
    now = i;
    steps.forEach((el, k) => el.classList.toggle('hidden', k !== i));
    document.getElementById('bar-isi').style.width = `${((i + 1) / steps.length) * 100}%`;
    document.getElementById('bar-teks').textContent = `${i + 1}/${steps.length}`;
    document.getElementById('bar').setAttribute('aria-valuenow', i + 1);
    document.getElementById('prev').classList.toggle('hidden', i === 0);
    document.getElementById('next').classList.toggle('hidden', i === steps.length - 1);
    document.getElementById('kirim').classList.toggle('hidden', i !== steps.length - 1);
    window.scrollTo(0, 0);
}

function langkahValid(i) {
    const kosong = {
        0: () => !form.querySelector('[data-lama] input:not(:checked)') && !fotoBaru.length ? 'Tambahkan minimal 1 foto.' : !form.querySelector('[name=category_id]:checked') ? 'Pilih kategori barang.' : null,
        1: () => (form.title.value.trim().length < 5 ? 'Judul minimal 5 karakter.' : !form.price.value.trim() ? 'Harga wajib diisi.' : null),
        2: () => (!form.querySelector('[name=condition]:checked') ? 'Pilih kondisi barang.' : null),
        3: () => null,
    }[i]();
    document.querySelectorAll('[data-js-err]').forEach((e) => e.remove());
    if (kosong) steps[i].insertAdjacentHTML('beforeend', `<p data-js-err role="alert" class="text-sm text-red-600">${kosong}</p>`);
    return !kosong;
}

document.getElementById('next').addEventListener('click', () => langkahValid(now) && show(now + 1));
document.getElementById('prev').addEventListener('click', () => show(now - 1));
document.getElementById('back').addEventListener('click', (e) => { if (now > 0) { e.preventDefault(); show(now - 1); } });
form.addEventListener('submit', (e) => {
    if (e.submitter?.value === 'draft') return;
    const gagal = steps.findIndex((_, i) => !langkahValid(i));
    if (gagal >= 0) { e.preventDefault(); show(gagal); }
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
document.getElementById('foto-add').addEventListener('click', () => input.click());
input.addEventListener('change', () => {
    fotoBaru = [...fotoBaru, ...input.files].slice(0, Math.max(0, maks - lama()));
    renderFoto();
});
grid.addEventListener('click', (e) => {
    const x = e.target.closest('[data-hapus]');
    if (x) { fotoBaru.splice(Number(x.dataset.hapus), 1); renderFoto(); }
});
grid.addEventListener('change', (e) => e.target.name === 'remove_photos[]' && renderFoto());

// Setelah error server, buka langkah yang berisi pesan error pertama.
const err = form.querySelector('[data-err]');
show(err ? steps.findIndex((s) => s.contains(err)) : 0);
renderFoto();
