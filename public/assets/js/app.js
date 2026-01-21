/**
 * Dashboard App - Main entry point
 * Wires up all components and handles page state
 */
document.addEventListener('DOMContentLoaded', async () => {
    // State
    let currentPage = 'dashboard';
    let trendingMovies = [];
    let user = null;

    // DOM Elements
    const trendingGrid = document.getElementById('trending-grid');
    const heroSection = document.querySelector('.hero-section');
    const searchInput = document.querySelector('.search-bar input');
    const navItems = document.querySelectorAll('.nav-item');

    // Initialize Search
    if (searchInput) {
        const searchDropdown = SearchBar.createResultsDropdown(searchInput.parentElement);

        SearchBar.init(searchInput, {
            debounceMs: 400,
            minChars: 2,
            onSearch: async (query) => {
                try {
                    searchDropdown.setContent('<div class="search-loading">Searching...</div>');
                    searchDropdown.show();

                    const res = await API.tmdb.search(query);
                    const html = SearchBar.renderResults(res.results || [], selectMovie);
                    searchDropdown.setContent(html);

                    // Bind click events
                    searchDropdown.element.querySelectorAll('.search-result-item').forEach(item => {
                        item.addEventListener('click', () => {
                            const movie = (res.results || []).find(m => m.id == item.dataset.id);
                            if (movie) selectMovie(movie);
                            searchDropdown.clear();
                        });
                    });
                } catch (err) {
                    searchDropdown.setContent('<div class="search-error">Search failed</div>');
                }
            },
            onClear: () => searchDropdown.clear(),
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-bar')) {
                searchDropdown.hide();
            }
        });
    }

    // Navigation
    navItems.forEach(item => {
        item.addEventListener('click', () => {
            navItems.forEach(n => n.classList.remove('active'));
            item.classList.add('active');
            const page = item.dataset.page || 'dashboard';
            navigateTo(page);
        });
    });

    // Load initial data
    await loadTrending();

    // Check auth status
    if (API.auth.isLoggedIn()) {
        try {
            const res = await API.auth.me();
            user = res.user;
            updateUserUI(user);
        } catch {
            // Token expired
        }
    }

    // Listen for auth events
    window.addEventListener('auth:expired', () => {
        Toast.warning('Session expired, please login again');
        user = null;
        updateUserUI(null);
    });

    /**
     * Load trending movies
     */
    async function loadTrending() {
        if (!trendingGrid) return;

        // Show skeletons
        trendingGrid.innerHTML = '';
        trendingGrid.appendChild(MovieCard.createSkeleton(4));

        try {
            const res = await API.tmdb.trending();
            trendingMovies = res.results || [];

            // Update hero with first movie
            if (trendingMovies.length > 0) {
                updateHero(trendingMovies[0]);
            }

            // Render cards
            MovieCard.renderGrid(trendingGrid, trendingMovies.slice(0, 6), {
                onClick: selectMovie,
                showActions: true,
            });

        } catch (err) {
            trendingGrid.innerHTML = '<p class="error">Failed to load trending movies</p>';
            Toast.error('Could not load trending movies');
        }
    }

    /**
     * Update hero section with movie
     */
    function updateHero(movie) {
        if (!heroSection) return;

        const bgUrl = movie.backdrop_path
            ? `https://image.tmdb.org/t/p/original${movie.backdrop_path}`
            : '';

        heroSection.style.backgroundImage = `url('${bgUrl}')`;

        const content = heroSection.querySelector('.hero-content');
        if (content) {
            content.innerHTML = `
                <h1 style="margin-bottom: 12px; font-size: 1.5rem;">${movie.title}</h1>
                <p style="margin-bottom: 16px;">${movie.overview?.substring(0, 150)}...</p>
                <div class="flex gap-sm">
                    <button class="btn btn-primary" onclick="selectMovie(${JSON.stringify(movie).replace(/"/g, '&quot;')})">
                        <i class="fas fa-play"></i> Watch Now
                    </button>
                    <button class="btn" style="background: white" data-sync="${movie.id}">
                        <i class="fas fa-plus"></i> Add to Library
                    </button>
                </div>
            `;

            // Sync button
            content.querySelector('[data-sync]')?.addEventListener('click', async () => {
                try {
                    await API.movies.sync(movie.id);
                    Toast.success('Movie added to library!');
                } catch (err) {
                    Toast.error(err.message || 'Failed to sync movie');
                }
            });
        }
    }

    /**
     * Select/view a movie
     */
    window.selectMovie = function (movie) {
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
                        <p style="margin-top: 16px;">${movie.overview || 'No description available.'}</p>
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
            ],
        });
    };

    /**
     * Navigate between pages (SPA-style)
     */
    function navigateTo(page) {
        currentPage = page;
        // In a real app, this would swap content sections
        console.log('Navigate to:', page);
    }

    /**
     * Update user UI based on auth state
     */
    function updateUserUI(userData) {
        const userEl = document.querySelector('.user-profile span');
        if (userEl) {
            userEl.textContent = userData ? userData.name : 'Guest';
        }
    }

    // Movie action events (watchlist, like)
    document.addEventListener('movie:action', async (e) => {
        const { action, movie } = e.detail;

        if (!API.auth.isLoggedIn()) {
            Toast.warning('Please login to use this feature');
            return;
        }

        Toast.info(`${action} action for: ${movie.title}`);
        // TODO: Implement actual watchlist/like API calls
    });
});
