<?php

namespace HuseyinFiliz\Stickiest\Event;

use Flarum\Discussion\Discussion;
use Flarum\User\User;

class DiscussionWasStickiest
{
    public function __construct(
        public Discussion $discussion,
        public User $actor
    ) {}
}