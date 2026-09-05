@extends('layouts.admin.app')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Kategoriler</h1>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-sm btn-primary">
            <i class="fas fa-plus fa-sm me-2"></i>Yeni Kategori
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Search Form -->
    <div class="search-wrapper">
        <div class="card shadow-sm mb-4">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col position-relative">
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control" 
                                   id="searchInput"
                                   placeholder="Kategori adı ara..."
                                   autocomplete="off">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button" id="clearSearch" style="display: none;">
                                    <i class="fas fa-times fa-sm"></i>
                                </button>
                            </div>
                        </div>
                        <div id="searchResults" class="position-absolute w-100 mt-1 shadow-sm bg-white rounded border" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="categoriesTable">
                    <thead class="table-light">
                        <tr>
                            <th>Kategori Adı</th>
                            <th>Haber Sayısı</th>
                            <th style="width: 120px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td>{{ $category->name }}</td>
                                <td>{{ $category->news_count }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.categories.edit', $category) }}" 
                                           class="btn btn-xs btn-outline-primary"
                                           title="Düzenle">
                                            <i class="fas fa-edit fa-sm"></i>
                                        </a>
                                        <form action="{{ route('admin.categories.destroy', $category) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('Bu kategoriyi silmek istediğinizden emin misiniz?');">
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
                                <td colspan="3" class="text-center py-4 text-muted">
                                    <i class="fas fa-folder fa-2x mb-3 d-block"></i>
                                    Henüz kategori bulunmuyor.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted small">
                    Toplam {{ $categories->total() }} kategoriden {{ $categories->firstItem() ?? 0 }}-{{ $categories->lastItem() ?? 0 }} arası gösteriliyor
                </div>
                <div>
                    {{ $categories->links() }}
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
        searchUrl: '{{ route("admin.categories.search") }}',
        redirectUrl: '{{ route("admin.categories.index") }}'
    });
});
</script>
@endpush
@endsection