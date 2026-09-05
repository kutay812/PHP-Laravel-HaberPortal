@extends('layouts.app')

@section('title', 'Yorumu Düzenle')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="bg-surface dark:bg-surface-container rounded-2xl shadow-sm border border-outline-variant p-6 md:p-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold font-display text-on-surface">Yorumu Düzenle</h1>
            @if($comment->news)
                <a href="{{ route('news.show', $comment->news->slug) }}" class="text-sm font-medium text-primary hover:underline flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">arrow_back</span> Habere Dön
                </a>
            @endif
        </div>

        @if($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('comments.update', $comment) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="content" class="block text-sm font-semibold text-on-surface mb-2">Yorumunuz</label>
                <textarea id="content" name="content" rows="5" required maxlength="1000"
                    class="w-full rounded-xl border border-outline-variant bg-surface-container-low dark:bg-surface-container p-4 text-on-surface placeholder:text-outline focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors resize-y">{{ old('content', $comment->content) }}</textarea>
                <div class="flex justify-between text-xs text-on-surface-variant mt-1.5">
                    <span>Maksimum 1000 karakter</span>
                    <span id="char-count">{{ strlen($comment->content) }}/1000</span>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                @if($comment->news)
                    <a href="{{ route('news.show', $comment->news->slug) }}" class="px-5 py-2.5 rounded-full text-sm font-semibold border border-outline-variant text-on-surface hover:bg-surface-container transition-colors">
                        İptal
                    </a>
                @endif
                <button type="submit" class="px-6 py-2.5 rounded-full text-sm font-bold bg-primary text-on-primary hover:bg-primary/90 transition-colors shadow-sm">
                    Değişiklikleri Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const textarea = document.getElementById('content');
    const charCount = document.getElementById('char-count');
    if (textarea && charCount) {
        textarea.addEventListener('input', () => {
            charCount.textContent = `${textarea.value.length}/1000`;
        });
    }
</script>
@endpush
@endsection
