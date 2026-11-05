@extends('layouts.admin.app')

@section('title', 'Yönetim Paneli')

@section('content')
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <h1 class="h3 mb-0 text-gray-800">Yönetim Paneli</h1>

        <div class="d-flex flex-wrap gap-2">
            @can('news.create')
                <a href="{{ route('admin.news.create') }}" class="btn btn-sm btn-primary mr-2">
                    <i class="fas fa-plus fa-sm me-1"></i> Yeni Haber Ekle
                </a>
            @endcan

            <!-- Fetch All News Form -->
            <form action="{{ route('admin.fetch-news') }}" method="POST" class="d-inline mr-2">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-success font-weight-bold" onclick="return confirm('Bu işlem tüm aktif RSS kaynaklarına bağlanıp yeni haberleri arka planda indirecek. Onaylıyor musunuz?')">
                    <i class="fas fa-robot fa-sm me-1"></i> Tüm Kaynaklardan Haber Çek
                </button>
            </form>

            @hasanyrole('Super Admin|Admin')
                <!-- Clear Cache Form -->
                <form action="{{ route('admin.clear-cache') }}" method="POST" class="d-inline mr-2">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-broom fa-sm me-1"></i> Önbelleği Temizle
                    </button>
                </form>
            @endhasanyrole

            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-external-link-alt fa-sm me-1"></i> Siteyi Görüntüle
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Kapat"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Kapat"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    <!-- Stat Cards Row -->
    <div class="row">
        <!-- News Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="{{ route('admin.news.index') }}" class="text-decoration-none">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Toplam Haber</div>
                                <div class="h5 mb-0 font-weight-bold text-dark">{{ $stats['news_count'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-newspaper fa-2x text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Categories Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="{{ route('admin.categories.index') }}" class="text-decoration-none">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Toplam Kategori</div>
                                <div class="h5 mb-0 font-weight-bold text-dark">{{ $stats['category_count'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-folder fa-2x text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Sources Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Haber Kaynakları (RSS)</div>
                            <div class="h5 mb-0 font-weight-bold text-dark">{{ $stats['source_count'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-rss fa-2x text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Views Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Toplam Okunma</div>
                            <div class="h5 mb-0 font-weight-bold text-dark">{{ number_format($stats['total_views']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-eye fa-2x text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row Stats -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-3 mb-xl-0">
            <div class="card shadow-sm h-100">
                <div class="card-body py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-xs text-muted text-uppercase font-weight-bold">Kullanıcılar</div>
                        <div class="h3 mb-0 font-weight-bold">{{ $stats['user_count'] }}</div>
                    </div>
                    <i class="fas fa-users text-muted fa-2x"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-3 mb-xl-0">
            <a href="{{ route('admin.tags.index') }}" class="text-decoration-none text-dark">
                <div class="card shadow-sm h-100">
                    <div class="card-body py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs text-muted text-uppercase font-weight-bold">Etiketler</div>
                            <div class="h3 mb-0 font-weight-bold">{{ $stats['tag_count'] }}</div>
                        </div>
                        <i class="fas fa-tags text-muted fa-2x"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-4 col-md-12">
            <div class="card shadow-sm h-100">
                <div class="card-body py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-xs text-muted text-uppercase font-weight-bold">Yorumlar</div>
                        <div class="h3 mb-0 font-weight-bold">{{ $stats['comment_count'] }}</div>
                    </div>
                    <i class="fas fa-comments text-muted fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Tables / Lists Row -->
    <div class="row">
        <!-- Latest News List -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white font-weight-bold text-gray-800 d-flex justify-content-between align-items-center">
                    <span>Son Eklenen Haberler</span>
                    <a href="{{ route('admin.news.index') }}" class="btn btn-xs btn-link p-0 text-primary">Tümünü Gör</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <tbody>
                                @forelse($latestNews as $item)
                                    <tr>
                                        <td class="pl-3">
                                            <a href="{{ route('admin.news.edit', $item) }}" class="font-weight-bold text-dark text-decoration-none">
                                                {{ Str::limit($item->title, 55) }}
                                            </a>
                                            <div class="text-xs text-muted">
                                                {{ $item->category?->name ?? 'Kategorisiz' }} &bull; {{ $item->created_at?->format('d.m.Y H:i') }}
                                            </div>
                                        </td>
                                        <td class="text-right pr-3">
                                            <span class="badge {{ $item->is_active ? 'badge-success' : 'badge-danger' }}">
                                                {{ $item->is_active ? 'Yayında' : 'Pasif' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center py-4 text-muted">Haber bulunmuyor.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- News Sources List -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white font-weight-bold text-gray-800 d-flex justify-content-between align-items-center">
                    <span>Haber Kaynakları Durumu</span>
                    @hasanyrole('Super Admin|Admin')
                        <a href="{{ route('admin.sources.index') }}" class="btn btn-xs btn-link p-0 text-primary">Yönet</a>
                    @endhasanyrole
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead>
                                <tr class="text-xs text-muted uppercase">
                                    <th class="pl-3">Kaynak</th>
                                    <th>Son Çekilme</th>
                                    @hasanyrole('Super Admin|Admin')
                                        <th class="text-right pr-3">İşlem</th>
                                    @endhasanyrole
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sources as $source)
                                    <tr>
                                        <td class="pl-3 font-weight-bold">
                                            {{ $source->name }}
                                            <div class="text-xs text-muted">Güven Skoru: %{{ $source->reliability_score }}</div>
                                        </td>
                                        <td class="small">
                                            {{ $source->last_fetched_at ? $source->last_fetched_at->format('d.m.Y H:i') : 'Hiç çekilmedi' }}
                                        </td>
                                        @hasanyrole('Super Admin|Admin')
                                            <td class="text-right pr-3">
                                                <form action="{{ route('admin.sources.fetch', $source) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs btn-outline-success">
                                                        <i class="fas fa-sync fa-xs"></i> Şimdi Çek
                                                    </button>
                                                </form>
                                            </td>
                                        @endhasanyrole
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">Kayıtlı haber kaynağı bulunmuyor.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
