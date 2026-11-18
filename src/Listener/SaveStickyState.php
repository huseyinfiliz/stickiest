<?php

namespace HuseyinFiliz\Stickiest\Listener;

use Flarum\Discussion\Event\Saving;
use Flarum\Discussion\Discussion;
use HuseyinFiliz\Stickiest\Event\DiscussionTagStickyStateChanged;
use Illuminate\Support\Arr;

class SaveStickyState
{
    public function handle(Saving $event): void
    {
        $discussion = $event->discussion;
        $actor = $event->actor;
        $data = $event->data;

        if (Arr::has($data, 'attributes.isStickiest')) {
            $actor->assertCan('stickiest', $discussion);
        }

        if (Arr::has($data, 'attributes.isTagSticky')) {
            $actor->assertCan('tagSticky', $discussion);
        }

        if (Arr::has($data, 'attributes.isTagSticky') || Arr::has($data, 'attributes.stickyTagIds')) {
            $actor->assertCan('tagSticky', $discussion);
            $wasTagSticky = (bool) $discussion->getRawOriginal('is_tag_sticky');
            $oldTagIds = $discussion->stickyTags()->orderBy('tags.id')->pluck('tags.id')->map(fn ($id) => (int) $id)->all();

            // Runs after the field's pivot callback, so the event contains persisted state.
            $discussion->afterSave(function (Discussion $discussion) use ($actor, $wasTagSticky, $oldTagIds) {
                $isTagSticky = (bool) $discussion->getAttribute('is_tag_sticky');
                $newTagIds = $discussion->stickyTags()->orderBy('tags.id')->pluck('tags.id')->map(fn ($id) => (int) $id)->all();

                if ($wasTagSticky !== $isTagSticky || $oldTagIds !== $newTagIds) {
                    $discussion->raise(new DiscussionTagStickyStateChanged(
                        $discussion, $actor, $wasTagSticky, $isTagSticky, $oldTagIds, $newTagIds
                    ));
                }
            });
        }
    }
}
