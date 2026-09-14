<?php

/*
 * This file is part of Stickiest.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace HuseyinFiliz\Stickiest\Tests\unit;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Query\QueryCriteria;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Query\TagFilterGambit;
use Flarum\Tags\TagRepository;
use Flarum\User\User;
use HuseyinFiliz\Stickiest\PinStickiedDiscussionsToTop;
use Illuminate\Database\Query\Builder;
use PHPUnit\Framework\TestCase;

class PinStickiedDiscussionsToTopTest extends TestCase
{
    private function makeUser(): User
    {
        return $this->createMock(User::class);
    }

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

    private function makeCriteria(array $queryParams = [], bool $sortIsDefault = true): QueryCriteria
    {
        return new QueryCriteria($this->makeUser(), $queryParams, null, $sortIsDefault);
    }

    private function makeFilterState(array $activeFilters = []): array
    {
        $query = $this->createMock(Builder::class);
        $query->orders = null;

        $filterState = $this->createMock(FilterState::class);
        $filterState->method('getQuery')->willReturn($query);
        $filterState->method('getActiveFilters')->willReturn($activeFilters);
        $filterState->method('getActor')->willReturn($this->makeUser());

        return [$filterState, $query];
    }

    /** @test */
    public function it_skips_when_sort_is_not_default(): void
    {
        $pin = new PinStickiedDiscussionsToTop(
            $this->makeSettings(),
            $this->makeTagRepository()
        );

        $criteria = $this->makeCriteria([], false);
        [$filterState] = $this->makeFilterState();

        $filterState->expects($this->never())->method('getQuery');

        $pin($filterState, $criteria);
    }

    /** @test */
    public function it_pins_super_stickies_and_tag_stickies_on_tag_page(): void
    {
        $tagFilter = $this->createMock(TagFilterGambit::class);
        $pin = new PinStickiedDiscussionsToTop(
            $this->makeSettings(),
            $this->makeTagRepository(42)
        );

        foreach (['general', ['general']] as $slug) {
            $criteria = $this->makeCriteria(['tag' => $slug]);
            [$filterState, $query] = $this->makeFilterState([$tagFilter]);

            $query->expects($this->once())
                ->method('leftJoin')
                ->with('discussion_sticky_tag as dst', $this->isType('callable'))
                ->willReturnSelf();

            // Crucial: we do NOT call whereNotIn to hide discussions from unselected tags!
            $query->expects($this->never())
                ->method('whereNotIn');

            $pin($filterState, $criteria);

            $this->assertIsArray($query->orders);
            $this->assertEquals('is_stickiest', $query->orders[0]['column']);
            $this->assertEquals('desc', $query->orders[0]['direction']);
            $this->assertEquals('dst.tag_id', $query->orders[1]['column']);
            $this->assertEquals('desc', $query->orders[1]['direction']);
            $this->assertEquals('is_sticky', $query->orders[2]['column']);
            $this->assertEquals('desc', $query->orders[2]['direction']);
        }
    }

    /** @test */
    public function it_handles_tag_criteria_without_slug(): void
    {
        $tagFilter = $this->createMock(TagFilterGambit::class);
        $pin = new PinStickiedDiscussionsToTop(
            $this->makeSettings(),
            $this->makeTagRepository(null)
        );

        $criteria = $this->makeCriteria([]);
        [$filterState, $query] = $this->makeFilterState([$tagFilter]);

        $query->expects($this->never())->method('leftJoin');

        $pin($filterState, $criteria);
    }

    /** @test */
    public function it_preserves_existing_orders_when_prepending_sticky_orders(): void
    {
        $tagFilter = $this->createMock(TagFilterGambit::class);
        $pin = new PinStickiedDiscussionsToTop(
            $this->makeSettings(),
            $this->makeTagRepository(10)
        );

        $criteria = $this->makeCriteria(['tag' => 'announcements']);
        [$filterState, $query] = $this->makeFilterState([$tagFilter]);

        $query->orders = [
            ['column' => 'last_posted_at', 'direction' => 'desc']
        ];

        $query->method('leftJoin')->willReturnSelf();

        $pin($filterState, $criteria);

        $this->assertCount(4, $query->orders);
        $this->assertEquals('is_stickiest', $query->orders[0]['column']);
        $this->assertEquals('dst.tag_id', $query->orders[1]['column']);
        $this->assertEquals('is_sticky', $query->orders[2]['column']);
        $this->assertEquals('last_posted_at', $query->orders[3]['column']);
        $this->assertEquals('desc', $query->orders[3]['direction']);
    }

    /** @test */
    public function it_does_not_modify_query_for_multiple_active_filters(): void
    {
        $tagFilter = $this->createMock(TagFilterGambit::class);
        $otherFilter = $this->createMock(FilterInterface::class);

        $pin = new PinStickiedDiscussionsToTop(
            $this->makeSettings(),
            $this->makeTagRepository(1)
        );

        $criteria = $this->makeCriteria(['tag' => 'general']);
        [$filterState, $query] = $this->makeFilterState([$tagFilter, $otherFilter]);

        $query->expects($this->never())->method('leftJoin');

        $pin($filterState, $criteria);
    }

    /** @test */
    public function it_does_not_modify_query_for_non_tag_single_filter(): void
    {
        $otherFilter = $this->createMock(FilterInterface::class);

        $pin = new PinStickiedDiscussionsToTop(
            $this->makeSettings(),
            $this->makeTagRepository(1)
        );

        $criteria = $this->makeCriteria(['tag' => 'general']);
        [$filterState, $query] = $this->makeFilterState([$otherFilter]);

        $query->expects($this->never())->method('leftJoin');

        $pin($filterState, $criteria);
    }
}
