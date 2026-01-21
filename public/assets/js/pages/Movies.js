/**
 * Movies Page
 * browse and search all movies, sync from TMDB
 */
const MoviesPage = (() => {
    let currentSource = 'library';
    let currentSort = 'latest';

    async function render(container) {
        container.innerHTML = `
            <h1 class="page-title"><i class="fas fa-film text-primary"></i> All Movies</h1>
            
            <div class="filters-bar">
                <div class="filter-group">
                    <label>Sort by:</label>
                    <select id="sort-filter" class="filter-select">
                        <option value="latest">Latest</option>
                        <option value="popular">Popular</option>
                        <option value="rating">Top Rated</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Source:</label>
                    <select id="source-filter" class="filter-select">
                        <option value="library">My Library</option>
                        <option value="tmdb">TMDB Trending</option>
                    </select>
                </div>
                <button id="sync-trending" class="btn btn-accent">
                    <i class="fas fa-sync"></i> Sync Trending
                </button>
            </div>

            <div id="movies-grid" class="movie-grid"></div>

            <div class="load-more">
                <button id="load-more-btn" class="btn btn-primary hidden">
                    <i class="fas fa-plus"></i> Load More
                </button>
            </div>
        `;

        bindEvents();
        await loadMovies();
    }

    function bindEvents() {
        document.getElementById('sort-filter')?.addEventListener('change', (e) => {
            currentSort = e.target.value;
            loadMovies();
        });

        document.getElementById('source-filter')?.addEventListener('change', (e) => {
            currentSource = e.target.value;
            loadMovies();
        });

        document.getElementById('sync-trending')?.addEventListener('click', syncTrending);
    }

    async function loadMovies() {
        const grid = document.getElementById('movies-grid');
        if (!grid) return;

        grid.innerHTML = '';
        grid.appendChild(MovieCard.createSkeleton(8));

        try {
            let movies;

            if (currentSource === 'tmdb') {
                const res = await API.tmdb.trending();
                movies = res.results || [];
            } else {
                const res = await API.movies.list({ sort: currentSort, limit: 20 });
                movies = res.data || [];
            }

            MovieCard.renderGrid(grid, movies, {
                onClick: showMovieDetail,
                showActions: true
            });

            if (movies.length === 0) {
                grid.innerHTML = '<p style="text-align:center; padding: 40px;">No movies found. Try syncing some from TMDB!</p>';
            }
        } catch (err) {
            grid.innerHTML = '<p class="error">Failed to load movies</p>';
            Toast.error(err.message || 'Load failed');
        }
    }

    async function syncTrending() {
        const btn = document.getElementById('sync-trending');
        if (!btn) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';

        try {
            const trending = await API.tmdb.trending();
            const ids = (trending.results || []).slice(0, 10).map(m => m.id);
            const res = await API.movies.syncBatch(ids);
            Toast.success(`Synced ${res.summary.synced_count} movies!`);
            if (currentSource === 'library') loadMovies();
        } catch (err) {
            Toast.error(err.message || 'Sync failed');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sync"></i> Sync Trending';
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

if (typeof module !== 'undefined') module.exports = MoviesPage;
