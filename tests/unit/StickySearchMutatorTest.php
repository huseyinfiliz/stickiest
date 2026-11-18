<?php

namespace HuseyinFiliz\Stickiest\Tests\unit;

use Flarum\Api\Resource\DiscussionResource;
use Flarum\Extension\ExtensionManager;
use Flarum\Http\SlugDriverInterface;
use Flarum\Http\SlugManager;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\SearchCriteria;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Search\Filter\TagFilter;
use Flarum\Tags\Tag;
use Flarum\User\User;
use HuseyinFiliz\Stickiest\Search\StickySearchMutator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\SQLiteConnection;
use PHPUnit\Framework\TestCase;

class StickySearchMutatorTest extends TestCase
{
    private function mutator(array $settings = [], bool $coreSticky = false): StickySearchMutator
    {
        $repository = $this->createStub(SettingsRepositoryInterface::class);
        $repository->method('get')->willReturnCallback(fn ($key, $default = null) => $settings[$key] ?? $default);
        $tag = new Tag;
        $tag->id = 5;
        $driver = $this->createStub(SlugDriverInterface::class);
        $driver->method('fromSlug')->willReturn($tag);
        $slugs = $this->createStub(SlugManager::class);
        $slugs->method('forResource')->willReturn($driver);
        $extensions = $this->createStub(ExtensionManager::class);
        $extensions->method('isEnabled')->willReturn($coreSticky);
        $resource = new class extends DiscussionResource {
            public function __construct()
            {
            }
        };

        return new StickySearchMutator($repository, $slugs, $resource, $extensions);
    }

    private function state(bool $tag = false, bool $fulltext = false): DatabaseSearchState
    {
        $query = new QueryBuilder(new SQLiteConnection(new \PDO('sqlite::memory:')));
        $query->from('discussions')->orderByDesc('last_posted_at');
        $state = new DatabaseSearchState(new User, $fulltext);
        $state->setQuery(new Builder($query));

        if ($tag) {
            $state->addActiveFilter($this->createStub(TagFilter::class));
        }

        return $state;
    }

    private function criteria(array $filters = [], bool $default = true): SearchCriteria
    {
        return new SearchCriteria(new User, $filters, sortIsDefault: $default);
    }

    public function test_it_skips_non_default_sort(): void
    {
        $state = $this->state();
        $before = $state->getQuery()->toSql();
        ($this->mutator())($state, $this->criteria([], false));
        $this->assertSame($before, $state->getQuery()->toSql());
    }

    public function test_it_skips_fulltext_search(): void
    {
        $state = $this->state(fulltext: true);
        $before = $state->getQuery()->toSql();
        ($this->mutator())($state, $this->criteria());
        $this->assertSame($before, $state->getQuery()->toSql());
    }

    public function test_it_detects_tag_pages_with_scalar_or_array_filters(): void
    {
        foreach (['general', ['general']] as $slug) {
            $state = $this->state(tag: true);
            ($this->mutator())($state, $this->criteria(['tag' => $slug]));
            $this->assertStringContainsString('left join "discussion_sticky_tag"', $state->getQuery()->toSql());
            $this->assertSame([5], $state->getQuery()->getBindings());
            $this->assertEmpty($state->getQuery()->getQuery()->wheres);
        }
    }

    public function test_it_preserves_the_sticky_hierarchy_and_existing_sort(): void
    {
        $state = $this->state(tag: true);
        ($this->mutator(coreSticky: true))($state, $this->criteria(['tag' => 'general']));
        $this->assertStringContainsString(
            'order by "is_stickiest" desc, CASE WHEN discussion_sticky_tag.tag_id IS NOT NULL THEN 1 ELSE 0 END DESC, "is_sticky" desc, "last_posted_at" desc',
            $state->getQuery()->toSql()
        );
    }

    public function test_multiple_tag_filters_do_not_pin_the_first_tag_arbitrarily(): void
    {
        foreach ([['general', 'other'], 'general,other'] as $slugs) {
            $state = $this->state(tag: true);
            ($this->mutator())($state, $this->criteria(['tag' => $slugs]));
            $this->assertStringNotContainsString('discussion_sticky_tag', $state->getQuery()->toSql());
        }
    }

    public function test_it_prepends_super_stickies_on_all_discussions(): void
    {
        $state = $this->state();
        ($this->mutator())($state, $this->criteria());
        $this->assertStringContainsString('order by "is_stickiest" desc, "last_posted_at" desc', $state->getQuery()->toSql());
    }

    public function test_it_hides_tag_stickies_when_the_setting_is_disabled(): void
    {
        $state = $this->state();
        ($this->mutator())($state, $this->criteria());
        $this->assertStringContainsString('("is_tag_sticky" = ? or "is_stickiest" = ?)', $state->getQuery()->toSql());
        $this->assertSame([false, true], $state->getQuery()->getBindings());
    }

    public function test_it_keeps_tag_stickies_when_the_setting_is_enabled(): void
    {
        $state = $this->state();
        ($this->mutator(['huseyinfiliz-stickiest.show_tag_sticky_in_all' => true]))($state, $this->criteria());
        $this->assertEmpty($state->getQuery()->getQuery()->wheres);
    }
}
