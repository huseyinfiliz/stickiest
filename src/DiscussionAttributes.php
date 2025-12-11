<?php

namespace HuseyinFiliz\Stickiest;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;
use HuseyinFiliz\Stickiest\Event\DiscussionWasStickiest;
use HuseyinFiliz\Stickiest\Event\DiscussionWasUnstickiest;

class DiscussionAttributes
{
    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('isStickiest')
                ->get(fn (Discussion $discussion) => (bool) $discussion->is_stickiest)
                ->writable(fn (Discussion $discussion, Context $context) => $context->getActor()->can('stickiest', $discussion))
                ->set(function (Discussion $discussion, bool $value, Context $context) {
                    $wasStickiest = (bool) $discussion->is_stickiest;
                    $discussion->is_stickiest = $value;
                    
                    // Event tetikle (değiştiyse)
                    if ($value !== $wasStickiest) {
                        $actor = $context->getActor();
                        $discussion->afterSave(function ($discussion) use ($actor, $value) {
                            if ($value) {
                                $discussion->raise(new DiscussionWasStickiest($discussion, $actor));
                            } else {
                                $discussion->raise(new DiscussionWasUnstickiest($discussion, $actor));
                            }
                        });
                    }
                }),
            Schema\Boolean::make('isTagSticky')
                ->get(fn (Discussion $discussion) => (bool) $discussion->is_tag_sticky)
                ->writable(fn (Discussion $discussion, Context $context) => $context->getActor()->can('tagSticky', $discussion))
                ->set(function (Discussion $discussion, bool $value) {
                    $discussion->is_tag_sticky = $value;
                }),
            Schema\Arr::make('stickyTagIds')
                ->get(fn (Discussion $discussion) => $discussion->stickyTags->pluck('id')->toArray())
                ->writable(fn (Discussion $discussion, Context $context) => $context->getActor()->can('tagSticky', $discussion))
                ->set(function (Discussion $discussion, ?array $value, Context $context) {
                    // Kayıt sonrası pivot tabloyu güncelle
                    $discussion::saved(function ($discussion) use ($value) {
                        if (!empty($value)) {
                            $discussion->stickyTags()->sync($value);
                        } else {
                            $discussion->stickyTags()->detach();
                        }
                    });
                }),
            Schema\Boolean::make('canStickiest')
                ->get(fn (Discussion $discussion, Context $context) => $context->getActor()->can('stickiest', $discussion)),
            Schema\Boolean::make('canTagSticky')
                ->get(fn (Discussion $discussion, Context $context) => $context->getActor()->can('tagSticky', $discussion)),
        ];
    }
}