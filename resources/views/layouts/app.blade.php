<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Gece Haber') | Gece Haber</title>
    <meta name="description" content="@yield('meta_description', 'En güncel ulusal ve uluslararası haberler.')">
    <meta property="og:title" content="@yield('title', 'Gece Haber')">
    <meta property="og:image" content="{{ asset('logo.png') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <script>
        (function () {
            try {
                var saved = localStorage.getItem('theme');
                var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                var theme = saved || (prefersDark ? 'dark' : 'light');
                document.documentElement.classList.toggle('dark', theme === 'dark');
                document.documentElement.classList.toggle('light', theme !== 'dark');
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {}
        })();
    </script>

    <!-- Google Fonts: Inter & Source Serif 4 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=Source+Serif+4:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <!-- CSS & Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-background dark:bg-on-surface text-on-background dark:text-inverse-on-surface font-body-md min-h-screen flex flex-col">

    <!-- TopNavBar -->
    <header class="bg-surface dark:bg-on-surface docked full-width top-0 border-b border-outline-variant dark:border-on-surface-variant sticky z-50">
        <div class="flex flex-col w-full max-w-[1280px] mx-auto px-[20px] py-2">
            <!-- Top Row: Logo & Search/Actions -->
            <div class="flex justify-between items-center py-3">
                <a class="inline-flex items-center gap-3 hover:opacity-90 transition-opacity shrink-0" href="{{ route('home') }}" aria-label="Gece Haber Ana Sayfa">
                    <img src="{{ asset('images/logo-light.png') }}" alt="Gece Haber" width="220" height="90" class="h-11 sm:h-12 md:h-14 w-auto object-contain dark:hidden">
                    <img src="{{ asset('images/logo-dark.png') }}" alt="Gece Haber" width="220" height="90" class="h-11 sm:h-12 md:h-14 w-auto object-contain hidden dark:block">
                </a>

                <div class="hidden md:flex items-center gap-4">
                    <form action="{{ route('home') }}" method="GET" class="relative" role="search">
                        <label for="desktop-search" class="sr-only">Haberlerde ara</label>
                        <input id="desktop-search" name="search" value="{{ request('search') }}" class="bg-surface-container dark:bg-inverse-surface dark:text-inverse-on-surface border border-outline-variant rounded-full py-2 pl-4 pr-10 focus:outline-none focus:border-primary text-sm w-64" placeholder="Haberlerde ara..." type="text"/>
                        <button type="submit" aria-label="Ara" class="material-symbols-outlined absolute right-3 top-1/2 transform -translate-y-1/2 text-on-surface-variant dark:text-surface-variant cursor-pointer">search</button>
                    </form>

                    <button type="button" class="theme-toggle-btn w-10 h-10 rounded-full bg-surface-container dark:bg-inverse-surface border border-outline-variant flex items-center justify-center text-on-surface dark:text-inverse-on-surface hover:border-secondary transition-colors cursor-pointer" aria-label="Temayı değiştir" title="Açık / Koyu Tema">
                        <span class="material-symbols-outlined theme-icon-moon text-xl">dark_mode</span>
                        <span class="material-symbols-outlined theme-icon-sun text-xl hidden">light_mode</span>
                    </button>

                    @auth
                        <div class="relative group">
                            <button type="button" aria-haspopup="true" class="flex items-center gap-2 bg-primary text-on-primary px-4 py-2 rounded font-headline-sm text-headline-sm tracking-tight hover:opacity-90 transition-all cursor-pointer">
                                <span class="material-symbols-outlined">person</span> {{ explode(' ', auth()->user()->name)[0] }}
                            </button>
                            <div class="absolute right-0 top-full pt-2 w-52 opacity-0 invisible group-hover:opacity-100 group-hover:visible focus-within:opacity-100 focus-within:visible transition-all z-50">
                                <div class="bg-surface-container-highest dark:bg-inverse-surface border border-outline-variant rounded shadow-xl overflow-hidden">
                                    @if(auth()->user()->hasAnyRole(['Admin', 'Editor', 'Super Admin']))
                                        <a href="{{ route('admin.index') }}" class="block px-4 py-2.5 text-sm font-semibold text-on-surface dark:text-inverse-on-surface hover:bg-surface-container dark:hover:bg-on-surface hover:text-secondary transition-colors">
                                            <span class="material-symbols-outlined align-middle text-base mr-1">settings</span> Yönetim Paneli
                                        </a>
                                    @endif
                                    <a href="{{ route('profile') }}" class="block px-4 py-2.5 text-sm text-on-surface dark:text-inverse-on-surface hover:bg-surface-container dark:hover:bg-on-surface hover:text-secondary transition-colors">
                                        <span class="material-symbols-outlined align-middle text-base mr-1">manage_accounts</span> Profilim
                                    </a>
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-on-surface dark:text-inverse-on-surface hover:bg-surface-container dark:hover:bg-on-surface hover:text-error cursor-pointer transition-colors">
                                            <span class="material-symbols-outlined align-middle text-base mr-1">logout</span> Çıkış Yap
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="flex items-center gap-2 bg-primary text-on-primary px-4 py-2 rounded font-headline-sm text-headline-sm tracking-tight hover:opacity-90 transition-all">
                            <span class="material-symbols-outlined">person</span> Giriş Yap
                        </a>
                    @endauth
                </div>

                <!-- Mobile Menu Buttons -->
                <div class="flex items-center gap-2 md:hidden">
                    <button type="button" class="theme-toggle-btn w-9 h-9 rounded-full bg-surface-container dark:bg-inverse-surface border border-outline-variant flex items-center justify-center text-on-surface dark:text-inverse-on-surface cursor-pointer" aria-label="Temayı değiştir">
                        <span class="material-symbols-outlined theme-icon-moon text-lg">dark_mode</span>
                        <span class="material-symbols-outlined theme-icon-sun text-lg hidden">light_mode</span>
                    </button>
                    <button type="button" id="mobile-menu-open" aria-label="Menüyü aç" aria-expanded="false" class="text-primary dark:text-inverse-on-surface cursor-pointer p-1">
                        <span class="material-symbols-outlined text-3xl">menu</span>
                    </button>
                </div>
            </div>

            <!-- Bottom Row: Navigation Links -->
            <nav class="hidden md:flex gap-6 py-2 overflow-x-auto" aria-label="Ana kategoriler">
                <a class="{{ request()->routeIs('home') && !request()->has('tag') ? 'text-secondary dark:text-secondary-fixed border-b-2 border-secondary dark:border-secondary-fixed' : 'text-on-surface dark:text-surface-variant font-bold hover:text-secondary dark:hover:text-secondary-fixed transition-colors duration-200' }} pb-1 font-headline-sm text-headline-sm tracking-tight whitespace-nowrap" href="{{ route('home') }}">ANA SAYFA</a>
                @foreach($globalCategories ?? [] as $cat)
                    <a class="{{ request()->is('kategori/'.$cat->slug) ? 'text-secondary dark:text-secondary-fixed border-b-2 border-secondary dark:border-secondary-fixed' : 'text-on-surface dark:text-surface-variant font-bold hover:text-secondary dark:hover:text-secondary-fixed transition-colors duration-200' }} pb-1 font-headline-sm text-headline-sm tracking-tight whitespace-nowrap" href="{{ route('category.news', $cat->slug) }}">{{ mb_strtoupper($cat->name, 'UTF-8') }}</a>
                @endforeach
            </nav>
        </div>

        <!-- Mobile Menu Overlay -->
        <div id="mobile-menu-overlay" class="hidden fixed inset-0 z-[100] md:hidden" role="dialog" aria-modal="true" aria-label="Mobil Menü">
            <!-- Backdrop -->
            <div id="mobile-menu-backdrop" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

            <!-- Menu Panel -->
            <div class="absolute inset-y-0 left-0 w-4/5 max-w-sm bg-surface dark:bg-on-surface shadow-xl flex flex-col">
                <div class="flex items-center justify-between p-4 border-b border-outline-variant">
                    <a href="{{ route('home') }}" aria-label="Gece Haber Ana Sayfa">
                        <img src="{{ asset('images/logo-light.png') }}" alt="Gece Haber" class="h-9 w-auto object-contain dark:hidden">
                        <img src="{{ asset('images/logo-dark.png') }}" alt="Gece Haber" class="h-9 w-auto object-contain hidden dark:block">
                    </a>
                    <button type="button" id="mobile-menu-close" aria-label="Menüyü kapat" class="text-on-surface-variant dark:text-surface-variant cursor-pointer">
                        <span class="material-symbols-outlined text-2xl">close</span>
                    </button>
                </div>

                <div class="p-4 overflow-y-auto flex-1 flex flex-col gap-6">
                    <!-- Mobile Search -->
                    <form action="{{ route('home') }}" method="GET" class="relative" role="search">
                        <label for="mobile-search" class="sr-only">Haberlerde ara</label>
                        <input id="mobile-search" name="search" value="{{ request('search') }}" class="w-full bg-surface-container dark:bg-inverse-surface dark:text-inverse-on-surface border border-outline-variant rounded-full py-3 pl-4 pr-10 focus:outline-none focus:border-primary text-sm" placeholder="Haberlerde ara..." type="text"/>
                        <button type="submit" aria-label="Ara" class="material-symbols-outlined absolute right-4 top-1/2 transform -translate-y-1/2 text-on-surface-variant dark:text-surface-variant">search</button>
                    </form>

                    <!-- Mobile Navigation -->
                    <nav class="flex flex-col gap-4" aria-label="Mobil kategoriler">
                        <a class="text-lg font-bold {{ request()->routeIs('home') && !request()->has('tag') ? 'text-secondary' : 'text-on-surface dark:text-inverse-on-surface' }}" href="{{ route('home') }}">ANA SAYFA</a>
                        @foreach($globalCategories ?? [] as $cat)
                            <a class="text-lg font-bold {{ request()->is('kategori/'.$cat->slug) ? 'text-secondary' : 'text-on-surface dark:text-inverse-on-surface' }}" href="{{ route('category.news', $cat->slug) }}">{{ mb_strtoupper($cat->name, 'UTF-8') }}</a>
                        @endforeach
                    </nav>
                </div>

                <!-- Mobile Footer / Auth -->
                <div class="p-4 border-t border-outline-variant bg-surface-container-low dark:bg-inverse-surface">
                    @auth
                        <div class="flex flex-col gap-3">
                            <div class="flex items-center gap-2 text-on-surface dark:text-inverse-on-surface font-bold">
                                <span class="material-symbols-outlined">account_circle</span>
                                {{ auth()->user()->name }}
                            </div>
                            @if(auth()->user()->hasAnyRole(['Admin', 'Editor', 'Super Admin']))
                                <a href="{{ route('admin.index') }}" class="flex items-center gap-2 text-primary dark:text-primary-fixed hover:underline font-semibold">
                                    <span class="material-symbols-outlined text-sm">settings</span> Yönetim Paneli
                                </a>
                            @endif
                            <a href="{{ route('profile') }}" class="flex items-center gap-2 text-on-surface dark:text-inverse-on-surface hover:underline">
                                <span class="material-symbols-outlined text-sm">manage_accounts</span> Profilim
                            </a>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="flex items-center gap-2 text-error hover:underline w-full text-left cursor-pointer">
                                    <span class="material-symbols-outlined text-sm">logout</span> Çıkış Yap
                                </button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="flex items-center justify-center gap-2 bg-primary text-on-primary px-4 py-3 rounded-full font-bold w-full">
                            <span class="material-symbols-outlined">login</span> Giriş Yap
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- BREAKING NEWS TICKER -->
    @yield('breaking_news_ticker')

    <!-- FLASH MESSAGES -->
    @if(session('success'))
        <div class="bg-secondary-container text-on-secondary-container border-b-2 border-secondary px-4 py-3 font-headline-sm text-headline-sm text-center shadow-lg relative z-40">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-error-container text-on-error-container border-b-2 border-error px-4 py-3 font-headline-sm text-headline-sm text-center shadow-lg relative z-40">
            {{ session('error') }}
        </div>
    @endif

    <!-- MAIN CONTENT -->
    @yield('content')

    <!-- Footer -->
    <footer class="bg-primary dark:bg-on-primary-fixed w-full mt-[48px] py-12 px-[20px] border-t border-primary-fixed-dim">
        <div class="max-w-[1280px] mx-auto flex flex-col items-center gap-6">
            <a href="{{ route('home') }}" aria-label="Gece Haber Ana Sayfa" class="hover:opacity-90 transition-opacity">
                <img src="{{ asset('images/logo-dark.png') }}" alt="Gece Haber" width="200" height="82" class="h-12 w-auto object-contain">
            </a>
            <nav class="flex flex-wrap justify-center gap-4" aria-label="Kurumsal bağlantılar">
                <a class="font-body-md text-body-md text-surface-variant dark:text-outline-variant hover:text-white hover:underline transition-all" href="{{ route('home') }}">Ana Sayfa</a>
                <a class="font-body-md text-body-md text-surface-variant dark:text-outline-variant hover:text-white hover:underline transition-all" href="{{ route('feed') }}">RSS Beslemesi</a>
                <a class="font-body-md text-body-md text-surface-variant dark:text-outline-variant hover:text-white hover:underline transition-all" href="{{ route('sitemap') }}">Site Haritası</a>
                <a class="font-body-md text-body-md text-surface-variant dark:text-outline-variant hover:text-white hover:underline transition-all" href="{{ route('sitemap.news') }}">Google News Haritası</a>
            </nav>
            <div class="flex gap-4 mt-4">
                <a href="{{ route('feed') }}" aria-label="RSS" class="w-10 h-10 rounded-full bg-on-primary/10 flex items-center justify-center text-on-primary hover:bg-secondary transition-colors"><span class="material-symbols-outlined">rss_feed</span></a>
                <a href="{{ route('sitemap') }}" aria-label="Site Haritası" class="w-10 h-10 rounded-full bg-on-primary/10 flex items-center justify-center text-on-primary hover:bg-secondary transition-colors"><span class="material-symbols-outlined">public</span></a>
            </div>
            <p class="font-meta-data text-meta-data text-surface-variant/70 mt-6 text-center">
                © {{ date('Y') }} Gece Haber. Tüm hakları saklıdır.<br>İçerikler izinsiz kopyalanamaz.
            </p>
        </div>
    </footer>

    @yield('structured_data')
</body>
</html>
