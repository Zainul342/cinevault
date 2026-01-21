/**
 * Analytics Page
 * shows user stats and viewing history
 */
const AnalyticsPage = (() => {
    async function render(container) {
        container.innerHTML = `
            <h1 class="page-title"><i class="fas fa-chart-line text-primary"></i> Analytics</h1>

            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-lg); margin-bottom: var(--spacing-xl);">
                <div class="stat-card widget-panel">
                    <h3>Movies Watched</h3>
                    <p id="stat-watched" style="font-size: 2rem; font-weight: bold; color: var(--primary);">--</p>
                </div>
                <div class="stat-card widget-panel">
                    <h3>In Watchlist</h3>
                    <p id="stat-watchlist" style="font-size: 2rem; font-weight: bold; color: var(--accent);">--</p>
                </div>
                <div class="stat-card widget-panel">
                    <h3>Movies Liked</h3>
                    <p id="stat-liked" style="font-size: 2rem; font-weight: bold; color: var(--primary);">--</p>
                </div>
                <div class="stat-card widget-panel">
                    <h3>Total Hours</h3>
                    <p id="stat-hours" style="font-size: 2rem; font-weight: bold; color: var(--accent);">--</p>
                </div>
            </div>

            <div class="widget-panel">
                <h3><i class="fas fa-history text-primary"></i> Recent Activity</h3>
                <div id="activity-timeline" style="margin-top: var(--spacing-md);">
                    <p style="color: var(--text-muted);">No activity recorded yet</p>
                </div>
            </div>
        `;

        await loadStats();
    }

    async function loadStats() {
        try {
            const [watchlistRes] = await Promise.all([
                API.watchlist.list()
            ]);

            const watchlistCount = (watchlistRes.data || []).length;

            document.getElementById('stat-watchlist').textContent = watchlistCount;
            document.getElementById('stat-watched').textContent = '0';
            document.getElementById('stat-liked').textContent = '0';
            document.getElementById('stat-hours').textContent = '0h';
        } catch (err) {
            console.error('Failed to load stats:', err);
        }
    }

    return { render };
})();

if (typeof module !== 'undefined') module.exports = AnalyticsPage;
