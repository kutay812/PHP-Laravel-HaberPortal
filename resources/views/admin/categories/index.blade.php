@extends('layouts.admin.app')

@section('title', 'Kategoriler')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Kategoriler</h1>
        @can('categories.create')
            <a href="{{ route('admin.categories.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-plus fa-sm me-2"></i>Yeni Kategori
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
                                <button class="btn btn-outline-danger" type="button" id="clearSearch" aria-label="Aramayı temizle" style="display: none;">
                                    <i class="fas fa-times fa-sm"></i>
                                </button>
                            </div>
                        </div>
                        <div id="searchResults" class="position-absolute w-100 mt-1 shadow-sm bg-white rounded border" style="display: none; z-index: 1050;"></div>
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
                            <th>Slug</th>
                            <th>Haber Sayısı</th>
                            <th style="width: 130px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td class="font-weight-bold">{{ $category->name }}</td>
                                <td><code>{{ $category->slug }}</code></td>
                                <td><span class="badge badge-primary">{{ $category->news_count }} haber</span></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        @can('categories.edit')
                                            <a href="{{ route('admin.categories.edit', $category) }}"
                                               class="btn btn-xs btn-outline-primary mr-1"
                                               title="Düzenle">
                                                <i class="fas fa-edit fa-sm"></i>
                                            </a>
                                        @endcan
                                        @can('categories.delete')
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
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <i class="fas fa-folder fa-2x mb-3 d-block"></i>
                                    Henüz kategori bulunmuyor.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
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
