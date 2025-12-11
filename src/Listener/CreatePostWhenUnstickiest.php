<?php

namespace HuseyinFiliz\Stickiest\Listener;

use HuseyinFiliz\Stickiest\Event\DiscussionWasUnstickiest;
use HuseyinFiliz\Stickiest\Post\DiscussionStickiestPost;

class CreatePostWhenUnstickiest
{
    public function handle(DiscussionWasUnstickiest $event): void
    {
        $post = DiscussionStickiestPost::reply(
            $event->discussion->id,
            $event->actor->id,
            false
        );

        $event->discussion->mergePost($post);
    }
}