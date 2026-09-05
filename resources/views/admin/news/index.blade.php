@extends('layouts.admin.app')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Haberler</h1>
        <a href="{{ route('admin.news.create') }}" class="btn btn-sm btn-primary">
            <i class="fas fa-plus fa-sm me-2"></i>Yeni Haber
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Search Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col position-relative">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-gray-400"></i>
                        </span>
                        <input type="text" 
                               class="form-control border-start-0 ps-0" 
                               id="searchInput"
                               placeholder="Aramak için yazın..."
                               autocomplete="off">
                        <button class="btn btn-link text-danger" 
                                type="button" 
                                id="clearSearch" 
                                style="display: none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div id="searchResults" class="position-absolute w-100 mt-1 shadow-lg bg-white rounded-lg border-0" style="display: none; z-index: 1050;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="newsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">Görsel</th>
                            <th>Başlık</th>
                            <th>Kategori</th>
                            <th>Yazar</th>
                            <th>Durum</th>
                            <th>Görüntülenme</th>
                            <th>Tarih</th>
                            <th style="width: 120px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($news as $item)
                            <tr>
                                <td>
                                    @if($item->image_url)
                                        <img src="{{ $item->image_url }}" alt="" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                                    @else
                                        <div class="bg-light text-muted d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.news.show', $item) }}" class="text-decoration-none">
                                        {{ Str::limit($item->title, 50) }}
                                    </a>
                                </td>
                                <td>{{ $item->categories && $item->categories->count() > 0 ? $item->categories->pluck('name')->join(', ') : ($item->category->name ?? 'Kategorisiz') }}</td>
                                <td>{{ $item->user->name ?? 'Bilinmiyor' }}</td>
                                <td>
                                    @switch($item->status)
                                        @case('published')
                                            <span class="badge bg-success rounded-pill">Yayında</span>
                                            @break
                                        @case('draft')
                                            <span class="badge bg-warning rounded-pill">Taslak</span>
                                            @break
                                        @case('archived')
                                            <span class="badge bg-secondary rounded-pill">Arşivlenmiş</span>
                                            @break
                                    @endswitch
                                </td>
                                <td>{{ $item->views }}</td>
                                <td>{{ $item->created_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.news.edit', $item) }}" 
                                           class="btn btn-xs btn-outline-primary"
                                           title="Düzenle">
                                            <i class="fas fa-edit fa-sm"></i>
                                        </a>
                                        <form action="{{ route('admin.news.destroy', $item) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('Bu haberi silmek istediğinizden emin misiniz?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="btn btn-xs btn-outline-danger" 
                                                    title="Sil">
                                                <i class="fas fa-trash fa-sm"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-newspaper fa-2x mb-3 d-block"></i>
                                    Henüz haber bulunmuyor.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted small">
                    Toplam {{ $news->total() }} haberden {{ $news->firstItem() ?? 0 }}-{{ $news->lastItem() ?? 0 }} arası gösteriliyor
                </div>
                <div>
                    {{ $news->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    initAdminAutocomplete({
        inputId: 'searchInput',
        resultsId: 'searchResults',
        clearId: 'clearSearch',
        searchUrl: '{{ route("admin.news.search") }}',
        redirectUrl: '{{ route("admin.news.index") }}'
    });
});
</script>
@endpush
@endsection
