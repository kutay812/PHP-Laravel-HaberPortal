<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Jobs\FetchNewsJob;
use App\Models\Category;
use App\Models\Comment;
use App\Models\News;
use App\Models\NewsSource;
use App\Models\Tag;
use App\Models\User;
use App\Services\QueueTriggerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'news_count'     => News::count(),
            'category_count' => Category::count(),
            'user_count'     => User::count(),
            'source_count'   => NewsSource::count(),
            'tag_count'      => Tag::count(),
            'comment_count'  => Comment::count(),
            'total_views'    => (int) News::sum('views'),
        ];

        $latestNews = News::with(['category', 'newsSource'])->latest()->limit(5)->get();
        $sources = NewsSource::orderByDesc('last_fetched_at')->limit(5)->get();

        return view('admin.dashboard', compact('stats', 'latestNews', 'sources'));
    }

    public function clearCache(): RedirectResponse
    {
        abort_unless(auth()->user()?->hasAnyRole(RoleName::adminRoles()) ?? false, 403);

        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');

        return redirect()->back()->with('success', 'Önbellek temizleme işlemi başarıyla tamamlandı.');
    }

    /**
     * Tüm kaynaklardan haber çekmeyi kuyruk üzerinden asenkron tetikle.
     */
    public function fetchNews(QueueTriggerService $queueTrigger): RedirectResponse
    {
        try {
            FetchNewsJob::dispatch(null, true);
            $queueTrigger->triggerWorkerIfNeeded();

            Log::info('Admin tarafından manuel tüm haber çekme kuyruğa eklendi.', [
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()->with(
                'success',
                'Haber çekme ve özgünleştirme görevi arka planda başlatıldı. Yeni haberler kısa süre içinde listeye yansıyacaktır.'
            );
        } catch (\Throwable $e) {
            Log::error('Manuel haber çekme kuyruk hatası: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Haber botu kuyruğa eklenirken bir hata oluştu.');
        }
    }
}
