/**
 * MovieCard Component - Renders movie cards dynamically
 */
const MovieCard = (() => {
    const TMDB_IMG_BASE = 'https://image.tmdb.org/t/p/w500';

    /**
     * Create a movie card element
     * @param {Object} movie - Movie data
     * @param {Object} options - Render options
     */
    function create(movie, options = {}) {
        const {
            onClick = null,
            showActions = false,
            size = 'normal', // 'small' | 'normal' | 'large'
        } = options;

        const card = document.createElement('div');
        card.className = `movie-card movie-card--${size}`;
        card.dataset.id = movie.id || movie.tmdb_id;

        const posterUrl = movie.poster_path
            ? `${TMDB_IMG_BASE}${movie.poster_path}`
            : 'assets/img/no-poster.png';

        const year = movie.release_date
            ? new Date(movie.release_date).getFullYear()
            : 'N/A';

        const rating = movie.vote_average
            ? movie.vote_average.toFixed(1)
            : '-';

        card.innerHTML = `
            <img src="${posterUrl}" alt="${movie.title}" class="card-poster" loading="lazy">
            <div class="card-info">
                <h3 class="card-title" title="${movie.title}">${movie.title}</h3>
                <div class="card-meta">
                    <span>${year}</span>
                    <span class="rating-badge">${rating}</span>
                </div>
                ${showActions ? `
                    <div class="card-actions">
                        <button class="btn-icon" data-action="watchlist" title="Add to Watchlist">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button class="btn-icon" data-action="like" title="Like">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                ` : ''}
            </div>
        `;

        // Click handler
        if (onClick) {
            card.style.cursor = 'pointer';
            card.addEventListener('click', (e) => {
                if (!e.target.closest('.card-actions')) {
                    onClick(movie);
                }
            });
        }

        // Action buttons
        if (showActions) {
            card.querySelectorAll('.btn-icon').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const action = btn.dataset.action;
                    card.dispatchEvent(new CustomEvent('movie:action', {
                        bubbles: true,
                        detail: { action, movie }
                    }));
                });
            });
        }

        return card;
    }

    /**
     * Render multiple cards into a container
     */
    function renderGrid(container, movies, options = {}) {
        const grid = document.createElement('div');
        grid.className = 'movie-grid';

        movies.forEach(movie => {
            grid.appendChild(create(movie, options));
        });

        container.innerHTML = '';
        container.appendChild(grid);
    }

    /**
     * Create skeleton loader cards
     */
    function createSkeleton(count = 4) {
        const fragment = document.createDocumentFragment();
        for (let i = 0; i < count; i++) {
            const skeleton = document.createElement('div');
            skeleton.className = 'movie-card movie-card--skeleton';
            skeleton.innerHTML = `
                <div class="skeleton-poster"></div>
                <div class="card-info">
                    <div class="skeleton-text"></div>
                    <div class="skeleton-text short"></div>
                </div>
            `;
            fragment.appendChild(skeleton);
        }
        return fragment;
    }

    return { create, renderGrid, createSkeleton };
})();
