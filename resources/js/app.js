import './theme.js';
import './bootstrap';

import Alpine from 'alpinejs';
import * as bootstrap from 'bootstrap/dist/js/bootstrap.bundle.min.js';

window.Alpine = Alpine;
// dipakai script inline (tooltip, modal) lewat variabel global
window.bootstrap = bootstrap;

Alpine.start();