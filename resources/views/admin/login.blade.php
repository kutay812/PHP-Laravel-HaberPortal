@extends('layouts.admin.simple')

@section('content')
<div class="container">
    <div class="row justify-content-center min-vh-100 align-items-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
            <div class="text-center mb-4">
                <a href="{{ route('home') }}" class="d-inline-block mb-3">
                    <img src="{{ asset('images/logo-light.png') }}" alt="Gece Haber" style="height: 64px; width: auto;">
                </a>
                <h1 class="h4 font-weight-bold text-dark mb-1">Yönetim Paneli Girişi</h1>
                <p class="text-muted small mb-0">Devam etmek için yetkili hesabınızla giriş yapın</p>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if(session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.login.post') }}">
                        @csrf

                        <div class="mb-3">
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

                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">Şifre</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-lock text-muted"></i>
                                </span>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                       id="password" name="password" required>
                                @error('password')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2 fw-semibold">
                                <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                            </button>

                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <a href="{{ route('home') }}" class="btn btn-link btn-sm text-secondary text-decoration-none px-0">
                                    <i class="fas fa-arrow-left me-1"></i>Siteye Dön
                                </a>
                                <a href="{{ route('admin.password.request') }}" class="btn btn-link btn-sm text-decoration-none px-0">
                                    Şifremi Unuttum
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
