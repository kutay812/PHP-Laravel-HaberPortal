@extends('layouts.admin.app')

@section('title', 'Yeni Etiket Ekle')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Yeni Etiket Ekle</h1>
        <a href="{{ route('admin.tags.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Geri Dön
        </a>
    </div>

    <div class="card shadow-sm" style="max-width: 560px;">
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

            <form action="{{ route('admin.tags.store') }}" method="POST">
                @csrf

                <div class="form-group mb-3">
                    <label for="name" class="font-weight-bold">Etiket Adı *</label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="örn: ekonomi, teknoloji" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mb-4">
                    <label for="color" class="font-weight-bold">Renk (HEX) *</label>
                    <div class="input-group">
                        <input type="color" name="color" id="color" class="form-control mr-2" style="max-width: 80px; padding: 0.2rem;" value="{{ old('color', '#3576f6') }}" required>
                        <input type="text" id="color_text" class="form-control" value="{{ old('color', '#3576f6') }}" readonly>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('admin.tags.index') }}" class="btn btn-secondary mr-2">İptal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('color').addEventListener('input', function(e) {
        document.getElementById('color_text').value = e.target.value;
    });
</script>
@endpush
@endsection
