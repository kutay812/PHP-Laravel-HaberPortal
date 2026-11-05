@extends('layouts.admin.app')

@section('title', 'Profilim')

@section('content')
<div class="container-fluid py-2">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 font-weight-bold">Profil Düzenle</h5>
                    <a href="{{ route('admin.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-2"></i>Geri Dön
                    </a>
                </div>
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

                    <form id="profile-form" action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center mb-3">
                                    <div class="profile-image-wrapper mb-3">
                                        <img id="profile-preview"
                                             src="{{ auth()->user()->profile_image_url }}"
                                             class="rounded-circle profile-image"
                                             alt="Profil Resmi">
                                    </div>
                                    <div class="mb-2">
                                        <label for="profile_image" class="btn btn-outline-primary btn-sm mb-0" style="cursor: pointer;">
                                            <i class="fas fa-upload me-2"></i>Resim Seç
                                        </label>
                                        <input type="file" id="profile_image" name="profile_image" class="d-none" accept="image/*">
                                    </div>
                                    @if(auth()->user()->profile_image)
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="if(confirm('Profil resmini kaldırmak istiyor musunuz?')) document.getElementById('remove-image').submit();">
                                            <i class="fas fa-trash me-1"></i>Resmi Kaldır
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Ad Soyad *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">E-posta *</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                           id="email" name="email" value="{{ old('email', auth()->user()->email) }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <hr class="my-4">
                                <p class="text-muted small mb-3">Şifrenizi değiştirmek istemiyorsanız aşağıdaki alanları boş bırakabilirsiniz.</p>

                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Mevcut Şifre</label>
                                    <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                                           id="current_password" name="current_password">
                                    @error('current_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Yeni Şifre</label>
                                    <input type="password" class="form-control @if($errors->has('password') || $errors->has('new_password')) is-invalid @endif"
                                           id="password" name="password">
                                    @if($errors->has('password') || $errors->has('new_password'))
                                        <div class="invalid-feedback">{{ $errors->first('password') ?: $errors->first('new_password') }}</div>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label">Yeni Şifre (Tekrar)</label>
                                    <input type="password" class="form-control"
                                           id="password_confirmation" name="password_confirmation">
                                </div>
                            </div>
                        </div>
                    </form>

                    @if(auth()->user()->profile_image)
                        <form id="remove-image" action="{{ route('admin.profile.image.delete') }}" method="POST" class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endif
                </div>
                <div class="card-footer bg-white py-3">
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4" form="profile-form">
                            <i class="fas fa-save me-2"></i>Profili Güncelle
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.profile-image-wrapper {
    width: 140px;
    height: 140px;
    margin: 0 auto;
    position: relative;
}

.profile-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border: 2px solid #eee;
}
</style>
@endpush

@push('scripts')
<script>
document.getElementById('profile_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 10 * 1024 * 1024) {
            alert('Dosya boyutu 10MB\'dan büyük olamaz.');
            this.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = function(ev) {
            document.getElementById('profile-preview').src = ev.target.result;
        };
        reader.readAsDataURL(file);
    }
});
</script>
@endpush
@endsection
