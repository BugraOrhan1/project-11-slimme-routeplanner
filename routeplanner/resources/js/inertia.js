import { h, reactive } from 'vue';
import { page } from './mockData';

export const Head = {
    props: { title: String },
    mounted() { if (this.title) document.title = `${this.title} – Klink Routeplanner`; },
    updated() { if (this.title) document.title = `${this.title} – Klink Routeplanner`; },
    render() { return null; },
};

export const Link = {
    props: { href: { type: String, required: true } },
    setup(props, { slots }) {
        const navigate = (event) => {
            if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            window.history.pushState({}, '', props.href);
            window.dispatchEvent(new PopStateEvent('popstate'));
        };
        return () => h('a', { href: props.href, onClick: navigate }, slots.default?.());
    },
    methods: {
        navigate(event) {
            if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            window.history.pushState({}, '', this.href);
            window.dispatchEvent(new PopStateEvent('popstate'));
        },
    },
};

function navigate(href) {
    window.history.pushState({}, '', href);
    window.dispatchEvent(new PopStateEvent('popstate'));
}

export const router = { get: navigate, post: navigate, put: navigate, delete: navigate };
export function usePage() { return page; }

export function useForm(initial) {
    const form = reactive({ ...initial, errors: {}, processing: false });
    form.data = () => Object.fromEntries(Object.entries(form).filter(([key]) => !['errors', 'processing', 'data', 'reset', 'clearErrors', 'transform', 'post', 'put'].includes(key)));
    form.reset = (...keys) => (keys.length ? keys.forEach((key) => (form[key] = initial[key] ?? '')) : Object.assign(form, initial));
    form.clearErrors = () => (form.errors = {});
    form.transform = (callback) => { form._transform = callback; return form; };
    form.post = (href, options = {}) => { options.onSuccess?.(); navigate(href); };
    form.put = (href, options = {}) => { options.onSuccess?.(); navigate(href); };
    return form;
}

export function createInertiaApp() { throw new Error('The standalone frontend does not use Inertia bootstrapping.'); }