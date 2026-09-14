<?php

namespace HuseyinFiliz\Stickiest\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class TagStickyRegressionTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /** Total number of plain regular discussions */
    const PLAIN_COUNT = 25;

    /** Multi-tag discussion, stickied ONLY in Tag A (tag 1) */
    const STICKY_IN_TAG_A_ONLY = 100;

    /** Multi-tag discussion, stickied in BOTH Tag A (1) and Tag B (2) */
    const STICKY_IN_BOTH_TAGS = 101;

    /** Super sticky discussion (uppermost everywhere) */
    const SUPER_STICKY = 102;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');
        $this->extension('huseyinfiliz-stickiest');

        $discussions = [];
        $posts = [];
        $discussionTag = [];

        // Generate 25 plain discussions with descending dates (Discussion 1 newest, 25 oldest)
        for ($i = 1; $i <= self::PLAIN_COUNT; $i++) {
            $discussions[] = [
                'id'             => $i,
                'title'          => "Plain Discussion $i",
                'slug'           => "plain-discussion-$i",
                'created_at'     => Carbon::now()->subMinutes(100 + $i)->toDateTimeString(),
                'last_posted_at' => Carbon::now()->subMinutes(100 + $i)->toDateTimeString(),
                'user_id'        => 1,
                'first_post_id'  => $i,
                'comment_count'  => 1,
                'is_stickiest'   => false,
                'is_tag_sticky'  => false,
            ];
            $posts[] = [
                'id'             => $i,
                'number'         => 1,
                'discussion_id'  => $i,
                'created_at'     => Carbon::now()->subMinutes(100 + $i)->toDateTimeString(),
                'user_id'        => 1,
                'type'           => 'comment',
                'content'        => "<t><p>Post $i</p></t>",
            ];
            $discussionTag[] = ['discussion_id' => $i, 'tag_id' => 1];
            $discussionTag[] = ['discussion_id' => $i, 'tag_id' => 2];
        }

        // 100: Tag sticky only in tag 1 (old date so natural position would be low)
        $discussions[] = [
            'id'             => self::STICKY_IN_TAG_A_ONLY,
            'title'          => 'Sticky in Tag A Only',
            'slug'           => 'sticky-tag-a-only',
            'created_at'     => Carbon::now()->subDays(5)->toDateTimeString(),
            'last_posted_at' => Carbon::now()->subDays(5)->toDateTimeString(),
            'user_id'        => 1,
            'first_post_id'  => self::STICKY_IN_TAG_A_ONLY,
            'comment_count'  => 1,
            'is_stickiest'   => false,
            'is_tag_sticky'  => true,
        ];
        $posts[] = [
            'id' => self::STICKY_IN_TAG_A_ONLY, 'number' => 1, 'discussion_id' => self::STICKY_IN_TAG_A_ONLY,
            'created_at' => Carbon::now()->subDays(5)->toDateTimeString(), 'user_id' => 1,
            'type' => 'comment', 'content' => '<t><p>Content</p></t>',
        ];
        $discussionTag[] = ['discussion_id' => self::STICKY_IN_TAG_A_ONLY, 'tag_id' => 1];
        $discussionTag[] = ['discussion_id' => self::STICKY_IN_TAG_A_ONLY, 'tag_id' => 2];

        // 101: Sticky in BOTH tag 1 and tag 2
        $discussions[] = [
            'id'             => self::STICKY_IN_BOTH_TAGS,
            'title'          => 'Sticky in Both Tags',
            'slug'           => 'sticky-in-both-tags',
            'created_at'     => Carbon::now()->subDays(4)->toDateTimeString(),
            'last_posted_at' => Carbon::now()->subDays(4)->toDateTimeString(),
            'user_id'        => 1,
            'first_post_id'  => self::STICKY_IN_BOTH_TAGS,
            'comment_count'  => 1,
            'is_stickiest'   => false,
            'is_tag_sticky'  => true,
        ];
        $posts[] = [
            'id' => self::STICKY_IN_BOTH_TAGS, 'number' => 1, 'discussion_id' => self::STICKY_IN_BOTH_TAGS,
            'created_at' => Carbon::now()->subDays(4)->toDateTimeString(), 'user_id' => 1,
            'type' => 'comment', 'content' => '<t><p>Content</p></t>',
        ];
        $discussionTag[] = ['discussion_id' => self::STICKY_IN_BOTH_TAGS, 'tag_id' => 1];
        $discussionTag[] = ['discussion_id' => self::STICKY_IN_BOTH_TAGS, 'tag_id' => 2];

        // 102: Super sticky
        $discussions[] = [
            'id'             => self::SUPER_STICKY,
            'title'          => 'Super Sticky Everywhere',
            'slug'           => 'super-sticky-everywhere',
            'created_at'     => Carbon::now()->subDays(10)->toDateTimeString(),
            'last_posted_at' => Carbon::now()->subDays(10)->toDateTimeString(),
            'user_id'        => 1,
            'first_post_id'  => self::SUPER_STICKY,
            'comment_count'  => 1,
            'is_stickiest'   => true,
            'is_tag_sticky'  => false,
        ];
        $posts[] = [
            'id' => self::SUPER_STICKY, 'number' => 1, 'discussion_id' => self::SUPER_STICKY,
            'created_at' => Carbon::now()->subDays(10)->toDateTimeString(), 'user_id' => 1,
            'type' => 'comment', 'content' => '<t><p>Content</p></t>',
        ];
        $discussionTag[] = ['discussion_id' => self::SUPER_STICKY, 'tag_id' => 1];
        $discussionTag[] = ['discussion_id' => self::SUPER_STICKY, 'tag_id' => 2];

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                [
                    'id'                 => 3,
                    'username'           => 'moderator',
                    'email'              => 'mod@example.com',
                    'password'           => '$2y$10$LO59tiT7uggl6Oe23o/O6uxy7CHhy3400ZBGl5bCAwtKEBF3UFKCO',
                    'is_email_confirmed' => 1,
                ],
            ],
            'tags' => [
                ['id' => 1, 'name' => 'Tag A', 'slug' => 'tag-a', 'color' => '#f00', 'position' => 1, 'is_restricted' => 0, 'is_hidden' => 0],
                ['id' => 2, 'name' => 'Tag B', 'slug' => 'tag-b', 'color' => '#0f0', 'position' => 2, 'is_restricted' => 0, 'is_hidden' => 0],
            ],
            'discussions'    => $discussions,
            'posts'          => $posts,
            'discussion_tag' => $discussionTag,
            'discussion_sticky_tag' => [
                ['discussion_id' => self::STICKY_IN_TAG_A_ONLY, 'tag_id' => 1],
                ['discussion_id' => self::STICKY_IN_BOTH_TAGS,  'tag_id' => 1],
                ['discussion_id' => self::STICKY_IN_BOTH_TAGS,  'tag_id' => 2],
            ],
            'group_permission' => [
                ['group_id' => 4, 'permission' => 'discussion.sticky'],
                ['group_id' => 4, 'permission' => 'discussion.stickiest'],
                ['group_id' => 4, 'permission' => 'discussion.tagSticky'],
            ],
            'group_user' => [
                ['group_id' => 4, 'user_id' => 3],
            ],
        ]);
    }

    /**
     * Helper to query discussion IDs for a tag filter with limit and offset.
     *
     * @return int[]
     */
    protected function getDiscussionIds(string $tagSlug, int $limit = 50, int $offset = 0): array
    {
        $request = $this->request('GET', '/api/discussions')->withQueryParams([
            'filter' => ['tag' => $tagSlug],
            'page'   => ['limit' => (string) $limit, 'offset' => (string) $offset],
        ]);

        $response = $this->send($request);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        return array_map('intval', array_column($body['data'], 'id'));
    }

    /**
     * 1. Regression test for Issue #1:
     * A discussion stickied in one tag must NOT be hidden in other primary tags it belongs to.
     *
     * @test
     */
    public function test_discussion_sticky_in_one_tag_is_not_hidden_in_other_primary_tags(): void
    {
        // View Tag B: Discussion 100 is NOT stickied in Tag B, but belongs to Tag B
        $tagBIds = $this->getDiscussionIds('tag-b', 50);

        // It must NOT be hidden
        $this->assertContains(self::STICKY_IN_TAG_A_ONLY, $tagBIds, 'Discussion stickied only in Tag A must still appear in Tag B');

        // It must NOT be pinned to the top of Tag B
        $this->assertNotEquals(self::STICKY_IN_TAG_A_ONLY, $tagBIds[0], 'Discussion stickied only in Tag A must not be pinned in Tag B');

        // View Tag A: Discussion 100 IS stickied in Tag A -> it must be pinned near top
        $tagAIds = $this->getDiscussionIds('tag-a', 50);
        $topPinned = array_slice($tagAIds, 0, 4);
        $this->assertContains(self::STICKY_IN_TAG_A_ONLY, $topPinned, 'Discussion stickied in Tag A must be pinned near top in Tag A');
    }

    /**
     * 2. Multiple primary tags sticky behavior:
     * When stickied in both Tag A and Tag B, it must be pinned in BOTH tags.
     * When stickied in Tag A only, it must be pinned in Tag A but regular in Tag B.
     *
     * @test
     */
    public function test_sticky_behavior_with_multiple_primary_tags(): void
    {
        $tagAIds = $this->getDiscussionIds('tag-a', 50);
        $tagBIds = $this->getDiscussionIds('tag-b', 50);

        // Discussion 101 is sticky in BOTH Tag A and Tag B:
        // In Tag A, top pinned items include 101
        $tagATop = array_slice($tagAIds, 0, 4);
        $this->assertContains(self::STICKY_IN_BOTH_TAGS, $tagATop, 'Discussion 101 must be pinned in Tag A');

        // In Tag B, top pinned items include 101
        $tagBTop = array_slice($tagBIds, 0, 4);
        $this->assertContains(self::STICKY_IN_BOTH_TAGS, $tagBTop, 'Discussion 101 must be pinned in Tag B');

        // Discussion 100 is sticky only in Tag A:
        $this->assertContains(self::STICKY_IN_TAG_A_ONLY, $tagATop, 'Discussion 100 must be pinned in Tag A');
        $this->assertNotContains(self::STICKY_IN_TAG_A_ONLY, array_slice($tagBIds, 0, 3), 'Discussion 100 must NOT be pinned in Tag B');
    }

    /**
     * 3. Regression test for PR #2:
     * Pagination with limit and offset must not lose or duplicate discussions across pages.
     *
     * @test
     */
    public function test_pagination_limit_and_offset_prevents_lost_or_duplicated_discussions(): void
    {
        // Tag A has 25 plain + 3 special = 28 discussions total
        $page1 = $this->getDiscussionIds('tag-a', 10, 0);
        $page2 = $this->getDiscussionIds('tag-a', 10, 10);
        $page3 = $this->getDiscussionIds('tag-a', 10, 20);

        $this->assertCount(10, $page1, 'Page 1 should have 10 items');
        $this->assertCount(10, $page2, 'Page 2 should have 10 items');
        $this->assertCount(8, $page3, 'Page 3 should have 8 items');

        // No overlap between any pages (fixes PR #2 repeat bug)
        $this->assertEmpty(array_intersect($page1, $page2), 'Page 1 and Page 2 must not repeat discussions');
        $this->assertEmpty(array_intersect($page2, $page3), 'Page 2 and Page 3 must not repeat discussions');
        $this->assertEmpty(array_intersect($page1, $page3), 'Page 1 and Page 3 must not repeat discussions');

        // All 28 discussions must be accounted for (fixes PR #2 truncation/lost bug)
        $allRetrieved = array_merge($page1, $page2, $page3);
        $this->assertCount(28, array_unique($allRetrieved), 'All 28 discussions must be present without duplicates');

        // Also verify on Tag B where Discussion 100 is an unstickied multi-tag discussion
        $tagBPage1 = $this->getDiscussionIds('tag-b', 15, 0);
        $tagBPage2 = $this->getDiscussionIds('tag-b', 15, 15);

        $this->assertEmpty(array_intersect($tagBPage1, $tagBPage2), 'Tag B pages must not overlap');
        $tagBAll = array_merge($tagBPage1, $tagBPage2);
        $this->assertContains(self::STICKY_IN_TAG_A_ONLY, $tagBAll, 'Discussion 100 must not be lost across Tag B pages');
    }

    /**
     * 4. Multiple sticky discussions ordering hierarchy:
     * 1) Super sticky is uppermost (#1)
     * 2) Tag stickies for this tag come next
     * 3) Plain non-sticky discussions follow
     *
     * @test
     */
    public function test_multiple_sticky_discussions_are_ordered_correctly(): void
    {
        $ids = $this->getDiscussionIds('tag-a', 10);

        // Super sticky (102) must be first
        $this->assertEquals(self::SUPER_STICKY, $ids[0], 'Super sticky must be the uppermost discussion');

        // Positions 1 and 2 must be the tag stickies (100 and 101)
        $tagStickies = [$ids[1], $ids[2]];
        $this->assertContains(self::STICKY_IN_TAG_A_ONLY, $tagStickies, 'Tag sticky 100 must be among tag sticky block');
        $this->assertContains(self::STICKY_IN_BOTH_TAGS, $tagStickies, 'Tag sticky 101 must be among tag sticky block');

        // Position 3 onwards must be plain discussions
        $this->assertLessThanOrEqual(self::PLAIN_COUNT, $ids[3], 'Discussions after stickies must be plain discussions');
    }

    /**
     * 5. Unsticky operation restores natural chronological sorting:
     * When discussions are unstickied, they fall back to their default position.
     *
     * @test
     */
    public function test_unsticky_operation_restores_natural_sorting(): void
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
            $this->request('PATCH', '/api/discussions/'.self::STICKY_IN_TAG_A_ONLY, [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'type'       => 'discussions',
                        'id'         => (string) self::STICKY_IN_TAG_A_ONLY,
                        'attributes' => ['isTagSticky' => false, 'stickyTagIds' => []],
                    ],
                ],
            ])
        );
        $this->assertEquals(200, $response->getStatusCode());

        // Query Tag A again
        $ids = $this->getDiscussionIds('tag-a', 50);

        // Discussion 102 was created 10 days ago (oldest date), so it should no longer be #1
        $this->assertNotEquals(self::SUPER_STICKY, $ids[0], 'Unsuperstickied discussion 102 should no longer be #1');

        // Discussion 101 is still tag-sticky, so it should now be at the very top!
        $this->assertEquals(self::STICKY_IN_BOTH_TAGS, $ids[0], 'Remaining tag sticky 101 should now be at top');

        // Discussion 100 was created 5 days ago, so it should not be among the top 2 pinned discussions anymore
        $this->assertNotContains(self::STICKY_IN_TAG_A_ONLY, array_slice($ids, 0, 2), 'Unstickied discussion 100 should not be pinned');

        // But Discussion 100 must still be present in the list
        $this->assertContains(self::STICKY_IN_TAG_A_ONLY, $ids, 'Unstickied discussion 100 must still be in discussion list');
    }
}
