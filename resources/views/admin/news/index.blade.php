@extends('layouts.admin.app')

@section('title', 'Haberler')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Haberler</h1>
        @can('news.create')
            <a href="{{ route('admin.news.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-plus fa-sm me-2"></i>Yeni Haber
            </a>
        @endcan
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

    <!-- Search Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col position-relative">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white border-right-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                        </div>
                        <input type="text"
                               class="form-control border-left-0"
                               id="searchInput"
                               placeholder="Haber başlığı, açıklama veya kategori ara..."
                               autocomplete="off">
                        <div class="input-group-append">
                            <button class="btn btn-outline-danger"
                                    type="button"
                                    id="clearSearch"
                                    aria-label="Aramayı temizle"
                                    style="display: none;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div id="searchResults" class="position-absolute w-100 mt-1 shadow-lg bg-white rounded border" style="display: none; z-index: 1050;">
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
                            <th>Yazar / Kaynak</th>
                            <th>Durum</th>
                            <th>Görüntülenme</th>
                            <th>Tarih</th>
                            <th style="width: 150px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($news as $item)
                            @php
                                $statusValue = $item->status instanceof \BackedEnum ? $item->status->value : (string) $item->status;
                            @endphp
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
                                    <a href="{{ route('admin.news.show', $item) }}" class="font-weight-bold text-dark text-decoration-none">
                                        {{ Str::limit($item->title, 55) }}
                                    </a>
                                    <div class="mt-1">
                                        @if($item->is_breaking)
                                            <span class="badge bg-danger text-white py-1 px-2" style="font-size: 0.65rem;">Son Dakika</span>
                                        @endif
                                        @if($item->is_featured)
                                            <span class="badge bg-primary text-white py-1 px-2" style="font-size: 0.65rem;">Manşet</span>
                                        @endif
                                        @if($item->is_editor_pick)
                                            <span class="badge bg-info text-white py-1 px-2" style="font-size: 0.65rem;">Editör</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $item->categories && $item->categories->count() > 0 ? $item->categories->pluck('name')->join(', ') : ($item->category?->name ?? 'Kategorisiz') }}</td>
                                <td>{{ $item->user?->name ?? 'Otonom Bot' }}</td>
                                <td>
                                    @if(!$item->is_active || $statusValue === 'draft')
                                        <span class="badge bg-warning text-dark">Taslak / Pasif</span>
                                    @elseif($statusValue === 'published')
                                        <span class="badge bg-success text-white">Yayında</span>
                                    @elseif($statusValue === 'archived')
                                        <span class="badge bg-secondary text-white">Arşivlenmiş</span>
                                    @else
                                        <span class="badge bg-light text-dark">{{ $statusValue }}</span>
                                    @endif
                                </td>
                                <td>{{ number_format((int) $item->views) }}</td>
                                <td>{{ $item->created_at?->format('d.m.Y H:i') }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.news.show', $item) }}"
                                           class="btn btn-xs btn-outline-info mr-1"
                                           title="Detay">
                                            <i class="fas fa-eye fa-sm"></i>
                                        </a>
                                        @can('news.edit')
                                            <a href="{{ route('admin.news.edit', $item) }}"
                                               class="btn btn-xs btn-outline-primary mr-1"
                                               title="Düzenle">
                                                <i class="fas fa-edit fa-sm"></i>
                                            </a>
                                        @endcan
                                        @can('news.delete')
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
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-newspaper fa-2x mb-3 d-block"></i>
                                    Henüz haber bulunmuyor.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
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
