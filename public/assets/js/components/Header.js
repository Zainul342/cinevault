/**
 * Header Component
 * renders top bar with search and user profile
 */
const Header = (() => {
    function render() {
        const user = State.get('user');
        const displayName = user ? user.name : 'Guest';

        return `
            <header class="top-bar">
                <div class="search-bar">
                    <i class="fas fa-search" style="color: var(--text-muted)"></i>
                    <input type="text" id="global-search" placeholder="Search movies, actors...">
                </div>
                <div class="user-profile">
                    <button class="btn btn-accent" style="padding: 8px 12px;">
                        <i class="fas fa-bell"></i>
                    </button>
                    <div class="avatar"></div>
                    <span style="font-weight: 600;" id="user-display-name">${displayName}</span>
                </div>
            </header>
        `;
    }

    function bindEvents() {
        const searchInput = document.getElementById('global-search');
        if (searchInput) {
            const dropdown = SearchBar.createResultsDropdown(searchInput.parentElement);

            SearchBar.init(searchInput, {
                debounceMs: 400,
                minChars: 2,
                onSearch: async (query) => {
                    try {
                        dropdown.setContent('<div class="search-loading">Searching...</div>');
                        dropdown.show();

                        const res = await API.tmdb.search(query);
                        const html = SearchBar.renderResults(res.results || [], (movie) => {
                            dropdown.clear();
                            showMovieModal(movie);
                        });
                        dropdown.setContent(html);

                        dropdown.element.querySelectorAll('.search-result-item').forEach(item => {
                            item.addEventListener('click', () => {
                                const movies = res.results || [];
                                const movie = movies.find(m => m.id == item.dataset.id);
                                if (movie) showMovieModal(movie);
                                dropdown.clear();
                            });
                        });
                    } catch (err) {
                        dropdown.setContent('<div class="search-error">Search failed</div>');
                    }
                },
                onClear: () => dropdown.clear()
            });

            document.addEventListener('click', (e) => {
                if (!e.target.closest('.search-bar')) dropdown.hide();
            });
        }

        State.subscribe('user', (user) => {
            const nameEl = document.getElementById('user-display-name');
            if (nameEl) nameEl.textContent = user ? user.name : 'Guest';
        });
    }

    function showMovieModal(movie) {
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

    return { render, bindEvents };
})();

if (typeof module !== 'undefined') module.exports = Header;
