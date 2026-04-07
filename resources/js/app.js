import './bootstrap';
import axios from 'axios';

// Configure global axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;

// Global HostPanel utilities (used across all Blade views)
window.HostPanel = window.HostPanel || {
    csrfToken: token?.content ?? '',

    async fetch(url, options = {}) {
        const res = await fetch(url, {
            headers: {
                'X-CSRF-TOKEN': this.csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...(options.headers || {}),
            },
            ...options,
        });
        return res.json();
    },

    toast(message, type = 'success') {
        const colors = { success: '#10b981', danger: '#ef4444', warning: '#f59e0b', info: '#3b82f6' };
        const icons  = { success: '✅', danger: '❌', warning: '⚠️', info: 'ℹ️' };
        const el = document.createElement('div');
        el.style.cssText = `
            position:fixed;bottom:1.25rem;right:1.25rem;z-index:9999;
            background:white;border-left:4px solid ${colors[type] || colors.info};
            box-shadow:0 10px 25px rgba(0,0,0,.15);border-radius:.75rem;
            padding:.875rem 1.25rem;max-width:320px;font-size:.875rem;font-family:inherit;
            display:flex;align-items:center;gap:.5rem;
            animation:hpSlideIn .3s ease;
        `;
        el.innerHTML = `<span>${icons[type] || ''}</span><span>${message}</span>`;
        document.body.appendChild(el);

        if (!document.getElementById('hp-toast-styles')) {
            const s = document.createElement('style');
            s.id = 'hp-toast-styles';
            s.textContent = '@keyframes hpSlideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}';
            document.head.appendChild(s);
        }
        setTimeout(() => el.remove(), 4500);
    },

    formatMoney: (v, currency = 'BRL') =>
        new Intl.NumberFormat('pt-BR', { style: 'currency', currency }).format(v || 0),

    formatDate: (d) => d ? new Date(d).toLocaleDateString('pt-BR') : '—',
    formatDateTime: (d) => d ? new Date(d).toLocaleString('pt-BR') : '—',

    timeAgo(dateStr) {
        if (!dateStr) return '—';
        const diff = Math.floor((Date.now() - new Date(dateStr)) / 60000);
        if (diff < 1) return 'agora';
        if (diff < 60) return `${diff}min atrás`;
        if (diff < 1440) return `${Math.floor(diff / 60)}h atrás`;
        return `${Math.floor(diff / 1440)}d atrás`;
    },
};
