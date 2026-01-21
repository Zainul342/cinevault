/**
 * Auth - Authentication guards and helpers
 * manages token storage and route protection
 */
const Auth = (() => {
    const TOKEN_KEY = 'cv_token';
    const USER_KEY = 'cv_user';

    function getToken() {
        return localStorage.getItem(TOKEN_KEY);
    }

    function setToken(token) {
        localStorage.setItem(TOKEN_KEY, token);
    }

    function clearToken() {
        localStorage.removeItem(TOKEN_KEY);
        localStorage.removeItem(USER_KEY);
    }

    function isLoggedIn() {
        return !!getToken();
    }

    function requireAuth(redirectTo = '/login.html') {
        if (!isLoggedIn()) {
            window.location.href = redirectTo;
            return false;
        }
        return true;
    }

    async function checkSession() {
        if (!isLoggedIn()) return null;

        try {
            const res = await API.auth.me();
            if (res.user) {
                State.setUser(res.user);
                return res.user;
            }
        } catch (err) {
            clearToken();
            State.clearUser();
        }
        return null;
    }

    function logout() {
        clearToken();
        State.clearUser();
        window.location.href = '/login.html';
    }

    return { getToken, setToken, clearToken, isLoggedIn, requireAuth, checkSession, logout };
})();

if (typeof module !== 'undefined') module.exports = Auth;
