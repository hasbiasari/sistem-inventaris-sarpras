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

// Chart.js nyimpen warna tiap chart pas dibikin (bukan baca Chart.defaults tiap render),
// jadi kalau tema di-toggle tanpa reload halaman, chart yang udah kebentuk duluan tetep
// pakai warna tema lama -> teksnya jadi nyaris gak keliatan. Update manual tiap chart di sini.
function refreshChartColors(theme) {
    if (typeof window.Chart === 'undefined' || !window.Chart.instances) {
        return;
    }

    const color = theme === 'dark' ? '#e9ecef' : '#495057';
    const gridColor = theme === 'dark' ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

    window.Chart.defaults.color = color;
    window.Chart.defaults.borderColor = gridColor;

    Object.values(window.Chart.instances).forEach((chart) => {
        chart.options.color = color;
        if (chart.options.scales) {
            Object.values(chart.options.scales).forEach((scale) => {
                scale.ticks = { ...(scale.ticks || {}), color };
                scale.grid = { ...(scale.grid || {}), color: gridColor };
            });
        }
        chart.update();
    });
}

function setTheme(theme, save = true) {
    document.documentElement.setAttribute('data-theme', theme);
    // Mazer pakai class "theme-dark" di body, bukan atribut data-theme
    document.body.classList.toggle('theme-dark', theme === 'dark');
    document.body.classList.toggle('theme-light', theme !== 'dark');
    if (save) {
        localStorage.setItem('theme', theme);
    }
    updateThemeToggleButton(theme);
    refreshChartColors(theme);
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