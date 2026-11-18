<?php

namespace HuseyinFiliz\Stickiest\Search;

use Flarum\Api\Resource\DiscussionResource;
use Flarum\Extension\ExtensionManager;
use Flarum\Http\SlugManager;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\SearchCriteria;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Search\Filter\TagFilter;
use Flarum\Tags\Tag;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder;

class StickySearchMutator
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected SlugManager $slugManager,
        protected DiscussionResource $discussions,
        protected ?ExtensionManager $extensions = null
    ) {}

    public function __invoke(DatabaseSearchState $state, SearchCriteria $criteria): void
    {
        if ($state->isFulltextSearch()) {
            return;
        }

        // Refine filters preserve the current list; window filters still need activity order.
        // Resolve optional marker interfaces by name to remain compatible with Flarum 2.0.
        $activeFilters = array_values(array_filter(
            $state->getActiveFilters(),
            fn ($filter) => !is_a($filter, 'Flarum\Search\Filter\RefineFilterInterface')
                || is_a($filter, 'Flarum\Search\Filter\WindowFilterInterface')
        ));
        $isTagPage = count($activeFilters) === 1 && $activeFilters[0] instanceof TagFilter;
        $tag = $isTagPage ? $this->resolveTag($state, $criteria) : null;

        foreach ($activeFilters as $filter) {
            if (is_a($filter, 'Flarum\Search\Filter\WindowFilterInterface')) {
                return;
            }
        }

        if (!$criteria->sortIsDefault && !$this->sortIsTagDefault($tag, $criteria)) {
            return;
        }

        $query = $state->getQuery();
        $baseQuery = $query->getQuery();

        if ($isTagPage) {
            if ($tag) {
                $tagId = $tag->id;
                $query->leftJoin('discussion_sticky_tag', function ($join) use ($tagId) {
                    $join->on('discussions.id', '=', 'discussion_sticky_tag.discussion_id')
                         ->where('discussion_sticky_tag.tag_id', '=', $tagId);
                });

                $orders = $baseQuery->orders ?? [];
                $prefix = $baseQuery->getConnection()->getTablePrefix();

                $orderColumns = [
                    ['column' => 'is_stickiest', 'direction' => 'desc'],
                    ['type' => 'Raw', 'sql' => "CASE WHEN {$prefix}discussion_sticky_tag.tag_id IS NOT NULL THEN 1 ELSE 0 END DESC"],
                ];

                if ($this->extensions && $this->extensions->isEnabled('flarum-sticky')) {
                    $orderColumns[] = ['column' => 'is_sticky', 'direction' => 'desc'];
                }

                array_unshift($orders, ...$orderColumns);

                $baseQuery->orders = $orders;
            }
        } else {
            // All Discussions (or multi-filter / no-filter context)
            $showTagStickyInAll = (bool) $this->settings->get('huseyinfiliz-stickiest.show_tag_sticky_in_all', false);

            if (!$showTagStickyInAll) {
                $this->excludeTagStickies($baseQuery);

                foreach ($baseQuery->unions ?? [] as $union) {
                    $this->excludeTagStickies($union['query']);
                }

                if ($baseQuery->unions) {
                    // union() copied its bindings before these additional constraints.
                    $baseQuery->setBindings(array_merge(...array_map(
                        fn ($union) => $union['query']->getBindings(),
                        $baseQuery->unions
                    )), 'union');
                }
            }

            $orders = $baseQuery->orders ?? [];

            array_unshift($orders, [
                'column' => 'is_stickiest',
                'direction' => 'desc'
            ]);

            $baseQuery->orders = $orders;

            // Core Sticky's unread-only mode orders the combined result separately.
            if ($baseQuery->unions) {
                $baseQuery->unionOrders = array_merge([
                    ['column' => 'is_stickiest', 'direction' => 'desc'],
                ], $baseQuery->unionOrders ?? []);
            }
        }
    }

    private function resolveTag(DatabaseSearchState $state, SearchCriteria $criteria): ?Tag
    {
        $slugs = (array) ($criteria->filters['tag'] ?? []);

        if (count($slugs) !== 1 || !is_string($slug = reset($slugs)) || str_contains($slug, ',')) {
            return null;
        }

        try {
            return $this->slugManager->forResource(Tag::class)->fromSlug($slug, $state->getActor());
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    private function sortIsTagDefault(?Tag $tag, SearchCriteria $criteria): bool
    {
        if (!$tag || !$tag->default_sort || !($sort = $this->discussions->sortMap()[$tag->default_sort] ?? null)) {
            return false;
        }

        $fields = [];

        foreach (explode(',', $sort) as $field) {
            $fields[ltrim($field, '-')] = str_starts_with($field, '-') ? 'desc' : 'asc';
        }

        return $fields === $criteria->sort;
    }

    private function excludeTagStickies(Builder $query): void
    {
        $query->where(function (Builder $query) {
            $query->where('is_tag_sticky', false)->orWhere('is_stickiest', true);
        });
    }
}
