// atur mode gelap/terang, simpan pilihan biar keinget terus
function updateThemeToggleButton(theme) {
    const button = document.querySelector('.guest-theme-toggle');
    if (!button) {
        return;
    }

    button.dataset.theme = theme;
    button.setAttribute('aria-label', theme === 'dark' ? 'Mode gelap aktif' : 'Mode terang aktif');

    const label = button.querySelector('.theme-label');
    if (label) {
        label.textContent = theme === 'dark' ? 'Gelap' : 'Terang';
    }

    const icon = button.querySelector('.theme-icon');
    if (icon) {
        icon.textContent = theme === 'dark' ? '🌙' : '☀️';
    }
}

function setTheme(theme, save = true) {
    document.documentElement.setAttribute('data-theme', theme);
    // app.css/app-dark.css bawaan Mazer aktif lewat class "theme-dark" di body,
    // bukan lewat atribut data-theme, jadi keduanya perlu disetel bareng
    document.body.classList.toggle('theme-dark', theme === 'dark');
    document.body.classList.toggle('theme-light', theme !== 'dark');
    if (save) {
        localStorage.setItem('theme', theme);
    }
    updateThemeToggleButton(theme);
}

function initTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme, false);
}

function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    setTheme(next);
}

initTheme();

window.toggleTheme = toggleTheme;