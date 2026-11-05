@extends('layouts.admin.app')

@section('title', 'Yeni Haber Kaynağı Ekle')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Yeni Haber Kaynağı Ekle</h1>
        <a href="{{ route('admin.sources.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Geri Dön
        </a>
    </div>

    <div class="card shadow-sm">
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

            <form action="{{ route('admin.sources.store') }}" method="POST">
                @csrf

                <div class="form-group mb-3">
                    <label for="name" class="font-weight-bold">Kaynak Adı *</label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="örn: TRT Haber, Anadolu Ajansı" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mb-3">
                    <label for="url" class="font-weight-bold">RSS Feed URL *</label>
                    <input type="url" name="url" id="url" class="form-control @error('url') is-invalid @enderror" value="{{ old('url') }}" placeholder="örn: https://www.trthaber.com/sondakika_articles.rss" required>
                    @error('url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="type" class="font-weight-bold">Kaynak Türü *</label>
                            <select name="type" id="type" class="form-select">
                                <option value="rss" @selected(old('type', 'rss') === 'rss')>RSS Feed</option>
                                <option value="api" @selected(old('type') === 'api')>API Endpoint</option>
                                <option value="scraper" @selected(old('type') === 'scraper')>Custom Web Scraper</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="reliability_score" class="font-weight-bold">Güvenilirlik Skoru (0-100) *</label>
                            <input type="number" name="reliability_score" id="reliability_score" class="form-control" value="{{ old('reliability_score', 80) }}" min="0" max="100" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="default_category_id" class="font-weight-bold">Varsayılan Kategori</label>
                            <select name="default_category_id" id="default_category_id" class="form-select">
                                <option value="">Seçiniz</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('default_category_id') === (string) $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="fetch_interval_minutes" class="font-weight-bold">Çekim Sıklığı (Dakika) *</label>
                            <input type="number" name="fetch_interval_minutes" id="fetch_interval_minutes" class="form-control" value="{{ old('fetch_interval_minutes', 30) }}" min="5" required>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label for="website_url" class="font-weight-bold">Kaynak Web Sitesi URL</label>
                    <input type="url" name="website_url" id="website_url" class="form-control" value="{{ old('website_url') }}" placeholder="https://www.trthaber.com">
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="hidden" name="is_active" value="0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" @checked(old('is_active', '1'))>
                            <label class="custom-control-label font-weight-bold" for="is_active">Aktif (Çekim yapılır)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <input type="hidden" name="auto_publish" value="0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="auto_publish" name="auto_publish" value="1" @checked(old('auto_publish', '1'))>
                            <label class="custom-control-label font-weight-bold" for="auto_publish">Otomatik Yayınla</label>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <label for="notes" class="font-weight-bold">Notlar</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Kaynak hakkında özel notlar...">{{ old('notes') }}</textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.sources.index') }}" class="btn btn-secondary mr-2">İptal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
