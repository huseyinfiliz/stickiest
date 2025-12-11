<?php

namespace HuseyinFiliz\Stickiest\Listener;

use Flarum\Discussion\Event\Saving;
use Illuminate\Support\Arr;

class SaveStickyState
{
    public function handle(Saving $event): void
    {
        $discussion = $event->discussion;
        $actor = $event->actor;
        $data = $event->data;

        // Super Sticky izin kontrolü
        if (Arr::has($data, 'attributes.isStickiest')) {
            $actor->assertCan('stickiest', $discussion);
        }

        // Tag Sticky izin kontrolü
        if (Arr::has($data, 'attributes.isTagSticky')) {
            $actor->assertCan('tagSticky', $discussion);
        }
    }
}