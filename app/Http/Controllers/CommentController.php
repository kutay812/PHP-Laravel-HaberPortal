<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SaveCommentRequest;
use App\Models\Comment;
use App\Models\News;
use App\Services\CommentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function __construct(
        protected readonly CommentService $commentService,
    ) {}

    public function store(SaveCommentRequest $request, News $news): RedirectResponse
    {
        $this->commentService->storeComment($news, (string) $request->validated('content'));

        return back()->with('success', 'Yorumunuz eklendi.');
    }

    public function edit(Comment $comment): View
    {
        if (!$this->commentService->userCanModify($comment)) {
            abort(403);
        }

        return view('comments.edit', compact('comment'));
    }

    public function update(SaveCommentRequest $request, Comment $comment): RedirectResponse
    {
        $this->commentService->updateComment($comment, (string) $request->validated('content'));

        if ($comment->news) {
            return redirect()->route('news.show', $comment->news->slug)->with('success', 'Yorum güncellendi.');
        }

        return redirect()->back()->with('success', 'Yorum güncellendi.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $this->commentService->deleteComment($comment);

        return back()->with('success', 'Yorum silindi.');
    }
}
