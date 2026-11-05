<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Http\Requests\SaveCommentRequest;
use App\Models\Comment;
use App\Models\News;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(SaveCommentRequest $request, News $news): RedirectResponse
    {
        Comment::create([
            'news_id' => $news->id,
            'user_id' => Auth::id(),
            'content' => (string) $request->validated('content'),
        ]);

        return back()->with('success', 'Yorumunuz eklendi.');
    }

    public function edit(Comment $comment): View
    {
        abort_unless($this->canModify($comment), 403);

        return view('comments.edit', compact('comment'));
    }

    public function update(SaveCommentRequest $request, Comment $comment): RedirectResponse
    {
        abort_unless($this->canModify($comment), 403, 'Bu yorumu düzenlemeye yetkiniz yok.');

        $comment->update(['content' => (string) $request->validated('content')]);

        if ($comment->news) {
            return redirect()->route('news.show', $comment->news->slug)->with('success', 'Yorum güncellendi.');
        }

        return redirect()->back()->with('success', 'Yorum güncellendi.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        abort_unless($this->canModify($comment), 403, 'Bu yorumu silmeye yetkiniz yok.');

        $comment->delete();

        return back()->with('success', 'Yorum silindi.');
    }

    private function canModify(Comment $comment): bool
    {
        $user = Auth::user();

        return $user !== null && ($user->hasAnyRole(RoleName::staffRoles()) || $user->id === $comment->user_id);
    }
}
