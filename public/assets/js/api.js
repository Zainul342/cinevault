/**
 * API Client - JWT-aware fetch wrapper
 */
const API = (() => {
    const BASE_URL = '/api';

    // Token management
    const getToken = () => localStorage.getItem('cv_token');
    const setToken = (token) => localStorage.setItem('cv_token', token);
    const clearToken = () => localStorage.removeItem('cv_token');

    /**
     * Core fetch wrapper with JWT and error handling
     */
    async function request(endpoint, options = {}) {
        const url = `${BASE_URL}${endpoint}`;
        const token = getToken();

        const config = {
            headers: {
                'Content-Type': 'application/json',
                ...(token && { 'Authorization': `Bearer ${token}` }),
                ...options.headers,
            },
            ...options,
        };

        // Convert body to JSON if object
        if (config.body && typeof config.body === 'object') {
            config.body = JSON.stringify(config.body);
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                // Handle 401 - clear token and redirect
                if (response.status === 401) {
                    clearToken();
                    window.dispatchEvent(new CustomEvent('auth:expired'));
                }
                throw { status: response.status, ...data };
            }

            return data;
        } catch (err) {
            if (err.status) throw err; // Already formatted
            throw { status: 0, error: 'NETWORK_ERROR', message: err.message };
        }
    }

    // HTTP method shortcuts
    const get = (endpoint, params = {}) => {
        const query = new URLSearchParams(params).toString();
        return request(`${endpoint}${query ? `?${query}` : ''}`);
    };

    const post = (endpoint, body) => request(endpoint, { method: 'POST', body });
    const put = (endpoint, body) => request(endpoint, { method: 'PUT', body });
    const del = (endpoint) => request(endpoint, { method: 'DELETE' });

    // Auth-specific helpers
    const auth = {
        async login(email, password) {
            const res = await post('/auth/login', { email, password });
            if (res.token) setToken(res.token);
            return res;
        },
        async register(name, email, password) {
            const res = await post('/auth/register', { name, email, password });
            if (res.token) setToken(res.token);
            return res;
        },
        async me() {
            return get('/auth/me');
        },
        logout() {
            clearToken();
            window.dispatchEvent(new CustomEvent('auth:logout'));
        },
        isLoggedIn: () => !!getToken(),
    };

    // Movie endpoints
    const movies = {
        list: (params) => get('/movies', params),
        get: (id) => get(`/movies/${id}`),
        create: (data) => post('/movies', data),
        update: (id, data) => put(`/movies/${id}`, data),
        delete: (id) => del(`/movies/${id}`),
        sync: (tmdbId) => post('/movies/sync', { tmdb_id: tmdbId }),
        syncBatch: (tmdbIds) => post('/movies/sync-batch', { tmdb_ids: tmdbIds }),
    };

    // TMDB proxy
    const tmdb = {
        search: (query, page = 1) => get('/tmdb/search', { query, page }),
        trending: (window = 'week') => get('/tmdb/trending', { window }),
        details: (id) => get(`/tmdb/movie/${id}`),
    };

    // Watchlist endpoints
    const watchlist = {
        list: () => get('/watchlist'),
        add: (movieId) => post(`/watchlist/${movieId}`),
        remove: (movieId) => del(`/watchlist/${movieId}`),
    };

    // User interactions
    const interactions = {
        like: (movieId) => post(`/movies/${movieId}/like`),
        history: (movieId) => post(`/movies/${movieId}/history`),
        status: (movieId) => get(`/interactions/status/${movieId}`),
    };

    // Recommendations
    const recommendations = (limit = 12) => get('/recommendations', { limit });

    return {
        get, post, put, del,
        auth, movies, tmdb, watchlist, interactions, recommendations,
        getToken, setToken, clearToken
    };
})();

// Export for modules
if (typeof module !== 'undefined') module.exports = API;
