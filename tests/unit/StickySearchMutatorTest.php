<?php

namespace HuseyinFiliz\Stickiest\Tests\unit;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\SearchCriteria;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Search\Filter\TagFilter;
use Flarum\Tags\TagRepository;
use HuseyinFiliz\Stickiest\Search\StickySearchMutator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use PHPUnit\Framework\TestCase;

class StickySearchMutatorTest extends TestCase
{
    private function makeSettings(array $values = []): SettingsRepositoryInterface
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturnCallback(function (string $key, $default = null) use ($values) {
            return $values[$key] ?? $default;
        });

        return $settings;
    }

    private function makeTagRepository(?int $tagId = 1): TagRepository
    {
        $repo = $this->createMock(TagRepository::class);
        $repo->method('getIdForSlug')->willReturn($tagId);

        return $repo;
    }

    private function makeCriteria(array $filters = [], bool $sortIsDefault = true): SearchCriteria
    {
        $actor = new \stdClass();
        $criteria = new SearchCriteria($actor, [], $filters);
        $criteria->sortIsDefault = $sortIsDefault;

        return $criteria;
    }

    private function makeState(array $activeFilters = []): array
    {
        $baseQuery = $this->createMock(QueryBuilder::class);
        $baseQuery->orders = null;

        $eloquentQuery = $this->createMock(Builder::class);
        $eloquentQuery->method('getQuery')->willReturn($baseQuery);

        $state = $this->createMock(DatabaseSearchState::class);
        $state->method('getQuery')->willReturn($eloquentQuery);
        $state->method('getActiveFilters')->willReturn($activeFilters);
        $state->method('isFulltextSearch')->willReturn(false);

        return [$state, $baseQuery, $eloquentQuery];
    }

    /** @test */
    public function it_skips_non_default_sort(): void
    {
        $mutator = new StickySearchMutator(
            $this->makeSettings(),
            $this->makeTagRepository()
        );

        $criteria = $this->makeCriteria([], false);
        [$state] = $this->makeState();

        // getActiveFilters should never be called if sortIsDefault is false
        $state->expects($this->never())->method('getActiveFilters');

        $mutator($state, $criteria);
    }

    /** @test */
    public function it_skips_fulltext_search(): void
    {
        $mutator = new StickySearchMutator(
            $this->makeSettings(),
            $this->makeTagRepository()
        );

        $criteria = $this->makeCriteria();
        $baseQuery = $this->createMock(QueryBuilder::class);
        $baseQuery->orders = null;

        $eloquentQuery = $this->createMock(Builder::class);
        $eloquentQuery->method('getQuery')->willReturn($baseQuery);

        $state = $this->createMock(DatabaseSearchState::class);
        $state->method('getQuery')->willReturn($eloquentQuery);
        $state->method('isFulltextSearch')->willReturn(true);
        $state->expects($this->never())->method('getActiveFilters');

        $mutator($state, $criteria);
    }

    /** @test */
    public function it_detects_tag_page_via_active_filters(): void
    {
        $tagFilter = $this->createMock(TagFilter::class);

        $mutator = new StickySearchMutator(
            $this->makeSettings(),
            $this->makeTagRepository(5)
        );

        // Both string and array slug formats should work
        foreach (['general', ['general']] as $slug) {
            $criteria = $this->makeCriteria(['tag' => $slug]);
            [$state, $baseQuery, $eloquentQuery] = $this->makeState([$tagFilter]);

            $eloquentQuery->expects($this->once())->method('leftJoin');

            $mutator($state, $criteria);

            $this->assertIsArray($baseQuery->orders);
            $this->assertEquals('is_stickiest', $baseQuery->orders[0]['column']);
        }
    }

    /** @test */
    public function it_handles_array_tag_filter_without_type_error(): void
    {
        $tagFilter = $this->createMock(TagFilter::class);
        $repo = $this->makeTagRepository(3);

        // Ensure getIdForSlug receives a string, not an array
        $repo->expects($this->once())
             ->method('getIdForSlug')
             ->with($this->isType('string'))
             ->willReturn(3);

        $mutator = new StickySearchMutator($this->makeSettings(), $repo);

        $criteria = $this->makeCriteria(['tag' => ['general']]); // array format (beta8+)
        [$state, $baseQuery, $eloquentQuery] = $this->makeState([$tagFilter]);
        $eloquentQuery->method('leftJoin')->willReturnSelf();

        $mutator($state, $criteria);
    }

    /** @test */
    public function it_prepends_stickiest_order_on_all_discussions(): void
    {
        $mutator = new StickySearchMutator(
            $this->makeSettings(),
            $this->makeTagRepository()
        );

        $criteria = $this->makeCriteria(); // no tag filter
        [$state, $baseQuery, $eloquentQuery] = $this->makeState([]); // no active filters

        $eloquentQuery->method('where')->willReturnSelf();

        $mutator($state, $criteria);

        $this->assertIsArray($baseQuery->orders);
        $this->assertEquals('is_stickiest', $baseQuery->orders[0]['column']);
        $this->assertEquals('desc', $baseQuery->orders[0]['direction']);
    }

    /** @test */
    public function it_hides_tag_stickies_in_all_discussions_when_setting_disabled(): void
    {
        $mutator = new StickySearchMutator(
            $this->makeSettings(['huseyinfiliz-stickiest.show_tag_sticky_in_all' => false]),
            $this->makeTagRepository()
        );

        $criteria = $this->makeCriteria();
        [$state, $baseQuery, $eloquentQuery] = $this->makeState([]);

        $eloquentQuery->expects($this->once())->method('where')->willReturnSelf();

        $mutator($state, $criteria);
    }

    /** @test */
    public function it_skips_tag_sticky_filter_when_show_all_setting_enabled(): void
    {
        $mutator = new StickySearchMutator(
            $this->makeSettings(['huseyinfiliz-stickiest.show_tag_sticky_in_all' => true]),
            $this->makeTagRepository()
        );

        $criteria = $this->makeCriteria();
        [$state, $baseQuery, $eloquentQuery] = $this->makeState([]);

        // where() should NOT be called when show_tag_sticky_in_all = true
        $eloquentQuery->expects($this->never())->method('where');

        $mutator($state, $criteria);
    }
}