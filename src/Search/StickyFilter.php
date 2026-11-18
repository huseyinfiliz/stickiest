<?php

namespace HuseyinFiliz\Stickiest\Search;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class StickyFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'stickiest';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $state->getQuery()->where('is_stickiest', !$negate);
    }
}
