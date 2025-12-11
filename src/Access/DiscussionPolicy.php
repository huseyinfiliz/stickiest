<?php

namespace HuseyinFiliz\Stickiest\Access;

use Flarum\Discussion\Discussion;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class DiscussionPolicy extends AbstractPolicy
{
    public function stickiest(User $actor, Discussion $discussion): ?string
    {
        if ($actor->hasPermission('discussion.stickiest')) {
            return $this->allow();
        }

        return null;
    }

    public function tagSticky(User $actor, Discussion $discussion): ?string
    {
        if ($actor->hasPermission('discussion.tagSticky')) {
            return $this->allow();
        }

        return null;
    }
}
