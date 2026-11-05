<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RoleName;
use App\Models\Comment;
use App\Models\News;
use Illuminate\Support\Facades\Auth;

class CommentService
{
    public function storeComment(News $news, string $content): Comment
    {
        return Comment::create([
            'news_id' => $news->id,
            'user_id' => Auth::id(),
            'content' => $content,
        ]);
    }

    public function updateComment(Comment $comment, string $content): Comment
    {
        if (!$this->userCanModify($comment)) {
            abort(403, 'Bu yorumu düzenlemeye yetkiniz yok.');
        }

        $comment->update(['content' => $content]);

        return $comment;
    }

    public function deleteComment(Comment $comment): void
    {
        if (!$this->userCanModify($comment)) {
            abort(403, 'Bu yorumu silmeye yetkiniz yok.');
        }

        $comment->delete();
    }

    public function userCanModify(Comment $comment): bool
    {
        $user = Auth::user();
        if ($user === null) {
            return false;
        }

        if ($user->hasAnyRole(RoleName::staffRoles())) {
            return true;
        }

        return $user->id === $comment->user_id;
    }
}
