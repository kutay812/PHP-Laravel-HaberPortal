/**
 * Admin Autocomplete Search Utility
 */
function initAdminAutocomplete(config) {
    const searchInput = document.getElementById(config.inputId || 'searchInput');
    const searchResults = document.getElementById(config.resultsId || 'searchResults');
    const clearSearch = document.getElementById(config.clearId || 'clearSearch');

    if (!searchInput || !searchResults) return;

    let searchTimeout = null;
    let selectedIndex = -1;

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function highlightText(text, searchTerm) {
        if (!searchTerm) return escapeHtml(text);
        const escapedTerm = searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp(`(${escapedTerm})`, 'gi');
        return escapeHtml(text).replace(regex, '<span class="highlight">$1</span>');
    }

    function selectResult(title) {
        searchInput.value = title;
        searchResults.style.display = 'none';
        if (clearSearch) clearSearch.style.display = 'block';
        window.location.href = `${config.redirectUrl}?search=${encodeURIComponent(title)}`;
    }

    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.trim();
        if (clearSearch) clearSearch.style.display = searchTerm ? 'block' : 'none';

        if (searchTerm.length < 1) {
            searchResults.style.display = 'none';
            return;
        }

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetch(`${config.searchUrl}?search=${encodeURIComponent(searchTerm)}`)
                .then(res => {
                    if (!res.ok) throw new Error('Arama hatası');
                    return res.json();
                })
                .then(data => {
                    selectedIndex = -1;
                    if (data.items && data.items.length > 0) {
                        searchResults.innerHTML = data.items.map(item => `
                            <div class="search-result-item" data-title="${encodeURIComponent(item.text)}">
                                <div class="search-result-title">${highlightText(item.text, searchTerm)}</div>
                                ${item.category ? `<div class="search-result-category">${escapeHtml(item.category)}</div>` : ''}
                            </div>
                        `).join('');
                        searchResults.style.display = 'block';

                        searchResults.querySelectorAll('.search-result-item').forEach(el => {
                            el.addEventListener('click', () => {
                                selectResult(decodeURIComponent(el.dataset.title));
                            });
                        });
                    } else {
                        searchResults.innerHTML = `
                            <div class="search-result-item text-muted">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-search me-2"></i> Sonuç bulunamadı
                                </div>
                            </div>`;
                        searchResults.style.display = 'block';
                    }
                })
                .catch(() => {
                    searchResults.innerHTML = `
                        <div class="search-result-item text-danger">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle me-2"></i> Arama sırasında hata oluştu
                            </div>
                        </div>`;
                    searchResults.style.display = 'block';
                });
        }, 150);
    });

    searchInput.addEventListener('focus', function() {
        if (this.value.trim()) {
            searchResults.style.display = 'block';
        }
    });

    if (clearSearch) {
        clearSearch.addEventListener('click', function() {
            searchInput.value = '';
            searchResults.style.display = 'none';
            clearSearch.style.display = 'none';
            searchInput.focus();
            window.location.href = config.redirectUrl;
        });
    }

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target) && (!clearSearch || !clearSearch.contains(e.target))) {
            searchResults.style.display = 'none';
        }
    });

    searchInput.addEventListener('keydown', function(e) {
        const items = searchResults.getElementsByClassName('search-result-item');
        if (!items.length) return;

        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (e.key === 'ArrowDown') {
                selectedIndex = (selectedIndex + 1) % items.length;
            } else {
                selectedIndex = selectedIndex <= 0 ? items.length - 1 : selectedIndex - 1;
            }
            Array.from(items).forEach((item, idx) => {
                if (idx === selectedIndex) {
                    item.classList.add('bg-light');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('bg-light');
                }
            });
        } else if (e.key === 'Enter' && selectedIndex >= 0) {
            e.preventDefault();
            const selectedItem = items[selectedIndex];
            if (selectedItem && selectedItem.dataset.title) {
                selectResult(decodeURIComponent(selectedItem.dataset.title));
            }
        } else if (e.key === 'Escape') {
            searchResults.style.display = 'none';
            searchInput.blur();
        }
    });

    const urlParams = new URLSearchParams(window.location.search);
    const searchParam = urlParams.get('search');
    if (searchParam) {
        searchInput.value = searchParam;
        if (clearSearch) clearSearch.style.display = 'block';
    }
}
