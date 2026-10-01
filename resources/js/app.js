document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy-link]');

    if (!button) {
        return;
    }

    const status = button.parentElement.querySelector('[data-copy-status]');

    try {
        await navigator.clipboard.writeText(button.dataset.copyUrl);
        status.textContent = 'Job link copied.';
        button.textContent = 'Copied';
    } catch {
        status.textContent = 'Copy is unavailable in this browser. Use one of the share links instead.';
    }
});
