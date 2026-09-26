import '../css/app.css';

import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Add CSRF token to axios requests
document.addEventListener('DOMContentLoaded', () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    }
});

// Listen for Inertia page changes to update CSRF token
window.addEventListener('inertia:finish', () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    }
});