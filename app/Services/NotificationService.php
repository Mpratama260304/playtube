<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Video;
use App\Models\Comment;
use Illuminate\Support\Str;

class NotificationService
{
    public function notifyNewSubscriber(User $channel, User $subscriber): void
    {
        Notification::notify($channel->id, 'new_subscriber', [
            'subscriber_id' => $subscriber->id,
            'subscriber_name' => $subscriber->name,
            'subscriber_username' => $subscriber->username,
            'subscriber_avatar' => $subscriber->avatar_url,
        ]);
    }

    public function notifyNewComment(Video $video, Comment $comment): void
    {
        // Don't notify if user comments on their own video
        if ($video->user_id === $comment->user_id) {
            return;
        }

        Notification::notify($video->user_id, 'new_comment', [
            'video_id' => $video->id,
            'video_slug' => $video->slug,
            'video_title' => $video->title,
            'commenter_id' => $comment->user_id,
            'commenter_name' => $comment->user->name,
            'commenter_avatar' => $comment->user->avatar_url,
            'comment_preview' => Str::limit($comment->body, 100),
        ]);
    }

    public function notifyCommentReply(Comment $parentComment, Comment $reply): void
    {
        // Don't notify if user replies to their own comment
        if ($parentComment->user_id === $reply->user_id) {
            return;
        }

        Notification::notify($parentComment->user_id, 'new_reply', [
            'video_id' => $parentComment->video_id,
            'video_slug' => $parentComment->video->slug,
            'video_title' => $parentComment->video->title,
            'replier_id' => $reply->user_id,
            'replier_name' => $reply->user->name,
            'replier_avatar' => $reply->user->avatar_url,
            'reply_preview' => Str::limit($reply->body, 100),
        ]);
    }

    public function notifyVideoPublished(Video $video): void
    {
        Notification::notify($video->user_id, 'video_published', [
            'video_id' => $video->id,
            'video_slug' => $video->slug,
            'video_title' => $video->title,
            'video_thumbnail' => $video->thumbnail_url,
        ]);
    }

    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function markAllAsRead(User $user): void
    {
        Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
