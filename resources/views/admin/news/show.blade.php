@extends('layouts.admin.app')

@section('title', $news->title)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h3 mb-0 text-gray-800">{{ $news->title }}</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('news.show', $news->slug) }}" target="_blank" rel="noopener" class="btn btn-outline-primary mr-2">
                <i class="fas fa-external-link-alt mr-1"></i> Sitede Görüntüle
            </a>
            <a href="{{ route('admin.news.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Geri Dön
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    @if($news->image_url)
                        <div class="mb-4">
                            <img src="{{ $news->image_url }}" alt="{{ $news->title }}" class="img-fluid rounded border" style="max-height: 400px; object-fit: cover; width: 100%;">
                        </div>
                    @endif

                    <div class="mb-4">
                        <h5 class="font-weight-bold">Özet</h5>
                        <p class="text-muted">{{ $news->description }}</p>
                    </div>

                    <div class="mb-4">
                        <h5 class="font-weight-bold">Haber Metni</h5>
                        <div class="border-top pt-3">{!! $news->content !!}</div>
                    </div>

                    @if($news->tags && $news->tags->isNotEmpty())
                        <div class="mb-3">
                            <strong>Etiketler:</strong>
                            @foreach($news->tags as $tag)
                                <span class="badge bg-secondary text-white mr-1">{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="font-weight-bold mb-3">Detaylar</h5>

                            <div class="mb-3">
                                <strong>Kategori:</strong>
                                <span class="badge bg-primary text-white">{{ $news->category?->name ?? 'Kategorisiz' }}</span>
                            </div>

                            <div class="mb-3">
                                <strong>Yazar / Kaynak:</strong>
                                <span>{{ $news->user?->name ?? ($news->newsSource?->name ?? 'Otonom Bot') }}</span>
                            </div>

                            @if($news->source_url)
                                <div class="mb-3">
                                    <strong>Kaynak Bağlantısı:</strong>
                                    <a href="{{ $news->source_url }}" target="_blank" rel="noopener noreferrer" class="small d-block text-truncate">{{ $news->source_url }}</a>
                                </div>
                            @endif

                            <div class="mb-3">
                                <strong>Durum:</strong>
                                @if($news->is_active)
                                    <span class="badge bg-success text-white">Yayında</span>
                                @else
                                    <span class="badge bg-danger text-white">Pasif / Taslak</span>
                                @endif
                            </div>

                            <div class="mb-3">
                                <strong>Görüntülenme:</strong>
                                <span>{{ number_format((int) $news->views) }}</span>
                            </div>

                            <div class="mb-3">
                                <strong>Okuma Süresi:</strong>
                                <span>{{ $news->reading_time ?? 1 }} dk</span>
                            </div>

                            <div class="mb-3">
                                <strong>Oluşturulma:</strong>
                                <span>{{ $news->created_at?->format('d.m.Y H:i') }}</span>
                            </div>

                            <div class="mb-0">
                                <strong>Güncelleme:</strong>
                                <span>{{ $news->updated_at?->format('d.m.Y H:i') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        @can('news.edit')
                            <a href="{{ route('admin.news.edit', $news) }}" class="btn btn-primary btn-block mb-2">
                                <i class="fas fa-edit mr-1"></i> Düzenle
                            </a>
                        @endcan
                        @can('news.delete')
                            <form action="{{ route('admin.news.destroy', $news) }}" method="POST" onsubmit="return confirm('Bu haberi silmek istediğinizden emin misiniz?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-block">
                                    <i class="fas fa-trash mr-1"></i> Haberi Sil
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
