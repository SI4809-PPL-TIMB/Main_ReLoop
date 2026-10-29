import './bootstrap';

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-password-toggle]');
    if (!button) return;

    const input = document.getElementById(button.getAttribute('aria-controls'));
    if (!(input instanceof HTMLInputElement)) return;
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(visible));
    button.setAttribute('aria-label', `${visible ? 'Sembunyikan' : 'Tampilkan'} kata sandi`);
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-login-form]')) return;

    if (form.getAttribute('aria-busy') === 'true') {
        event.preventDefault();
        return;
    }

    const button = form.querySelector('[data-login-submit]');
    if (!button) return;
    form.setAttribute('aria-busy', 'true');
    button.disabled = true;
    button.textContent = 'Sedang masuk…';
});

// Restore the form when navigating back from the browser's page cache.
window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-login-form]').forEach((form) => {
        form.removeAttribute('aria-busy');
        const button = form.querySelector('[data-login-submit]');
        if (!button) return;
        button.disabled = false;
        button.textContent = 'Masuk';
    });
});

document.querySelectorAll('form input[aria-invalid="true"]').forEach((input) => {
    input.addEventListener('input', () => input.removeAttribute('aria-invalid'), { once: true });
});
