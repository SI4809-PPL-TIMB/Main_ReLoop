import './bootstrap';

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-password-toggle]');
    if (!button) return;

    const input = document.getElementById(button.getAttribute('aria-controls'));
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(visible));
    button.setAttribute('aria-label', `${visible ? 'Sembunyikan' : 'Tampilkan'} kata sandi`);
    button.textContent = visible ? 'Sembunyikan' : 'Tampilkan';
});
