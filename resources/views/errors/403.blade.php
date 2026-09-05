@extends('layouts.app')

@section('title', '403 - Yetkisiz Erişim')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center bg-bg-primary px-4">
    <div class="max-w-2xl w-full text-center space-y-8">
        {{-- 403 Graphic --}}
        <div class="relative inline-block select-none">
            <h1 class="text-[150px] md:text-[200px] font-extrabold text-border leading-none font-heading tracking-tighter opacity-30">
                403
            </h1>
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="bg-red-600 text-white px-4 py-2 rounded-lg font-bold text-xl md:text-2xl tracking-wider shadow-xl transform -rotate-6">
                    YETKİSİZ ERİŞİM
                </div>
            </div>
        </div>

        {{-- Text Content --}}
        <div class="space-y-4 relative z-10">
            <h2 class="text-2xl md:text-3xl font-bold text-text">Bu sayfaya erişim yetkiniz bulunmamaktadır.</h2>
            <p class="text-text-muted text-lg max-w-lg mx-auto">
                Bu alanı görüntülemek için gerekli kullanıcı rollerine veya izinlere sahip değilsiniz. Giriş yapmış olduğunuz hesabı kontrol edebilir veya ana sayfaya dönebilirsiniz.
            </p>
        </div>

        {{-- Actions --}}
        <div class="max-w-md mx-auto pt-6">
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('home') }}" class="px-8 py-3 bg-accent text-white font-bold rounded-lg hover:bg-accent-hover transition-colors flex items-center gap-2 shadow-lg shadow-accent/20">
                    <i class="fas fa-home"></i> Ana Sayfaya Dön
                </a>
                <button onclick="window.history.back()" class="px-8 py-3 bg-bg-secondary text-text font-bold rounded-lg border border-border hover:bg-border transition-colors flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i> Geri Dön
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
