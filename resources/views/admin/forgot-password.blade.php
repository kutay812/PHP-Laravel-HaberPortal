@extends('layouts.admin.simple')

@section('content')
<div class="container">
    <div class="row justify-content-center min-vh-100 align-items-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
            <div class="text-center mb-4">
                <a href="{{ route('home') }}" class="d-inline-block mb-3">
                    <img src="{{ asset('images/logo-light.png') }}" alt="Gece Haber" style="height: 60px; width: auto;">
                </a>
                <h1 class="h4 fw-bold text-dark mb-1">Şifre Sıfırlama</h1>
                <p class="text-muted small">Kayıtlı e-posta adresinizi girin</p>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.password.email') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="email" class="form-label fw-semibold">E-posta Adresi</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-envelope text-muted"></i>
                                </span>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="email" name="email" value="{{ old('email') }}" required autofocus>
                                @error('email')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2 fw-semibold">
                                <i class="fas fa-paper-plane me-2"></i>Sıfırlama Bağlantısı Gönder
                            </button>

                            <a href="{{ route('admin.login') }}" class="btn btn-link text-center text-decoration-none">
                                <i class="fas fa-arrow-left me-2"></i>Giriş Sayfasına Dön
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
