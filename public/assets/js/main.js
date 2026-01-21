/**
 * Main Application Bootstrap
 * initializes router, components, and auth
 */
document.addEventListener('DOMContentLoaded', async () => {
    const appWrapper = document.querySelector('.app-wrapper');
    const contentContainer = document.getElementById('content-area');

    if (!Auth.requireAuth()) return;

    appWrapper.innerHTML = Sidebar.render() + `
        <main class="main-content">
            ${Header.render()}
            <div id="content-area" class="content-container"></div>
        </main>
    `;

    Sidebar.bindEvents();
    Header.bindEvents();

    await Auth.checkSession();

    Router.register('/dashboard', DashboardPage.render);
    Router.register('/movies', MoviesPage.render);
    Router.register('/watchlist', WatchlistPage.render);
    Router.register('/analytics', AnalyticsPage.render);
    Router.register('/settings', (container) => {
        container.innerHTML = `
            <h1 class="page-title"><i class="fas fa-cog text-primary"></i> Settings</h1>
            <div class="widget-panel">
                <p>Settings page coming soon...</p>
            </div>
        `;
    });

    Router.init('#content-area');

    document.addEventListener('movie:action', async (e) => {
        const { action, movie } = e.detail;
        const movieId = movie.id || movie.tmdb_id;

        if (!Auth.isLoggedIn()) {
            Toast.warning('Please login to use this feature');
            return;
        }

        const btn = document.querySelector(`[data-action="${action}"][data-movie-id="${movieId}"]`);

        try {
            if (action === 'watchlist') {
                btn?.classList.toggle('active');
                btn?.classList.add('animating');

                const inWatchlist = btn?.classList.contains('active');

                if (inWatchlist) {
                    if (!movie.id && movie.tmdb_id) {
                        await API.movies.sync(movie.tmdb_id);
                    }
                    await API.watchlist.add(movieId);
                    Toast.success(`"${movie.title}" added to watchlist`);
                } else {
                    await API.watchlist.remove(movieId);
                    Toast.success(`"${movie.title}" removed from watchlist`);
                }
            } else if (action === 'like') {
                btn?.classList.toggle('active');
                btn?.classList.add('animating');

                if (!movie.id && movie.tmdb_id) {
                    await API.movies.sync(movie.tmdb_id);
                }

                const res = await API.interactions.like(movieId);
                Toast.success(res.liked ? 'Movie liked!' : 'Like removed');
            }
        } catch (err) {
            btn?.classList.remove('active', 'animating');
            Toast.error(err.message || 'Action failed');
        } finally {
            setTimeout(() => btn?.classList.remove('animating'), 300);
        }
    });
});
