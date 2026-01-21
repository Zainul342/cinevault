/**
 * State - Simple reactive global state store
 * stores user session, app config, and shared data
 */
const State = (() => {
    const state = {
        user: null,
        isAuthenticated: false,
        currentPage: 'dashboard',
        movies: [],
        watchlist: [],
        loading: false
    };

    const listeners = {};

    function get(key) {
        return key ? state[key] : { ...state };
    }

    function set(key, value) {
        const oldValue = state[key];
        state[key] = value;

        if (listeners[key]) {
            listeners[key].forEach(fn => fn(value, oldValue));
        }
        if (listeners['*']) {
            listeners['*'].forEach(fn => fn(key, value, oldValue));
        }
    }

    function subscribe(key, callback) {
        if (!listeners[key]) listeners[key] = [];
        listeners[key].push(callback);

        return () => {
            listeners[key] = listeners[key].filter(fn => fn !== callback);
        };
    }

    function setUser(user) {
        set('user', user);
        set('isAuthenticated', !!user);
    }

    function clearUser() {
        set('user', null);
        set('isAuthenticated', false);
    }

    return { get, set, subscribe, setUser, clearUser };
})();

if (typeof module !== 'undefined') module.exports = State;
