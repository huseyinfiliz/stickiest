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
 * Tag-sticky listing & regression tests.
 *
 * Specifically verifies:
 * 1. Discussions stickied in one tag are NOT hidden in other primary tags (fixes Issue #1).
 * 2. Discussions stickied across multiple primary tags behave correctly in each tag.
 * 3. Pagination with limit and offset does not lose or repeat discussions (fixes PR #2).
 * 4. Multiple sticky types (super sticky, tag sticky, regular sticky, plain) are ordered correctly.
 * 5. Unsticking a discussion restores normal chronological sorting.
 */
class TagStickyListTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /** Number of plain, non-sticky discussions */
    const PLAIN_COUNT = 25;

    /** Multi-tag discussion, stickied ONLY in Parent tag (tag 1) */
    const STICKY_IN_TAG_1_ONLY = 100;

    /** Multi-tag discussion, stickied in BOTH Parent (tag 1) and Other (tag 2) */
    const STICKY_IN_BOTH_TAGS = 101;

    /** Super sticky discussion (uppermost everywhere) */
    const SUPER_STICKY = 102;

    /** Regular sticky discussion */
    const REGULAR_STICKY = 103;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');
        $this->extension('flarum-sticky');
        $this->extension('huseyinfiliz-stickiest');

        $discussions = [];
        $posts = [];
        $discussionTag = [];

        // 25 plain discussions with descending dates (Discussion 1 newest, 25 oldest)
        for ($i = 1; $i <= self::PLAIN_COUNT; $i++) {
            $discussions[] = [
                'id'             => $i,
                'title'          => "Plain Discussion $i",
                'created_at'     => Carbon::now()->subMinutes(100 + $i),
                'last_posted_at' => Carbon::now()->subMinutes(100 + $i),
                'user_id'        => 2,
                'first_post_id'  => $i,
                'comment_count'  => 1,
                'is_sticky'      => 0,
                'is_stickiest'   => 0,
                'is_tag_sticky'  => 0,
            ];
            $posts[] = [
                'id'            => $i,
                'number'        => 1,
                'discussion_id' => $i,
                'created_at'    => Carbon::now()->subMinutes(100 + $i),
                'user_id'       => 2,
                'type'          => 'comment',
                'content'       => "<t><p>Post $i</p></t>",
            ];
            $discussionTag[] = ['discussion_id' => $i, 'tag_id' => 1];
            $discussionTag[] = ['discussion_id' => $i, 'tag_id' => 2];
        }

        // 100: Tag sticky only in tag 1 (old date so natural position is low)
        $discussions[] = [
            'id'             => self::STICKY_IN_TAG_1_ONLY,
            'title'          => 'Sticky in Tag 1 Only',
            'created_at'     => Carbon::now()->subDays(5),
            'last_posted_at' => Carbon::now()->subDays(5),
            'user_id'        => 2,
            'first_post_id'  => self::STICKY_IN_TAG_1_ONLY,
            'comment_count'  => 1,
            'is_sticky'      => 0,
            'is_stickiest'   => 0,
            'is_tag_sticky'  => 1,
        ];
        $posts[] = [
            'id' => self::STICKY_IN_TAG_1_ONLY, 'number' => 1, 'discussion_id' => self::STICKY_IN_TAG_1_ONLY,
            'created_at' => Carbon::now()->subDays(5), 'user_id' => 2,
            'type' => 'comment', 'content' => '<t><p>First post</p></t>',
        ];
        $discussionTag[] = ['discussion_id' => self::STICKY_IN_TAG_1_ONLY, 'tag_id' => 1];
        $discussionTag[] = ['discussion_id' => self::STICKY_IN_TAG_1_ONLY, 'tag_id' => 2];

        // 101: Sticky in BOTH tag 1 and tag 2
        $discussions[] = [
            'id'             => self::STICKY_IN_BOTH_TAGS,
            'title'          => 'Sticky in Both Tags',
            'created_at'     => Carbon::now()->subDays(4),
            'last_posted_at' => Carbon::now()->subDays(4),
            'user_id'        => 2,
            'first_post_id'  => self::STICKY_IN_BOTH_TAGS,
            'comment_count'  => 1,
            'is_sticky'      => 0,
            'is_stickiest'   => 0,
            'is_tag_sticky'  => 1,
        ];
        $posts[] = [
            'id' => self::STICKY_IN_BOTH_TAGS, 'number' => 1, 'discussion_id' => self::STICKY_IN_BOTH_TAGS,
            'created_at' => Carbon::now()->subDays(4), 'user_id' => 2,
            'type' => 'comment', 'content' => '<t><p>First post</p></t>',
        ];
        $discussionTag[] = ['discussion_id' => self::STICKY_IN_BOTH_TAGS, 'tag_id' => 1];
        $discussionTag[] = ['discussion_id' => self::STICKY_IN_BOTH_TAGS, 'tag_id' => 2];

        // 102: Super sticky
        $discussions[] = [
            'id'             => self::SUPER_STICKY,
            'title'          => 'Super Sticky Everywhere',
            'created_at'     => Carbon::now()->subDays(10),
            'last_posted_at' => Carbon::now()->subDays(10),
            'user_id'        => 2,
            'first_post_id'  => self::SUPER_STICKY,
            'comment_count'  => 1,
            'is_sticky'      => 0,
            'is_stickiest'   => 1,
            'is_tag_sticky'  => 0,
        ];
        $posts[] = [
            'id' => self::SUPER_STICKY, 'number' => 1, 'discussion_id' => self::SUPER_STICKY,
            'created_at' => Carbon::now()->subDays(10), 'user_id' => 2,
            'type' => 'comment', 'content' => '<t><p>First post</p></t>',
        ];
        $discussionTag[] = ['discussion_id' => self::SUPER_STICKY, 'tag_id' => 1];
        $discussionTag[] = ['discussion_id' => self::SUPER_STICKY, 'tag_id' => 2];

        // 103: Regular sticky
        $discussions[] = [
            'id'             => self::REGULAR_STICKY,
            'title'          => 'Regular Sticky',
            'created_at'     => Carbon::now()->subDays(6),
            'last_posted_at' => Carbon::now()->subDays(6),
            'user_id'        => 2,
            'first_post_id'  => self::REGULAR_STICKY,
            'comment_count'  => 1,
            'is_sticky'      => 1,
            'is_stickiest'   => 0,
            'is_tag_sticky'  => 0,
        ];
        $posts[] = [
            'id' => self::REGULAR_STICKY, 'number' => 1, 'discussion_id' => self::REGULAR_STICKY,
            'created_at' => Carbon::now()->subDays(6), 'user_id' => 2,
            'type' => 'comment', 'content' => '<t><p>First post</p></t>',
        ];
        $discussionTag[] = ['discussion_id' => self::REGULAR_STICKY, 'tag_id' => 1];
        $discussionTag[] = ['discussion_id' => self::REGULAR_STICKY, 'tag_id' => 2];

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                [
                    'id'                 => 3,
                    'username'           => 'moderator',
                    'email'              => 'mod@example.com',
                    'password'           => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim',
                    'is_email_confirmed' => 1,
                ],
            ],
            'tags' => [
                ['id' => 1, 'name' => 'Parent', 'slug' => 'parent', 'position' => 0, 'parent_id' => null, 'is_restricted' => 0, 'is_hidden' => 0],
                ['id' => 2, 'name' => 'Other',  'slug' => 'other',  'position' => 1, 'parent_id' => null, 'is_restricted' => 0, 'is_hidden' => 0],
            ],
            'discussions'           => $discussions,
            'posts'                 => $posts,
            'discussion_tag'        => $discussionTag,
            'discussion_sticky_tag' => [
                ['discussion_id' => self::STICKY_IN_TAG_1_ONLY, 'tag_id' => 1],
                ['discussion_id' => self::STICKY_IN_BOTH_TAGS,  'tag_id' => 1],
                ['discussion_id' => self::STICKY_IN_BOTH_TAGS,  'tag_id' => 2],
            ],
            'group_permission' => [
                ['group_id' => 4, 'permission' => 'discussion.sticky'],
                ['group_id' => 4, 'permission' => 'discussion.stickiest'],
                ['group_id' => 4, 'permission' => 'discussion.stickiest.tagSticky'],
            ],
            'group_user' => [
                ['group_id' => 4, 'user_id' => 3],
            ],
        ]);
    }

    /**
     * @return int[]
     */
    protected function listTag(string $tagSlug = 'parent', int $limit = 50, int $offset = 0): array
    {
        $uri = "/api/discussions?filter[tag]=$tagSlug&page[limit]=$limit&page[offset]=$offset";
        $request = $this->request('GET', $uri)->withQueryParams([
            'filter' => ['tag' => $tagSlug],
            'page'   => ['limit' => $limit, 'offset' => $offset],
        ]);

        $response = $this->send($request);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        return array_map('intval', array_column($body['data'], 'id'));
    }

    /**
     * 1. Issue #1 regression test:
     * A discussion stickied only in Tag 1 must NOT be hidden from Tag 2 discussion list.
     *
     * @test
     */
    public function discussion_sticky_in_one_tag_is_not_hidden_in_other_primary_tags(): void
    {
        // View tag 2 (other): Discussion 100 is not stickied here, but belongs to tag 2
        $tag2Ids = $this->listTag('other', 50);

        // It must NOT be hidden
        $this->assertContains(self::STICKY_IN_TAG_1_ONLY, $tag2Ids, 'Discussion stickied only in Tag 1 must still appear in Tag 2');

        // It must NOT be pinned to the top of Tag 2
        $this->assertNotEquals(self::STICKY_IN_TAG_1_ONLY, $tag2Ids[0], 'Discussion stickied only in Tag 1 must not be pinned in Tag 2');

        // View tag 1 (parent): Discussion 100 IS stickied here -> must be pinned near top
        $tag1Ids = $this->listTag('parent', 50);
        $topPinned = array_slice($tag1Ids, 0, 4);
        $this->assertContains(self::STICKY_IN_TAG_1_ONLY, $topPinned, 'Discussion stickied in Tag 1 must be pinned in Tag 1');
    }

    /**
     * 2. Multiple primary tags sticky behavior:
     * Stickied in both -> pinned in both.
     * Stickied in one -> pinned only in that one, regular in the other.
     *
     * @test
     */
    public function sticky_behavior_with_multiple_primary_tags(): void
    {
        $tag1Ids = $this->listTag('parent', 50);
        $tag2Ids = $this->listTag('other', 50);

        // Discussion 101 is stickied in BOTH tags:
        $tag1Top = array_slice($tag1Ids, 0, 4);
        $this->assertContains(self::STICKY_IN_BOTH_TAGS, $tag1Top, 'Discussion 101 must be pinned in Tag 1');

        $tag2Top = array_slice($tag2Ids, 0, 4);
        $this->assertContains(self::STICKY_IN_BOTH_TAGS, $tag2Top, 'Discussion 101 must be pinned in Tag 2');

        // Discussion 100 is stickied only in Tag 1:
        $this->assertContains(self::STICKY_IN_TAG_1_ONLY, $tag1Top, 'Discussion 100 must be pinned in Tag 1');
        $this->assertNotContains(self::STICKY_IN_TAG_1_ONLY, array_slice($tag2Ids, 0, 3), 'Discussion 100 must NOT be pinned in Tag 2');
    }

    /**
     * 3. PR #2 regression test:
     * Pagination with limit and offset must not lose discussions or repeat rows.
     *
     * @test
     */
    public function pagination_limit_and_offset_prevents_lost_or_duplicated_discussions(): void
    {
        // Tag 1 has 25 plain + 4 special = 29 total
        $page1 = $this->listTag('parent', 10, 0);
        $page2 = $this->listTag('parent', 10, 10);
        $page3 = $this->listTag('parent', 10, 20);

        $this->assertCount(10, $page1, 'Page 1 should have 10 items');
        $this->assertCount(10, $page2, 'Page 2 should have 10 items');
        $this->assertCount(9, $page3, 'Page 3 should have 9 items');

        // No overlap between pages (fixes PR #2 repeat bug)
        $this->assertEmpty(array_intersect($page1, $page2), 'Page 1 and Page 2 must not repeat discussions');
        $this->assertEmpty(array_intersect($page2, $page3), 'Page 2 and Page 3 must not repeat discussions');
        $this->assertEmpty(array_intersect($page1, $page3), 'Page 1 and Page 3 must not repeat discussions');

        // All 29 discussions must be accounted for without loss
        $allRetrieved = array_merge($page1, $page2, $page3);
        $this->assertCount(29, array_unique($allRetrieved), 'All 29 discussions must be present without duplicates');

        // Also verify on Tag 2 where Discussion 100 is unstickied
        $tag2Page1 = $this->listTag('other', 15, 0);
        $tag2Page2 = $this->listTag('other', 15, 15);

        $this->assertEmpty(array_intersect($tag2Page1, $tag2Page2), 'Tag 2 pages must not overlap');
        $tag2All = array_merge($tag2Page1, $tag2Page2);
        $this->assertContains(self::STICKY_IN_TAG_1_ONLY, $tag2All, 'Discussion 100 must not be lost across Tag 2 pages');
    }

    /**
     * 4. Multiple sticky discussions ordering hierarchy:
     * 1) Super sticky uppermost (#1)
     * 2) Tag stickies for this tag
     * 3) Regular stickies
     * 4) Plain non-sticky discussions follow
     *
     * @test
     */
    public function multiple_sticky_discussions_are_ordered_correctly(): void
    {
        $ids = $this->listTag('parent', 10);

        // Super sticky (102) must be first
        $this->assertEquals(self::SUPER_STICKY, $ids[0], 'Super sticky must be the uppermost discussion');

        // Positions 1 and 2 must be tag stickies (100 and 101)
        $tagStickies = [$ids[1], $ids[2]];
        $this->assertContains(self::STICKY_IN_TAG_1_ONLY, $tagStickies, 'Tag sticky 100 must be in tag sticky block');
        $this->assertContains(self::STICKY_IN_BOTH_TAGS, $tagStickies, 'Tag sticky 101 must be in tag sticky block');

        // Position 3 must be regular sticky (103)
        $this->assertEquals(self::REGULAR_STICKY, $ids[3], 'Regular sticky 103 must appear after tag stickies');

        // Position 4 onwards must be plain discussions
        $this->assertLessThanOrEqual(self::PLAIN_COUNT, $ids[4], 'Discussions after stickies must be plain discussions');
    }

    /**
     * 5. Unsticky operation restores natural chronological sorting.
     *
     * @test
     */
    public function unsticky_operation_restores_natural_sorting(): void
    {
        // Unsupersticky Discussion 102
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/'.self::SUPER_STICKY, [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'type'       => 'discussions',
                        'id'         => (string) self::SUPER_STICKY,
                        'attributes' => ['isStickiest' => false],
                    ],
                ],
            ])
        );
        $this->assertEquals(200, $response->getStatusCode());

        // Remove tag sticky from Discussion 100
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/'.self::STICKY_IN_TAG_1_ONLY, [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'type'       => 'discussions',
                        'id'         => (string) self::STICKY_IN_TAG_1_ONLY,
                        'attributes' => ['isTagSticky' => false],
                    ],
                ],
            ])
        );
        $this->assertEquals(200, $response->getStatusCode());

        // Query Tag 1 again
        $ids = $this->listTag('parent', 50);

        // Discussion 102 was created 10 days ago (oldest date), so it should no longer be #1
        $this->assertNotEquals(self::SUPER_STICKY, $ids[0], 'Unsuperstickied discussion 102 should no longer be #1');

        // Discussion 101 is still tag-sticky, so it should now be at the very top
        $this->assertEquals(self::STICKY_IN_BOTH_TAGS, $ids[0], 'Remaining tag sticky 101 should now be at top');

        // Discussion 100 was created 5 days ago, so it should not be among the top pinned discussions
        $this->assertNotContains(self::STICKY_IN_TAG_1_ONLY, array_slice($ids, 0, 2), 'Unstickied discussion 100 should not be pinned');

        // But Discussion 100 must still be present in the list
        $this->assertContains(self::STICKY_IN_TAG_1_ONLY, $ids, 'Unstickied discussion 100 must still be in discussion list');
    }
}
