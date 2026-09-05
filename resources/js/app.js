import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    // Theme Management
    initTheme();
    setupThemeToggle();

    // Font Resizing (Reading Experience)
    setupFontResizer();

    // Reading Mode (Sepia toggle)
    setupReadingMode();

    // Lazy Loading fallback if browser doesn't support native lazy-loading
    setupLazyLoading();

    // Reading Progress Bar (Fallback if CSS Timeline scroll() is not supported)
    setupReadingProgressFallback();

    // Mega menu logic
    setupMegaMenu();
});

function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    if (theme === 'dark') {
        document.documentElement.classList.add('dark');
        document.documentElement.classList.remove('light');
    } else {
        document.documentElement.classList.remove('dark');
        document.documentElement.classList.add('light');
    }
    updateThemeToggleUI(theme);
}

// Theme Management
function initTheme() {
    const savedTheme = localStorage.getItem('theme');
    const preferredTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    const activeTheme = savedTheme || preferredTheme;
    applyTheme(activeTheme);
}

function setupThemeToggle() {
    const toggleBtns = document.querySelectorAll('.theme-toggle-btn');
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const isDark = document.documentElement.classList.contains('dark') || document.documentElement.getAttribute('data-theme') === 'dark';
            const nextTheme = isDark ? 'light' : 'dark';

            if (document.startViewTransition) {
                document.startViewTransition(() => {
                    applyTheme(nextTheme);
                });
            } else {
                applyTheme(nextTheme);
            }
            localStorage.setItem('theme', nextTheme);
        });
    });
}

function updateThemeToggleUI(theme) {
    const sunIcons = document.querySelectorAll('.theme-icon-sun');
    const moonIcons = document.querySelectorAll('.theme-icon-moon');
    
    if (theme === 'dark') {
        sunIcons.forEach(i => i.classList.remove('hidden'));
        moonIcons.forEach(i => i.classList.add('hidden'));
    } else {
        sunIcons.forEach(i => i.classList.add('hidden'));
        moonIcons.forEach(i => i.classList.remove('hidden'));
    }
}

// Font Resizing
function setupFontResizer() {
    const increaseBtn = document.getElementById('increase-font');
    const decreaseBtn = document.getElementById('decrease-font');
    const resetBtn = document.getElementById('reset-font');
    const contentArea = document.querySelector('.news-content-area');

    if (!contentArea) return;

    let currentSizePercent = 100;

    if (increaseBtn) {
        increaseBtn.addEventListener('click', () => {
            if (currentSizePercent < 150) {
                currentSizePercent += 10;
                contentArea.style.fontSize = `${currentSizePercent}%`;
            }
        });
    }

    if (decreaseBtn) {
        decreaseBtn.addEventListener('click', () => {
            if (currentSizePercent > 80) {
                currentSizePercent -= 10;
                contentArea.style.fontSize = `${currentSizePercent}%`;
            }
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            currentSizePercent = 100;
            contentArea.style.fontSize = '100%';
        });
    }
}

// Reading Mode (Sepia Sepia Mode Toggle)
function setupReadingMode() {
    const readingModeBtn = document.getElementById('reading-mode-toggle');
    const contentBody = document.querySelector('body');
    const articleContainer = document.querySelector('.article-main-container');

    if (readingModeBtn) {
        readingModeBtn.addEventListener('click', () => {
            contentBody.classList.toggle('reading-mode-active');
            if (articleContainer) {
                articleContainer.classList.toggle('shadow-none');
            }
            const isSepia = contentBody.classList.contains('reading-mode-active');
            localStorage.setItem('reading-mode', isSepia ? 'active' : 'inactive');
        });
    }
}

// Lazy Loading fallback
function setupLazyLoading() {
    const lazyImages = document.querySelectorAll('.lazy-image');

    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const image = entry.target;
                    image.src = image.dataset.src;
                    image.addEventListener('load', () => {
                        image.classList.add('loaded');
                    });
                    imageObserver.unobserve(image);
                }
            });
        });

        lazyImages.forEach(image => {
            imageObserver.observe(image);
        });
    } else {
        // Fallback for older browsers
        lazyImages.forEach(image => {
            image.src = image.dataset.src;
            image.classList.add('loaded');
        });
    }
}

// Reading Progress Bar Fallback (in case CSS Timeline scroll() is not supported)
function setupReadingProgressFallback() {
    const progressBar = document.querySelector('.scroll-progress-bar');
    if (!progressBar) return;

    if (!CSS.supports('animation-timeline', 'scroll()')) {
        window.addEventListener('scroll', () => {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = (winScroll / height) * 100;
            progressBar.style.scale = `${scrolled / 100} 1`;
        });
    }
}

// Mega Menu setup
function setupMegaMenu() {
    const megaMenuToggle = document.getElementById('mega-menu-toggle');
    const megaMenu = document.getElementById('mega-menu');
    
    if (megaMenuToggle && megaMenu) {
        megaMenuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            megaMenu.classList.toggle('hidden');
            megaMenu.classList.toggle('flex');
        });

        document.addEventListener('click', (e) => {
            if (!megaMenu.contains(e.target) && e.target !== megaMenuToggle) {
                megaMenu.classList.add('hidden');
                megaMenu.classList.remove('flex');
            }
        });
    }
}
