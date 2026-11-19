// Hanya interaksi mockup 4.1.1. Form asli berasal dari reloop-ecommerce-flow.
const categorySlugs = ['perabot', 'pakaian', 'buku', 'elektronik', 'mainan', 'sepeda', 'lainnya'];
const chosenCategory = new URLSearchParams(location.search).get('kategori');
const categoryInputs = [...document.querySelectorAll('input[name="kat"]')];
categoryInputs.forEach((input, index) => {
  if (!categorySlugs[index]) {
    input.closest('label').remove();
    return;
  }
  input.value = categorySlugs[index];
  input.checked = input.value === chosenCategory;
});
document.querySelector('header a').href = 'pilih-kategori.html?tipe=bekas';
