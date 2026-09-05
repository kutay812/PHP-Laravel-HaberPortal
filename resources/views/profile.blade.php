@extends('layouts.app')

@section('title', 'Profilimi Düzenle')

@section('content')
<div class="bg-background min-h-[70vh] flex items-center justify-center py-12 px-4 transition-colors duration-200">
    <div class="max-w-md w-full bg-surface dark:bg-surface-container p-8 rounded-2xl border border-outline-variant shadow-sm transition-colors flex flex-col gap-6">
        
        <div class="text-center">
            <h2 class="text-2xl font-extrabold text-on-surface tracking-tight font-display">Profil Ayarları</h2>
            <p class="text-xs text-on-surface-variant mt-1">Hesap bilgilerinizi güncelleyin</p>
        </div>

        @if(session('success'))
            <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 text-xs p-3 rounded-xl">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="flex flex-col gap-4">
            @csrf
            @method('PUT')

            <!-- Profile Image Preview -->
            <div class="flex flex-col items-center gap-2 mb-2">
                <img src="{{ $user->profile_image 
                    ? asset('storage/' . $user->profile_image) 
                    : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=3576f6&color=fff' }}"
                    class="rounded-full border-4 border-primary/20 object-cover w-24 h-24 shadow-md transition-transform hover:scale-105" alt="Profil Foto">
                
                <span class="text-xs text-on-surface-variant font-bold uppercase tracking-wide">Profil Fotoğrafı</span>
            </div>

            <!-- Upload new image -->
            <div>
                <label for="profile_image" class="text-xs text-on-surface-variant font-bold mb-1.5 block">Yeni Fotoğraf Yükle</label>
                <input type="file" name="profile_image" id="profile_image" accept="image/*"
                       class="w-full text-xs text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-full file:border file:border-outline-variant file:text-xs file:font-semibold file:bg-surface-container file:text-on-surface hover:file:bg-surface-container-high transition-colors">
                @error('profile_image')
                    <small class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</small>
                @enderror
            </div>

            <!-- Name -->
            <div>
                <label for="name" class="text-xs text-on-surface font-semibold mb-1.5 block">Ad Soyad</label>
                <input type="text" name="name" id="name" required value="{{ old('name', $user->name) }}"
                       class="w-full px-4 py-2.5 rounded-xl bg-surface-container-low dark:bg-surface-container border border-outline-variant text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors">
                @error('name')
                    <small class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</small>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="text-xs text-on-surface font-semibold mb-1.5 block">E-Posta Adresi</label>
                <input type="email" name="email" id="email" required value="{{ old('email', $user->email) }}"
                       class="w-full px-4 py-2.5 rounded-xl bg-surface-container-low dark:bg-surface-container border border-outline-variant text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors">
                @error('email')
                    <small class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</small>
                @enderror
            </div>

            <!-- User Roles Info -->
            <div>
                <label class="text-xs text-on-surface font-semibold mb-1.5 block">Yetkileriniz</label>
                <div class="w-full px-4 py-2.5 rounded-xl bg-surface-container border border-outline-variant/60 text-xs text-on-surface-variant select-none font-semibold uppercase tracking-wider">
                    {{ $roles && count($roles) ? implode(' / ', $roles) : 'Kullanıcı' }}
                </div>
            </div>

            <button type="submit" class="bg-primary hover:bg-primary/90 text-on-primary py-2.5 rounded-full text-sm font-bold transition-colors mt-2 shadow-sm cursor-pointer">
                Değişiklikleri Kaydet
            </button>
        </form>

    </div>
</div>
@endsection
