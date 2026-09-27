import './bootstrap';

import Alpine from 'alpinejs';
import * as lucide from 'lucide';
import Chart from 'chart.js/auto';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Alpine untuk layout admin (sidebar, toast) & atribut x- di blade.
window.Alpine = Alpine;
Alpine.start();

// Ikon Lucide untuk semua konten statis + refresh setelah Livewire morph.
window.lucide = lucide;
function refreshIcons() {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons({ icons: window.lucide.icons });
    }
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', refreshIcons);
} else {
    refreshIcons();
}
document.addEventListener('livewire:navigated', refreshIcons);
document.addEventListener('livewire:morph-updated', refreshIcons);
document.addEventListener('alpine:initialized', refreshIcons);

// Chart.js global untuk dashboard & halaman hasil.
window.Chart = Chart;

// Kelas Echo/Pusher global; halaman dashboard meng-init dengan config server.
window.Pusher = Pusher;
if (!window.Echo || typeof window.Echo !== 'function') {
    window.Echo = Echo;
}
