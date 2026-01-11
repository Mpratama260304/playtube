import './bootstrap';

import Alpine from 'alpinejs';
import Hls from 'hls.js';

// Make HLS.js globally available
window.Hls = Hls;

// Import PlayTube Player
import './playtube-player';

// Theme Store
Alpine.store('theme', {
    mode: localStorage.getItem('theme') || 'dark',
    
    toggle() {
        this.mode = this.mode === 'dark' ? 'light' : 'dark';
        localStorage.setItem('theme', this.mode);
    },
    
    init() {
        // Set initial theme from localStorage or system preference
        if (!localStorage.getItem('theme')) {
            this.mode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
    }
});

window.Alpine = Alpine;

Alpine.start();
