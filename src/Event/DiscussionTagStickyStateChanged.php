<?php

namespace HuseyinFiliz\Stickiest\Event;

use Flarum\Discussion\Discussion;
use Flarum\User\User;

class DiscussionTagStickyStateChanged
{
    /**
     * @param list<int> $oldTagIds
     * @param list<int> $newTagIds
     */
    public function __construct(
        public Discussion $discussion,
        public User $actor,
        public bool $wasTagSticky,
        public bool $isTagSticky,
        public array $oldTagIds,
        public array $newTagIds
    ) {
    }
}
