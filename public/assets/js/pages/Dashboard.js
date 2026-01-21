/**
 * Dashboard Page
 * shows hero section, trending movies, recent activity
 */
const DashboardPage = (() => {
    let trendingMovies = [];

    async function render(container) {
        container.innerHTML = `
            <div class="dashboard-grid">
                <div class="flex flex-col gap-lg">
                    <div class="hero-section" id="hero-section">
                        <div class="hero-overlay">
                            <div class="hero-content">
                                <h1 style="margin-bottom: 12px; font-size: 1.5rem;">Loading...</h1>
                                <p style="margin-bottom: 16px;">Fetching trending movies...</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between items-center" style="margin-bottom: var(--spacing-md)">
                            <h2><i class="fas fa-fire text-primary"></i> Trending Now</h2>
                            <a href="#/movies" class="text-accent">View All</a>
                        </div>
                        <div id="trending-grid" class="movie-grid"></div>
                    </div>
                </div>
                <div class="flex flex-col gap-lg">
                    <div class="widget-panel">
                        <h3><i class="fas fa-history text-primary"></i> Recent Activity</h3>
                        <div id="activity-list">
                            <p style="color: var(--text-muted)">No recent activity</p>
                        </div>
                    </div>
                    <div class="widget-panel" style="background: var(--primary);">
                        <h3><i class="fas fa-star"></i> Pro Membership</h3>
                        <p style="margin: 10px 0;">Upgrade to access 4K streaming and exclusive content.</p>
                        <button class="btn" style="background: white; width: 100%">Upgrade Now</button>
                    </div>
                </div>
            </div>
        `;

        await loadTrending();
    }

    async function loadTrending() {
        const grid = document.getElementById('trending-grid');
        if (!grid) return;

        grid.innerHTML = '';
        grid.appendChild(MovieCard.createSkeleton(6));

        try {
            const res = await API.tmdb.trending();
            trendingMovies = res.results || [];

            if (trendingMovies.length > 0) {
                updateHero(trendingMovies[0]);
            }

            MovieCard.renderGrid(grid, trendingMovies.slice(0, 6), {
                onClick: showMovieDetail,
                showActions: true
            });
        } catch (err) {
            grid.innerHTML = '<p class="error">Failed to load trending movies</p>';
            Toast.error('Could not load trending movies');
        }
    }

    function updateHero(movie) {
        const hero = document.getElementById('hero-section');
        if (!hero) return;

        const bgUrl = movie.backdrop_path
            ? `https://image.tmdb.org/t/p/original${movie.backdrop_path}`
            : '';

        hero.style.backgroundImage = `url('${bgUrl}')`;

        const content = hero.querySelector('.hero-content');
        if (content) {
            content.innerHTML = `
                <h1 style="margin-bottom: 12px; font-size: 1.5rem;">${movie.title}</h1>
                <p style="margin-bottom: 16px;">${movie.overview?.substring(0, 150)}...</p>
                <div class="flex gap-sm">
                    <button class="btn btn-primary" id="hero-watch-btn">
                        <i class="fas fa-play"></i> Watch Now
                    </button>
                    <button class="btn" style="background: white" id="hero-add-btn">
                        <i class="fas fa-plus"></i> Add to Library
                    </button>
                </div>
            `;

            document.getElementById('hero-watch-btn')?.addEventListener('click', () => showMovieDetail(movie));
            document.getElementById('hero-add-btn')?.addEventListener('click', async () => {
                try {
                    await API.movies.sync(movie.id);
                    Toast.success('Movie added to library!');
                } catch (err) {
                    Toast.error(err.message || 'Failed to sync movie');
                }
            });
        }
    }

    function showMovieDetail(movie) {
        Modal.open({
            title: movie.title,
            size: 'large',
            content: `
                <div class="movie-detail flex gap-lg">
                    <img src="https://image.tmdb.org/t/p/w300${movie.poster_path || ''}" 
                         alt="${movie.title}"
                         style="border-radius: var(--radius-sm); border: var(--border-width) solid var(--border-color);">
                    <div>
                        <p><strong>Release:</strong> ${movie.release_date || 'N/A'}</p>
                        <p><strong>Rating:</strong> ${movie.vote_average?.toFixed(1) || '-'}/10</p>
                        <p style="margin-top: 16px;">${movie.overview || 'No description.'}</p>
                    </div>
                </div>
            `,
            actions: [
                {
                    label: 'Add to Library',
                    class: 'btn-accent',
                    onClick: async () => {
                        try {
                            await API.movies.sync(movie.id);
                            Toast.success('Added to library!');
                            Modal.close();
                        } catch (err) {
                            Toast.error(err.message || 'Failed to add');
                        }
                    }
                },
                { label: 'Close', onClick: () => Modal.close() }
            ]
        });
    }

    return { render };
})();

if (typeof module !== 'undefined') module.exports = DashboardPage;
