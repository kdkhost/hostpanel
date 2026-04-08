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
        if (window.HostPanel?.confirm) {
            return window.HostPanel.confirm({ text: message });
        }

        return window.confirm(message);
    },

    notify(message, type = 'success') {
        if (window.HostPanel?.toast) {
            window.HostPanel.toast(message, type);
        }
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
