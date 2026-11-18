<?php

namespace HuseyinFiliz\Stickiest\Listener;

use HuseyinFiliz\Stickiest\Event\DiscussionWasStickiest;
use HuseyinFiliz\Stickiest\Post\DiscussionStickiestPost;

class CreatePostWhenStickiest
{
    public function handle(DiscussionWasStickiest $event): void
    {
        $post = DiscussionStickiestPost::reply(
            $event->discussion->id,
            $event->actor->id,
            true
        );

        $event->discussion->mergePost($post);
    }
}
