/**
 * Sidebar Component
 * renders navigation menu and handles active states
 */
const Sidebar = (() => {
    const navItems = [
        { path: '/dashboard', icon: 'fa-home', label: 'Dashboard' },
        { path: '/movies', icon: 'fa-film', label: 'Movies' },
        { path: '/watchlist', icon: 'fa-bookmark', label: 'Watchlist' },
        { path: '/analytics', icon: 'fa-chart-line', label: 'Analytics' },
    ];

    function render() {
        return `
            <aside class="sidebar">
                <div class="brand">
                    <h1>CINEVAULT</h1>
                </div>
                <nav class="nav-menu">
                    ${navItems.map(item => `
                        <a class="nav-item" data-path="${item.path}" href="#${item.path}">
                            <i class="fas ${item.icon}"></i>
                            <span class="nav-text">${item.label}</span>
                        </a>
                    `).join('')}
                    <div style="flex:1"></div>
                    <a class="nav-item" data-path="/settings" href="#/settings">
                        <i class="fas fa-cog"></i>
                        <span class="nav-text">Settings</span>
                    </a>
                    <a class="nav-item logout-btn" id="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="nav-text">Logout</span>
                    </a>
                </nav>
            </aside>
        `;
    }

    function bindEvents() {
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                Auth.logout();
            });
        }
    }

    return { render, bindEvents };
})();

if (typeof module !== 'undefined') module.exports = Sidebar;
