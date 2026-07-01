import { ref } from 'vue';

/**
 * Debounced JSON search against a URL that accepts a `?search=` param.
 * Returns reactive `results`/`loading` plus `search(term)` and `reset()`.
 */
export function useDebouncedSearch(searchUrl, { delay = 250 } = {}) {
    const results = ref([]);
    const loading = ref(false);
    let debounce_timer = null;

    const runFetch = async (term) => {
        loading.value = true;
        try {
            const url = new URL(searchUrl, window.location.origin);
            if (term) {
                url.searchParams.set('search', term);
            }
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            results.value = response.ok ? await response.json() : [];
        } catch (error) {
            results.value = [];
        } finally {
            loading.value = false;
        }
    };

    const search = (term = '') => {
        clearTimeout(debounce_timer);
        debounce_timer = setTimeout(() => runFetch(term), delay);
    };

    const reset = () => {
        clearTimeout(debounce_timer);
        results.value = [];
    };

    return { results, loading, search, reset };
}
