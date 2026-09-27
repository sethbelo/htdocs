<?php

namespace App\Services\Collaboration;

use App\Models\Comment;
use App\Models\User;
use App\Notifications\MentionedInComment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

/**
 * Commentaires internes avec mentions « @nom.utilisateur » notifiées.
 */
class CommentService
{
    public function post(Model $commentable, User $author, string $body, ?int $parentId = null): Comment
    {
        preg_match_all('/@([A-Za-z0-9._-]+)/', $body, $matches);
        $mentioned = User::query()->whereIn('username', array_unique($matches[1]))->where('id', '!=', $author->id)->get();

        $comment = $commentable->comments()->create([
            'user_id' => $author->id,
            'parent_id' => $parentId,
            'body' => $body,
            'mentions' => $mentioned->pluck('id')->all(),
        ]);

        Notification::send($mentioned, new MentionedInComment($comment, $author));

        return $comment;
    }
}
