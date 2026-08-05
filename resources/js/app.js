import './theme.js';
import './bootstrap';

import Alpine from 'alpinejs';
import * as bootstrap from 'bootstrap/dist/js/bootstrap.bundle.min.js';

window.Alpine = Alpine;
// beberapa script inline (tooltip, modal hapus akun profile) manggil "bootstrap.Tooltip"/
// "bootstrap.Modal" langsung sebagai variabel global, jadi exports-nya perlu ditempel ke window
window.bootstrap = bootstrap;

Alpine.start();