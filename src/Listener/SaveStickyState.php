<?php

namespace HuseyinFiliz\Stickiest\Listener;

use Flarum\Discussion\Event\Saving;
use HuseyinFiliz\Stickiest\Event\DiscussionWasStickiest;
use HuseyinFiliz\Stickiest\Event\DiscussionWasUnstickiest;
use Illuminate\Support\Arr;

class SaveStickyState
{
    public function handle(Saving $event): void
    {
        $discussion = $event->discussion;
        $actor = $event->actor;
        $data = $event->data;

        // Super Sticky
        if (Arr::has($data, 'attributes.isStickiest')) {
            $actor->assertCan('stickiest', $discussion);

            $isStickiest = (bool) Arr::get($data, 'attributes.isStickiest');
            $wasStickiest = (bool) $discussion->is_stickiest;

            if ($isStickiest !== $wasStickiest) {
                $discussion->is_stickiest = $isStickiest;

                $discussion->afterSave(function ($discussion) use ($actor, $isStickiest) {
                    if ($isStickiest) {
                        $discussion->raise(new DiscussionWasStickiest($discussion, $actor));
                    } else {
                        $discussion->raise(new DiscussionWasUnstickiest($discussion, $actor));
                    }
                });
            }
        }

        // Tag Sticky
        if (Arr::has($data, 'attributes.isTagSticky')) {
            $actor->assertCan('tagSticky', $discussion);
        }
    }
}