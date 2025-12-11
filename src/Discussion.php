<?php

namespace HuseyinFiliz\Stickiest;

use Flarum\Discussion\Discussion as FlarumDiscussion;
use Flarum\Tags\Tag;

class Discussion
{
    public static function stickyTags(FlarumDiscussion $discussion)
    {
        return $discussion->belongsToMany(Tag::class, 'discussion_sticky_tag');
    }
}
