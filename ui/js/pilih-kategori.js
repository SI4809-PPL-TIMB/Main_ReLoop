// Prototipe: pilih tipe, kategori detail pada halaman tujuan.
const destinations = {
  bekas: {label: 'Barang Bekas', button: 'Lanjut ke Pasang Barang', url: 'pasang-barang.html'},
  daur_ulang: {label: 'Daur Ulang', button: 'Lanjut ke Mitra Pengepul', url: 'pengepul-sementara.html'}
};
const form = document.getElementById('pilih-kategori');
const next = document.getElementById('lanjut');
form.addEventListener('change', () => {
  const d = destinations[form.elements.tipe.value];
  next.disabled = !d;
  next.textContent = d?.button ?? 'Lanjutkan';
  document.getElementById('ringkasan').textContent = d?.label ?? 'Pilih jenis barang untuk melanjutkan.';
});
form.addEventListener('submit', event => {
  event.preventDefault();
  const type = form.elements.tipe.value;
  if (Object.hasOwn(destinations, type)) location.href = destinations[type].url + '?tipe=' + type;
});
const previous = new URLSearchParams(location.search).get('tipe');
const input = [...form.elements.tipe].find(input => input.value === previous);
if (input) { input.checked = true; form.dispatchEvent(new Event('change')); }
