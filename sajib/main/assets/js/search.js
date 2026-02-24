// Global Search Functionality
(function () {
    const searchInput = document.getElementById('globalSearch');
    const searchResults = document.getElementById('searchResults');

    if (!searchInput || !searchResults) {
        console.warn('Search elements not found');
        return;
    }

    let searchTimeout;
    const MIN_SEARCH_LENGTH = 2;

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        const query = this.value.trim();

        if (query.length < MIN_SEARCH_LENGTH) {
            searchResults.style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(() => performSearch(query), 300);
    });

    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            const query = this.value.trim();
            if (query.length >= MIN_SEARCH_LENGTH) {
                window.location.href = `search_results?q=${encodeURIComponent(query)}`;
            }
        }
        if (e.key === 'Escape') {
            searchResults.style.display = 'none';
        }
    });

    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });

    function performSearch(query) {
        // Detect paths for the new modular structure
        let prefix = './';
        const path = window.location.pathname;

        if (path.includes('/modules/')) {
            // We are inside a module folder (2 levels deep from main root)
            prefix = '../../';
        }

        const searchUrl = `${prefix}modules/search/global.php?q=${encodeURIComponent(query)}`;

        fetch(searchUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data && data.results) {
                    displayResults(data);
                } else {
                    throw new Error('Invalid response format');
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                searchResults.innerHTML = '<div class="p-3 text-danger"><i class="fa-solid fa-exclamation-triangle me-2"></i>Search unavailable</div>';
                searchResults.style.display = 'block';
            });
    }

    function displayResults(data) {
        if (!data.results || data.results.length === 0) {
            searchResults.innerHTML = `
                <div class="p-3 text-muted text-center">
                    <i class="fa-solid fa-magnifying-glass fa-2x mb-2 opacity-25"></i>
                    <div>No results found for "${escapeHtml(data.query)}"</div>
                </div>
            `;
            searchResults.style.display = 'block';
            return;
        }

        let html = '<div class="py-2">';

        data.results.forEach(result => {
            const formattedDate = new Date(result.date).toLocaleDateString('en-US', {
                month: 'short', day: 'numeric', year: 'numeric'
            });

            let prefix = './';
            if (window.location.pathname.includes('/modules/')) {
                prefix = '../../';
            }

            html += `
                <a href="${prefix}${result.url}" class="d-flex align-items-start p-3 text-decoration-none text-dark border-bottom" style="transition: background 0.2s; cursor: pointer;">
                    <div class="flex-shrink-0 me-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 40px; height: 40px; background-color: ${result.color}15;">
                            <i class="fa-solid ${result.icon}" style="color: ${result.color};"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1" style="min-width: 0;">
                        <div class="fw-bold text-truncate">${escapeHtml(result.title)}</div>
                        <div class="small text-muted">
                            <span class="badge" style="background-color: ${result.color}20; color: ${result.color}; font-weight: 600;">${escapeHtml(result.module)}</span>
                            <span class="ms-2"><i class="fa-solid fa-calendar me-1"></i>${formattedDate}</span>
                            ${result.created_by && result.created_by !== 'N/A' ? `<span class="ms-2"><i class="fa-solid fa-user me-1"></i>${escapeHtml(result.created_by)}</span>` : ''}
                        </div>
                    </div>
                </a>
            `;
        });

        let prefix = './';
        if (window.location.pathname.includes('/modules/')) {
            prefix = '../../';
        }
        html += `
                <a href="${prefix}modules/search/results?q=${encodeURIComponent(data.query)}" 
                   class="d-block p-3 text-center text-primary text-decoration-none border-top bg-light">
                    <i class="fa-solid fa-arrow-right me-2"></i>View all ${data.total} results
                </a>
            `;

        html += '</div>';
        searchResults.innerHTML = html;

        // Add hover effects to links
        const links = searchResults.querySelectorAll('a');
        links.forEach(link => {
            link.addEventListener('mouseenter', function () {
                this.style.background = 'rgba(0,0,0,0.02)';
            });
            link.addEventListener('mouseleave', function () {
                this.style.background = 'transparent';
            });
        });

        searchResults.style.display = 'block';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
