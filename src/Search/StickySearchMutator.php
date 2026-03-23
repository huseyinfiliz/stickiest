<?php

namespace HuseyinFiliz\Stickiest\Search;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\SearchCriteria;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Search\Filter\TagFilter;
use Flarum\Tags\TagRepository;
use Illuminate\Support\Arr;

class StickySearchMutator
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected TagRepository $tags
    ) {}

    public function __invoke(DatabaseSearchState $state, SearchCriteria $criteria): void
    {
        if (!$criteria->sortIsDefault || $state->isFulltextSearch()) {
            return;
        }

        $query = $state->getQuery();
        $baseQuery = $query->getQuery();

        // Use getActiveFilters() + instanceof TagFilter like flarum/sticky 2.x.
        // This is more robust than reading $criteria->filters directly.
        $activeFilters = $state->getActiveFilters();
        $isTagPage = count($activeFilters) === 1 && $activeFilters[0] instanceof TagFilter;

        if ($isTagPage) {
            // Get the tag slug from criteria — handle both string (pre-beta8)
            // and array (beta8+) formats.
            $tagSlug = Arr::get($criteria->filters, 'tag');
            if (is_array($tagSlug)) {
                $tagSlug = Arr::first($tagSlug) ?: null;
            }

            $tagId = $tagSlug ? $this->tags->getIdForSlug((string) $tagSlug) : null;

            if ($tagId) {
                $query->leftJoin('discussion_sticky_tag as dst', function ($join) use ($tagId) {
                    $join->on('discussions.id', '=', 'dst.discussion_id')
                         ->where('dst.tag_id', '=', $tagId);
                });

                $orders = $baseQuery->orders ?? [];

                array_unshift($orders,
                    ['column' => 'is_stickiest', 'direction' => 'desc'],
                    ['column' => 'dst.tag_id', 'direction' => 'desc'],
                    ['column' => 'is_sticky', 'direction' => 'desc']
                );

                $baseQuery->orders = $orders;
            }
        } else {
            // All Discussions (or multi-filter / no-filter context)
            $showTagStickyInAll = (bool) $this->settings->get('huseyinfiliz-stickiest.show_tag_sticky_in_all', false);

            if (!$showTagStickyInAll) {
                $query->where(function ($q) {
                    $q->where('is_tag_sticky', false)
                      ->orWhere('is_stickiest', true);
                });
            }

            $orders = $baseQuery->orders ?? [];

            array_unshift($orders, [
                'column' => 'is_stickiest',
                'direction' => 'desc'
            ]);

            $baseQuery->orders = $orders;
        }
    }
}