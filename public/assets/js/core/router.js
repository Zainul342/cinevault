/**
 * Router - Hash-based SPA routing
 * handles navigation between pages without full reload
 */
const Router = (() => {
    const routes = {};
    let currentRoute = null;
    let contentEl = null;

    function init(containerSelector) {
        contentEl = document.querySelector(containerSelector);
        if (!contentEl) {
            console.error('Router: container not found');
            return;
        }

        window.addEventListener('hashchange', handleRoute);
        handleRoute();
    }

    function register(path, handler) {
        routes[path] = handler;
    }

    function navigate(path) {
        window.location.hash = path;
    }

    function handleRoute() {
        const hash = window.location.hash.slice(1) || '/dashboard';
        const pathParts = hash.split('/').filter(Boolean);
        const basePath = '/' + (pathParts[0] || 'dashboard');
        const params = pathParts.slice(1);

        if (routes[basePath]) {
            currentRoute = basePath;
            routes[basePath](contentEl, params);
            updateActiveNav(basePath);
        } else if (routes['/404']) {
            routes['/404'](contentEl);
        } else {
            contentEl.innerHTML = '<div class="error-page"><h2>page not found</h2></div>';
        }
    }

    function updateActiveNav(path) {
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            const itemPath = item.dataset.path;
            if (itemPath === path) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }

    function getCurrentRoute() {
        return currentRoute;
    }

    return { init, register, navigate, getCurrentRoute };
})();

if (typeof module !== 'undefined') module.exports = Router;
