@extends('layouts.admin.app')

@section('title', 'Kategori Düzenle')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Kategori Düzenle: {{ $category->name }}</h1>
        <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Geri Dön
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Kapat"><span aria-hidden="true">&times;</span></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Kapat"><span aria-hidden="true">&times;</span></button>
                </div>
            @endif

            <form action="{{ route('admin.categories.update', $category) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-8">
                        <!-- Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Kategori Adı *</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $category->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Title (SEO) -->
                        <div class="mb-3">
                            <label for="title" class="form-label">Başlık (SEO)</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                   id="title" name="title" value="{{ old('title', $category->title) }}">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Açıklama</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3">{{ old('description', $category->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Main Category -->
                        <div class="mb-3">
                            <label for="main_category_id" class="form-label">Ana Kategori</label>
                            <select class="form-select @error('main_category_id') is-invalid @enderror"
                                    id="main_category_id" name="main_category_id">
                                <option value="">Ana Kategori Seçin (Opsiyonel)</option>
                                @foreach($mainCategories as $mainCategory)
                                    <option value="{{ $mainCategory->id }}"
                                            {{ (string) old('main_category_id', $category->main_category_id) === (string) $mainCategory->id ? 'selected' : '' }}>
                                        {{ $mainCategory->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('main_category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Image -->
                        <div class="mb-4">
                            <label for="image_id" class="form-label">Görsel</label>
                            <select class="form-select @error('image_id') is-invalid @enderror"
                                    id="image_id" name="image_id">
                                <option value="">Görsel Seçin (Opsiyonel)</option>
                                @foreach($images as $image)
                                    <option value="{{ $image->id }}"
                                            {{ (string) old('image_id', $category->image_id) === (string) $image->id ? 'selected' : '' }}
                                            data-preview="{{ asset('storage/' . $image->path) }}">
                                        {{ $image->name ?? $image->path }}
                                    </option>
                                @endforeach
                            </select>
                            @error('image_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <div id="image-preview" class="mt-2">
                                @if($category->image)
                                    <img src="{{ asset('storage/' . $category->image->path) }}"
                                         alt="Seçili görsel"
                                         class="img-thumbnail"
                                         style="max-width: 180px;">
                                @endif
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save me-2"></i>Değişiklikleri Kaydet
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const imageSelect = document.getElementById('image_id');
    const imagePreview = document.getElementById('image-preview');

    if (imageSelect && imagePreview) {
        imageSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const previewUrl = selectedOption ? selectedOption.getAttribute('data-preview') : '';

            if (previewUrl) {
                imagePreview.innerHTML = '<img src="' + previewUrl + '" alt="Seçili görsel" class="img-thumbnail" style="max-width: 180px;">';
            } else {
                imagePreview.innerHTML = '';
            }
        });
    }
});
</script>
@endpush
@endsection
