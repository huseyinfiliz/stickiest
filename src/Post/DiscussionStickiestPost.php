<?php

namespace HuseyinFiliz\Stickiest\Post;

use Flarum\Post\AbstractEventPost;
use Flarum\Post\MergeableInterface;
use Flarum\Post\Post;

class DiscussionStickiestPost extends AbstractEventPost implements MergeableInterface
{
    public static string $type = 'discussionStickiest';

    public function saveAfter(Post $previous = null): static
    {
        // Önceki post aynı tipte ve aynı kullanıcıdansa birleştir
        if ($previous instanceof static && $this->user_id === $previous->user_id) {
            if ($previous->content['stickiest'] !== $this->content['stickiest']) {
                $previous->delete();
            } else {
                $previous->content = $this->content;
                $previous->save();
            }

            return $previous;
        }

        $this->save();

        return $this;
    }

    public static function reply(int $discussionId, int $userId, bool $isStickiest): static
    {
        $post = new static();

        $post->content = ['stickiest' => $isStickiest];
        $post->created_at = now();
        $post->discussion_id = $discussionId;
        $post->user_id = $userId;

        return $post;
    }
}