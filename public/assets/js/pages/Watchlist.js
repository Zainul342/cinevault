/**
 * Watchlist Page
 * shows user's saved movies and recommendations
 */
const WatchlistPage = (() => {
    async function render(container) {
        container.innerHTML = `
            <h1 class="page-title"><i class="fas fa-bookmark text-primary"></i> My Watchlist</h1>

            <div id="watchlist-grid" class="movie-grid"></div>

            <div id="recommendations-section" style="margin-top: var(--spacing-xl);">
                <h2><i class="fas fa-magic text-accent"></i> Recommended for You</h2>
                <div id="recommendations-grid" class="movie-grid" style="margin-top: var(--spacing-md);"></div>
            </div>
        `;

        await loadWatchlist();
        await loadRecommendations();
    }

    async function loadWatchlist() {
        const grid = document.getElementById('watchlist-grid');
        if (!grid) return;

        grid.innerHTML = '';
        grid.appendChild(MovieCard.createSkeleton(4));

        try {
            const res = await API.watchlist.list();
            const movies = res.data || [];

            if (movies.length === 0) {
                grid.innerHTML = `
                    <div style="text-align: center; padding: 40px; grid-column: 1/-1;">
                        <i class="fas fa-bookmark" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 16px;"></i>
                        <p>Your watchlist is empty</p>
                        <a href="#/movies" class="btn btn-primary" style="margin-top: 16px;">Browse Movies</a>
                    </div>
                `;
                return;
            }

            MovieCard.renderGrid(grid, movies, {
                onClick: showMovieDetail,
                showActions: true
            });
        } catch (err) {
            grid.innerHTML = '<p class="error">Failed to load watchlist</p>';
            Toast.error(err.message || 'Load failed');
        }
    }

    async function loadRecommendations() {
        const grid = document.getElementById('recommendations-grid');
        if (!grid) return;

        grid.innerHTML = '';
        grid.appendChild(MovieCard.createSkeleton(4));

        try {
            const res = await API.recommendations(8);
            const movies = res.data || [];

            if (movies.length === 0) {
                grid.innerHTML = '<p style="color: var(--text-muted);">Like some movies to get recommendations!</p>';
                return;
            }

            MovieCard.renderGrid(grid, movies, {
                onClick: showMovieDetail,
                showActions: true
            });
        } catch (err) {
            grid.innerHTML = '<p style="color: var(--text-muted);">Recommendations unavailable</p>';
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
                { label: 'Close', onClick: () => Modal.close() }
            ]
        });
    }

    return { render };
})();

if (typeof module !== 'undefined') module.exports = WatchlistPage;
