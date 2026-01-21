/**
 * SearchBar Component - With debounce and TMDB integration
 */
const SearchBar = (() => {
    let debounceTimer = null;

    /**
     * Initialize search bar functionality
     * @param {HTMLInputElement} input - Search input element
     * @param {Object} options - Configuration
     */
    function init(input, options = {}) {
        const {
            debounceMs = 350,
            minChars = 2,
            onSearch = () => { },
            onClear = () => { },
            resultsContainer = null,
        } = options;

        // Debounced search handler
        input.addEventListener('input', (e) => {
            const query = e.target.value.trim();

            clearTimeout(debounceTimer);

            if (query.length < minChars) {
                if (query.length === 0) onClear();
                return;
            }

            debounceTimer = setTimeout(() => {
                onSearch(query);
            }, debounceMs);
        });

        // Clear on Escape
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                input.value = '';
                onClear();
            }
        });

        return {
            clear: () => {
                input.value = '';
                onClear();
            },
            setValue: (val) => {
                input.value = val;
            },
        };
    }

    /**
     * Create search results dropdown
     */
    function createResultsDropdown(container) {
        const dropdown = document.createElement('div');
        dropdown.className = 'search-results';
        dropdown.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--bg-card);
            border: var(--border-width) solid var(--border-color);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-hard);
            max-height: 400px;
            overflow-y: auto;
            z-index: 100;
            display: none;
        `;
        container.style.position = 'relative';
        container.appendChild(dropdown);

        return {
            show: () => dropdown.style.display = 'block',
            hide: () => dropdown.style.display = 'none',
            setContent: (html) => dropdown.innerHTML = html,
            clear: () => {
                dropdown.innerHTML = '';
                dropdown.style.display = 'none';
            },
            element: dropdown,
        };
    }

    /**
     * Render search result items
     */
    function renderResults(movies, onSelect) {
        if (!movies.length) {
            return '<div class="search-empty">No results found</div>';
        }

        return movies.map(movie => `
            <div class="search-result-item" data-id="${movie.id}">
                <img src="https://image.tmdb.org/t/p/w92${movie.poster_path || ''}" 
                     onerror="this.src='assets/img/no-poster.png'"
                     alt="${movie.title}">
                <div class="result-info">
                    <strong>${movie.title}</strong>
                    <span>${movie.release_date?.split('-')[0] || 'N/A'}</span>
                </div>
            </div>
        `).join('');
    }

    return { init, createResultsDropdown, renderResults };
})();
