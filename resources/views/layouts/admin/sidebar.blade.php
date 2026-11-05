<!-- Sidebar -->
<aside class="sidebar">
    <!-- Brand -->
    <a href="{{ route('admin.index') }}" class="sidebar-brand text-decoration-none">
        <img src="{{ asset('images/logo-dark.png') }}" alt="Gece Haber" class="sidebar-logo">
    </a>

    <!-- User Profile -->
    <div class="sidebar-profile">
        <div class="profile-image">
            <img src="{{ auth()->user()->profile_image_url }}" alt="Profil Resmi">
        </div>
        <div class="profile-info">
            <div class="profile-name">{{ auth()->user()->name }}</div>
            <div class="profile-role">
                @foreach(auth()->user()->roles as $role)
                    <span class="role-badge">{{ $role->name }}</span>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Ana Menü -->
    <div class="sidebar-menu">
        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.index') ? 'active' : '' }}"
                   href="{{ route('admin.index') }}">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Yönetim Paneli</span>
                </a>
            </li>

            <!-- İçerik Yönetimi Başlığı -->
            <li class="nav-header">İçerik Yönetimi</li>

            <!-- Haberler -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.news.*') ? 'active' : '' }}"
                   href="{{ route('admin.news.index') }}">
                    <i class="fas fa-newspaper"></i>
                    <span>Haberler</span>
                </a>
            </li>

            <!-- Kategoriler -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"
                   href="{{ route('admin.categories.index') }}">
                    <i class="fas fa-folder"></i>
                    <span>Kategoriler</span>
                </a>
            </li>

            <!-- Etiketler -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.tags.*') ? 'active' : '' }}"
                   href="{{ route('admin.tags.index') }}">
                    <i class="fas fa-tags"></i>
                    <span>Etiketler</span>
                </a>
            </li>

            <!-- Haber Kaynakları (RSS) -->
            @hasanyrole('Super Admin|Admin')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.sources.*') ? 'active' : '' }}"
                   href="{{ route('admin.sources.index') }}">
                    <i class="fas fa-rss"></i>
                    <span>Haber Kaynakları</span>
                </a>
            </li>
            @endhasanyrole

            <!-- Kullanıcı Yönetimi Başlığı -->
            <li class="nav-header">Hesap &amp; Sistem</li>

            <!-- Kullanıcılar -->
            @can('users.view')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                   href="{{ route('admin.users.index') }}">
                    <i class="fas fa-users"></i>
                    <span>Kullanıcılar</span>
                </a>
            </li>
            @endcan

            <!-- Profil -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.profile') ? 'active' : '' }}"
                   href="{{ route('admin.profile') }}">
                    <i class="fas fa-user-cog"></i>
                    <span>Profilim</span>
                </a>
            </li>

            <!-- Siteyi Görüntüle -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('home') }}" target="_blank" rel="noopener">
                    <i class="fas fa-external-link-alt"></i>
                    <span>Siteyi Görüntüle</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Çıkış -->
    <div class="sidebar-bottom">
        <form action="{{ route('admin.logout') }}" method="POST">
            @csrf
            <button type="submit" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Çıkış Yap</span>
            </button>
        </form>
    </div>
</aside>

<style>
.sidebar {
    display: flex;
    flex-direction: column;
    background: #1e293b;
    width: 265px;
    min-width: 265px;
    position: sticky;
    top: 0;
    height: 100vh;
}

.sidebar-brand {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.1rem 1.25rem;
    background: rgba(0, 0, 0, 0.22);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.sidebar-logo {
    height: 40px;
    width: auto;
    object-fit: contain;
}

.sidebar-profile {
    padding: 1.1rem 1.25rem;
    background: rgba(255, 255, 255, 0.03);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    align-items: center;
    gap: 0.85rem;
}

.profile-image {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid rgba(255, 255, 255, 0.2);
    flex-shrink: 0;
}

.profile-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-info {
    flex: 1;
    min-width: 0;
}

.profile-name {
    color: #fff;
    font-weight: 700;
    font-size: 0.95rem;
    margin-bottom: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.profile-role {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
}

.role-badge {
    background: rgba(52, 152, 219, 0.28);
    color: #e0f2fe;
    font-size: 0.68rem;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    white-space: nowrap;
}

.sidebar-menu {
    flex: 1;
    padding: 1rem 0;
    overflow-y: auto;
}

.nav-header {
    color: rgba(255, 255, 255, 0.42);
    font-size: 0.72rem;
    padding: 1.1rem 1.5rem 0.4rem;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.6px;
}

.nav-link {
    color: rgba(255, 255, 255, 0.75) !important;
    padding: 0.72rem 1.5rem;
    transition: all 0.2s ease;
    border-left: 4px solid transparent;
    font-size: 0.93rem;
    display: flex;
    align-items: center;
}

.nav-link:hover {
    color: #fff !important;
    background: rgba(255, 255, 255, 0.06);
    border-left-color: #3498db;
}

.nav-link.active {
    color: #fff !important;
    background: rgba(52, 152, 219, 0.16);
    border-left-color: #3498db;
    font-weight: 700;
}

.nav-link i {
    width: 20px;
    text-align: center;
    margin-right: 0.75rem;
    font-size: 0.98rem;
}

.sidebar-bottom {
    padding: 1rem;
    background: rgba(0, 0, 0, 0.2);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.logout-btn {
    width: 100%;
    padding: 0.75rem;
    color: #fecaca;
    background: rgba(231, 76, 60, 0.18);
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.92rem;
    font-weight: 600;
}

.logout-btn:hover {
    color: #fff;
    background: rgba(231, 76, 60, 0.35);
}

.logout-btn i {
    margin-right: 0.6rem;
}

@media (max-width: 768px) {
    .sidebar {
        width: 76px;
        min-width: 76px;
    }

    .sidebar-logo {
        height: 24px;
    }

    .profile-info,
    .nav-link span,
    .logout-btn span,
    .nav-header {
        display: none;
    }

    .sidebar-profile,
    .nav-link,
    .logout-btn {
        padding: 0.9rem;
        justify-content: center;
    }

    .nav-link i,
    .logout-btn i {
        margin: 0;
        font-size: 1.15rem;
    }
}
</style>
