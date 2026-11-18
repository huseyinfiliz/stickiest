<?php

namespace HuseyinFiliz\Stickiest;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;
use Flarum\Foundation\ValidationException;
use Flarum\Locale\TranslatorInterface;
use HuseyinFiliz\Stickiest\Event\DiscussionWasStickiest;
use HuseyinFiliz\Stickiest\Event\DiscussionWasUnstickiest;

class DiscussionAttributes
{
    public function __construct(protected TranslatorInterface $translator)
    {
    }

    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('isStickiest')
                ->get(fn (Discussion $discussion) => (bool) $discussion->getAttribute('is_stickiest'))
                ->writable(fn (Discussion $discussion, Context $context) => $context->getActor()->can('stickiest', $discussion))
                ->set(function (Discussion $discussion, bool $value, Context $context) {
                    $wasStickiest = (bool) $discussion->getAttribute('is_stickiest');
                    $discussion->setAttribute('is_stickiest', $value);

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
                ->get(fn (Discussion $discussion) => (bool) $discussion->getAttribute('is_tag_sticky'))
                ->writable(fn (Discussion $discussion, Context $context) => $context->getActor()->can('tagSticky', $discussion))
                ->set(function (Discussion $discussion, bool $value) {
                    $discussion->setAttribute('is_tag_sticky', $value);
                }),
            Schema\Arr::make('stickyTagIds')
                ->get(fn (Discussion $discussion) => $discussion->stickyTags->pluck('id')->toArray())
                ->writable(fn (Discussion $discussion, Context $context) => $context->getActor()->can('tagSticky', $discussion))
                ->set(function (Discussion $discussion, ?array $value, Context $context) {
                    // Validate the original input before Schema\Arr can coerce scalars to arrays.
                    $tagIds = $this->validateTagIds($discussion, $context->body()['data']['attributes']['stickyTagIds']);

                    $discussion->afterSave(function (Discussion $discussion) use ($tagIds) {
                        $discussion->stickyTags()->sync($tagIds);
                        $discussion->unsetRelation('stickyTags');
                    });
                }),
            Schema\Boolean::make('canStickiest')
                ->get(fn (Discussion $discussion, Context $context) => $context->getActor()->can('stickiest', $discussion)),
            Schema\Boolean::make('canTagSticky')
                ->get(fn (Discussion $discussion, Context $context) => $context->getActor()->can('tagSticky', $discussion)),
        ];
    }

    /** @return list<int> */
    private function validateTagIds(Discussion $discussion, mixed $value): array
    {
        if (! is_array($value) || ! array_is_list($value)
            || count(array_filter($value, fn ($id) => is_int($id) && $id > 0)) !== count($value)
            || count(array_unique($value, SORT_REGULAR)) !== count($value)) {
            throw new ValidationException(['stickyTagIds' => $this->translator->trans('huseyinfiliz-stickiest.validation.invalid_tag_ids')]);
        }

        $discussionTagIds = $discussion->getRelationValue('tags')->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (array_diff($value, $discussionTagIds)) {
            throw new ValidationException(['stickyTagIds' => $this->translator->trans('huseyinfiliz-stickiest.validation.unrelated_tags')]);
        }

        return $value;
    }
}
