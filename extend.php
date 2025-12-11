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
use HuseyinFiliz\Stickiest\Event\DiscussionWasStickiest;
use HuseyinFiliz\Stickiest\Event\DiscussionWasUnstickiest;
use HuseyinFiliz\Stickiest\Listener\CreatePostWhenStickiest;
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

    // Post type kaydet
    (new Extend\Post())
        ->type(DiscussionStickiestPost::class),

    // Discussion model'e ilişki ekle
    (new Extend\Model(Discussion::class))
        ->belongsToMany('stickyTags', Tag::class, 'discussion_sticky_tag'),

    // API'ye attribute'lar ekle
    (new Extend\ApiResource(DiscussionResource::class))
        ->fields(DiscussionAttributes::class),

    // İzinler
    (new Extend\Policy())
        ->modelPolicy(Discussion::class, DiscussionPolicy::class),

    // Event listeners
    (new Extend\Event())
        ->listen(\Flarum\Discussion\Event\Saving::class, SaveStickyState::class)
        ->listen(DiscussionWasStickiest::class, [CreatePostWhenStickiest::class, 'whenStickiest'])
        ->listen(DiscussionWasUnstickiest::class, [CreatePostWhenStickiest::class, 'whenUnstickiest']),

    // Sıralama - super sticky en üste
    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->addMutator(DiscussionSearcher::class, StickySearchMutator::class),

    // Ayarlar
    (new Extend\Settings())
        ->default('huseyinfiliz-stickiest.show_tag_sticky_in_all', false)
        ->default('huseyinfiliz-stickiest.stickiest_icon', 'fas fa-star')
        ->serializeToForum('huseyinfiliz-stickiest.stickiest_icon', 'huseyinfiliz-stickiest.stickiest_icon')
        ->serializeToForum('huseyinfiliz-stickiest.show_tag_sticky_in_all', 'huseyinfiliz-stickiest.show_tag_sticky_in_all', function ($value) {
            return (bool) $value;
        }),
];