<?php

/*
 * This file is part of huseyinfiliz/stickiest.
 *
 * Copyright (c) 2025 Hüseyin Filiz.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace HuseyinFiliz\Stickiest;

use Flarum\Api\Resource\DiscussionResource;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\Tags\Tag;
use HuseyinFiliz\Stickiest\Access\DiscussionPolicy;
use HuseyinFiliz\Stickiest\Event\DiscussionTagStickyStateChanged;
use HuseyinFiliz\Stickiest\Event\DiscussionWasStickiest;
use HuseyinFiliz\Stickiest\Event\DiscussionWasUnstickiest;
use HuseyinFiliz\Stickiest\Listener\CreatePostWhenStickiest;
use HuseyinFiliz\Stickiest\Listener\CreatePostWhenUnstickiest;
use HuseyinFiliz\Stickiest\Listener\SaveStickyState;
use HuseyinFiliz\Stickiest\Post\DiscussionStickiestPost;
use HuseyinFiliz\Stickiest\Search\StickySearchMutator;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    // Register the super sticky event post.
    (new Extend\Post())
        ->type(DiscussionStickiestPost::class),

    // Store tag-specific sticky selections on the discussion.
    (new Extend\Model(Discussion::class))
        ->belongsToMany('stickyTags', Tag::class, 'discussion_sticky_tag'),

    // Expose sticky state and permissions through the discussion resource.
    (new Extend\ApiResource(DiscussionResource::class))
        ->fields(DiscussionAttributes::class)
        ->endpoint('index', function (\Flarum\Api\Endpoint\Index $endpoint) {
            return $endpoint->eagerLoad('stickyTags');
        }),

    // Permissions
    (new Extend\Policy())
        ->modelPolicy(Discussion::class, DiscussionPolicy::class),

    // Event listeners
    (new Extend\Event())
        ->listen(\Flarum\Discussion\Event\Saving::class, SaveStickyState::class)
        ->listen(DiscussionWasStickiest::class, CreatePostWhenStickiest::class)
        ->listen(DiscussionWasUnstickiest::class, CreatePostWhenUnstickiest::class),

    // Keep super stickies above tag and regular stickies.
    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->addMutator(DiscussionSearcher::class, StickySearchMutator::class),

    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-audit', fn () => [
            (new \Flarum\Audit\Extend\Audit())
                ->group('huseyinfiliz-stickiest')
                ->listen(DiscussionWasStickiest::class, 'discussion.super_stickied', fn ($event) => ['discussion_id' => $event->discussion->id])
                ->listen(DiscussionWasUnstickiest::class, 'discussion.super_unstickied', fn ($event) => ['discussion_id' => $event->discussion->id])
                ->listen(DiscussionTagStickyStateChanged::class, 'discussion.tag_stickied', fn ($event) => ! $event->wasTagSticky && $event->isTagSticky ? [
                    'discussion_id' => $event->discussion->id,
                    'old_tag_ids' => $event->oldTagIds,
                    'new_tag_ids' => $event->newTagIds,
                ] : null)
                ->listen(DiscussionTagStickyStateChanged::class, 'discussion.tag_unstickied', fn ($event) => $event->wasTagSticky && ! $event->isTagSticky ? [
                    'discussion_id' => $event->discussion->id,
                    'old_tag_ids' => $event->oldTagIds,
                    'new_tag_ids' => $event->newTagIds,
                ] : null)
                ->listen(DiscussionTagStickyStateChanged::class, 'discussion.tag_sticky_tags_changed', fn ($event) => $event->wasTagSticky === $event->isTagSticky ? [
                    'discussion_id' => $event->discussion->id,
                    'old_tag_ids' => $event->oldTagIds,
                    'new_tag_ids' => $event->newTagIds,
                ] : null),
        ]),

    // Settings
    (new Extend\Settings())
        ->default('huseyinfiliz-stickiest.show_tag_sticky_in_all', false)
        ->default('huseyinfiliz-stickiest.stickiest_icon', 'fas fa-star')
        ->default('huseyinfiliz-stickiest.show_sticky_highlight', true)
        ->serializeToForum('huseyinfiliz-stickiest.stickiest_icon', 'huseyinfiliz-stickiest.stickiest_icon')
        ->serializeToForum('huseyinfiliz-stickiest.show_tag_sticky_in_all', 'huseyinfiliz-stickiest.show_tag_sticky_in_all', function ($value) {
            return (bool) $value;
        })
        ->serializeToForum('huseyinfiliz-stickiest.show_sticky_highlight', 'huseyinfiliz-stickiest.show_sticky_highlight', function ($value) {
            return (bool) $value;
        }),
];
