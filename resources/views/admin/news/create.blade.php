@extends('layouts.admin.app')

@section('title', 'Yeni Haber Ekle')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Yeni Haber Ekle</h1>
        <a href="{{ route('admin.news.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Geri Dön
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Kapat">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <form action="{{ route('admin.news.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-md-8">
                        <!-- Title -->
                        <div class="mb-3">
                            <label for="title" class="form-label">Başlık *</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                   id="title" name="title" value="{{ old('title') }}" required minlength="5">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Kısa Özet (Açıklama) *</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3" required minlength="10">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Content -->
                        <div class="mb-3">
                            <label for="content" class="form-label">Haber İçeriği *</label>
                            <textarea class="form-control @error('content') is-invalid @enderror"
                                      id="content" name="content" rows="12" required minlength="30">{{ old('content') }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Category -->
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Kategori *</label>
                            <select class="form-select @error('category_id') is-invalid @enderror"
                                    id="category_id" name="category_id" required>
                                <option value="">Kategori Seçin</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                            {{ (string) old('category_id') === (string) $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Etiketler -->
                        <div class="mb-3">
                            <label for="tags" class="form-label">Etiketler (virgülle ayırın)</label>
                            <input type="text" class="form-control @error('tags') is-invalid @enderror"
                                   id="tags" name="tags" value="{{ old('tags') }}" placeholder="örn: ekonomi, borsa, merkez bankası">
                            @error('tags')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Yeni Görsel Yükle -->
                        <div class="mb-3">
                            <label for="new_image" class="form-label">Kapak Görseli Yükle</label>
                            <input type="file" class="form-control @error('new_image') is-invalid @enderror"
                                   id="new_image" name="new_image" accept="image/*">
                            @error('new_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">JPG, PNG, WEBP, GIF (Maks. 10MB)</small>
                        </div>

                        <!-- Editorial Placement Flags -->
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="form-label d-block mb-2">Vitrin Konumu</label>

                            <input type="hidden" name="is_featured" value="0">
                            <div class="custom-control custom-switch mb-2">
                                <input type="checkbox" class="custom-control-input" id="is_featured" name="is_featured" value="1" @checked(old('is_featured'))>
                                <label class="custom-control-label" for="is_featured">Manşette Göster</label>
                            </div>

                            <input type="hidden" name="is_breaking" value="0">
                            <div class="custom-control custom-switch mb-2">
                                <input type="checkbox" class="custom-control-input" id="is_breaking" name="is_breaking" value="1" @checked(old('is_breaking'))>
                                <label class="custom-control-label" for="is_breaking">Son Dakika Bandı</label>
                            </div>

                            <input type="hidden" name="is_editor_pick" value="0">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_editor_pick" name="is_editor_pick" value="1" @checked(old('is_editor_pick'))>
                                <label class="custom-control-label" for="is_editor_pick">Editörün Seçimi</label>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mb-4">
                            <label class="form-label d-block">Yayın Durumu</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio"
                                       name="is_active" id="active" value="1"
                                       {{ (string) old('is_active', '1') === '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="active">Yayında (Aktif)</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio"
                                       name="is_active" id="inactive" value="0"
                                       {{ (string) old('is_active') === '0' ? 'checked' : '' }}>
                                <label class="form-check-label" for="inactive">Taslak (Pasif)</label>
                            </div>
                            @error('is_active')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save me-2"></i>Haberi Kaydet
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
