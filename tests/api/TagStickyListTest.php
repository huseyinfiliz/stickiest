<?php

/*
 * This file is part of Stickiest.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace HuseyinFiliz\Stickiest\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

/**
 * Tag-sticky filtering must not depend on the page size or offset.
 *
 * Regression test: PinStickiedDiscussionsToTop used to clone the query and
 * pluck ids for its exclusion list. AbstractFilterer applies limit/offset
 * before filter mutators run, so the clone inherited them and the list came
 * back truncated (small page = wrong pins) and offset-shifted (page 2 dropped
 * the exclusion entirely and repeated page 1).
 */
class TagStickyListTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /** Plain, non-sticky discussions, enough to need a second page. */
    const PLAIN = 25;

    /** Tag-sticky in the OTHER tag only: must never show up under /t/parent. */
    const FOREIGN_STICKY = 100;

    /** Tag-sticky in the tag we list: must be pinned first. */
    const OWN_STICKY = 101;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');
        $this->extension('flarum-sticky');
        $this->extension('huseyinfiliz-stickiest');

        $discussions = [];
        $posts = [];
        $discussionTag = [];

        $rows = array_merge(range(1, self::PLAIN), [self::FOREIGN_STICKY, self::OWN_STICKY]);

        foreach ($rows as $i => $id) {
            $discussions[] = [
                'id'             => $id,
                'title'          => 'Discussion '.$id,
                'created_at'     => Carbon::now(),
                'last_posted_at' => Carbon::now()->subMinutes(count($rows) - $i),
                'user_id'        => 2,
                'first_post_id'  => $id,
                'comment_count'  => 1,
                'is_sticky'      => 0,
                'is_stickiest'   => 0,
                'is_tag_sticky'  => $id >= self::FOREIGN_STICKY ? 1 : 0,
            ];
            $posts[] = [
                'id' => $id, 'number' => 1, 'discussion_id' => $id,
                'created_at' => Carbon::now(), 'user_id' => 2,
                'type' => 'comment', 'content' => '<t><p>First post</p></t>',
            ];
            $discussionTag[] = ['discussion_id' => $id, 'tag_id' => 1];
        }

        $this->prepareDatabase([
            'users' => [$this->normalUser()],
            'tags'  => [
                ['id' => 1, 'name' => 'Parent', 'slug' => 'parent', 'position' => 0, 'parent_id' => null, 'is_restricted' => 0, 'is_hidden' => 0],
                ['id' => 2, 'name' => 'Other', 'slug' => 'other', 'position' => 1, 'parent_id' => null, 'is_restricted' => 0, 'is_hidden' => 0],
            ],
            'discussions'           => $discussions,
            'posts'                 => $posts,
            'discussion_tag'        => $discussionTag,
            'discussion_sticky_tag' => [
                ['discussion_id' => self::FOREIGN_STICKY, 'tag_id' => 2],
                ['discussion_id' => self::OWN_STICKY, 'tag_id' => 1],
            ],
        ]);
    }

    /**
     * @return int[]
     */
    protected function listTag(int $limit, int $offset = 0): array
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions?filter[tag]=parent&page[limit]='.$limit.'&page[offset]='.$offset)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        return array_map('intval', array_column($body['data'], 'id'));
    }

    /** @test */
    public function own_tag_sticky_is_pinned_and_foreign_tag_sticky_is_hidden(): void
    {
        foreach ([3, 5, 20, 30] as $limit) {
            $ids = $this->listTag($limit);

            $this->assertSame(self::OWN_STICKY, $ids[0], "limit=$limit should pin the tag's own sticky first");
            $this->assertNotContains(self::FOREIGN_STICKY, $ids, "limit=$limit leaked a sticky from another tag");
        }
    }

    /** @test */
    public function pages_do_not_overlap(): void
    {
        $first = $this->listTag(20);
        $second = $this->listTag(20, 20);

        $this->assertNotContains(self::FOREIGN_STICKY, $second, 'page 2 leaked a sticky from another tag');
        $this->assertSame([], array_intersect($first, $second), 'page 2 repeated rows from page 1');
    }
}
