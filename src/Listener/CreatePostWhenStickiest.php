<?php

namespace HuseyinFiliz\Stickiest\Listener;

use HuseyinFiliz\Stickiest\Event\DiscussionWasStickiest;
use HuseyinFiliz\Stickiest\Event\DiscussionWasUnstickiest;
use HuseyinFiliz\Stickiest\Post\DiscussionStickiestPost;

class CreatePostWhenStickiest
{
    public function whenStickiest(DiscussionWasStickiest $event): void
    {
        $post = DiscussionStickiestPost::reply(
            $event->discussion->id,
            $event->actor->id,
            true
        );

        $event->discussion->mergePost($post);
    }

    public function whenUnstickiest(DiscussionWasUnstickiest $event): void
    {
        $post = DiscussionStickiestPost::reply(
            $event->discussion->id,
            $event->actor->id,
            false
        );

        $event->discussion->mergePost($post);
    }
}