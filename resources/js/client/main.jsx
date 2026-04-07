import React from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';

// Configure axios defaults
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';
const csrfMeta = document.querySelector('meta[name="csrf-token"]');
if (csrfMeta) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfMeta.getAttribute('content');
}

// Mount React widgets (islands architecture — co-exists with Blade/Alpine)
const mounts = document.querySelectorAll('[data-react-component]');

mounts.forEach(async el => {
    const componentName = el.dataset.reactComponent;
    const props = el.dataset.props ? JSON.parse(el.dataset.props) : {};

    try {
        const module = await import(`./components/${componentName}.jsx`);
        const Component = module.default;
        createRoot(el).render(<Component {...props} />);
    } catch (e) {
        console.warn(`[HostPanel React] Component not found: ${componentName}`, e);
    }
});

export { axios };
