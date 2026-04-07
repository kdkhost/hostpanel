import axios from 'axios';

// Configure axios for admin area
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

const csrfMeta = document.querySelector('meta[name="csrf-token"]');
if (csrfMeta) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfMeta.getAttribute('content');
}

// Global admin utilities
window.AdminHP = {
    axios,

    async confirm(message) {
        return window.confirm(message);
    },

    notify(message, type = 'success') {
        const icons = { success: '✅', danger: '❌', warning: '⚠️', info: 'ℹ️' };
        const colors = { success: '#10b981', danger: '#ef4444', warning: '#f59e0b', info: '#3b82f6' };
        const el = document.createElement('div');
        el.style.cssText = `
            position: fixed; bottom: 1.25rem; right: 1.25rem; z-index: 9999;
            background: white; border-left: 4px solid ${colors[type] || colors.info};
            box-shadow: 0 10px 25px rgba(0,0,0,.15); border-radius: .75rem;
            padding: .875rem 1.25rem; max-width: 320px; font-size: .875rem;
            display: flex; align-items: center; gap: .5rem; animation: slideIn .3s ease;
        `;
        el.innerHTML = `<span>${icons[type] || ''}</span><span>${message}</span>`;
        document.body.appendChild(el);

        const style = document.createElement('style');
        style.textContent = '@keyframes slideIn { from { transform: translateX(100%); opacity:0 } to { transform: translateX(0); opacity:1 } }';
        document.head.appendChild(style);

        setTimeout(() => el.remove(), 4500);
    },

    formatMoney(value, currency = 'BRL') {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency }).format(value || 0);
    },

    formatDate(dateStr, format = 'dd/MM/yyyy') {
        if (!dateStr) return '—';
        return new Date(dateStr).toLocaleDateString('pt-BR');
    },

    formatDateTime(dateStr) {
        if (!dateStr) return '—';
        return new Date(dateStr).toLocaleString('pt-BR');
    },
};

export default window.AdminHP;
