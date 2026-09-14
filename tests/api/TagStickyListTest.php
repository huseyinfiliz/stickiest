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
 * Tag-sticky listing tests.
 *
 * Verifies that:
 * 1. Discussions stickied in the current tag are pinned to the top.
 * 2. Discussions that belong to this tag but are stickied in another tag
 *    are NOT hidden; they appear normally in the discussion list (fixes #1).
 * 3. Pagination limits and offsets do not cause duplicates or row repetition (fixes #2).
 */
class TagStickyListTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /** Plain, non-sticky discussions, enough to need a second page. */
    const PLAIN = 25;

    /** Tag-sticky in OTHER tag (2), but also has parent tag (1). */
    const FOREIGN_STICKY = 100;

    /** Tag-sticky in the current tag (1): must be pinned first. */
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

        // FOREIGN_STICKY also belongs to tag 2
        $discussionTag[] = ['discussion_id' => self::FOREIGN_STICKY, 'tag_id' => 2];

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
    public function own_tag_sticky_is_pinned_first_and_other_tags_are_not_hidden(): void
    {
        $ids = $this->listTag(30);

        $this->assertSame(self::OWN_STICKY, $ids[0], 'Tag own sticky should be pinned first');
        $this->assertContains(self::FOREIGN_STICKY, $ids, 'Discussion stickied in other tag should remain visible');
    }

    /** @test */
    public function pages_do_not_overlap(): void
    {
        $first = $this->listTag(20);
        $second = $this->listTag(20, 20);

        $this->assertSame([], array_intersect($first, $second), 'Page 2 should not repeat rows from page 1');
    }
}
