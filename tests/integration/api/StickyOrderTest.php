<?php

namespace HuseyinFiliz\Stickiest\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class StickyOrderTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');
        $this->extension('huseyinfiliz-stickiest');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
            'tags' => [
                ['id' => 1, 'name' => 'General', 'slug' => 'general', 'description' => null, 'color' => '#888', 'background_url' => null, 'background_mode' => null, 'position' => 1, 'parent_id' => null, 'default_sort' => null, 'is_restricted' => 0, 'is_hidden' => 0, 'discussion_count' => 3, 'last_posted_at' => Carbon::now()->toDateTimeString(), 'last_posted_discussion_id' => null, 'last_posted_user_id' => null, 'icon' => null],
            ],
            'discussions' => [
                [
                    'id'             => 1,
                    'title'          => 'Regular Discussion',
                    'created_at'     => Carbon::now()->subDays(3)->toDateTimeString(),
                    'last_posted_at' => Carbon::now()->subDays(3)->toDateTimeString(),
                    'user_id'        => 1,
                    'first_post_id'  => 1,
                    'comment_count'  => 1,
                    'is_stickiest'   => false,
                    'is_tag_sticky'  => false,
                ],
                [
                    'id'             => 2,
                    'title'          => 'Tag Sticky Discussion',
                    'created_at'     => Carbon::now()->subDays(2)->toDateTimeString(),
                    'last_posted_at' => Carbon::now()->subDays(2)->toDateTimeString(),
                    'user_id'        => 1,
                    'first_post_id'  => 2,
                    'comment_count'  => 1,
                    'is_stickiest'   => false,
                    'is_tag_sticky'  => true,
                ],
                [
                    'id'             => 3,
                    'title'          => 'Super Sticky Discussion',
                    'created_at'     => Carbon::now()->subDays(1)->toDateTimeString(),
                    'last_posted_at' => Carbon::now()->subDays(1)->toDateTimeString(),
                    'user_id'        => 1,
                    'first_post_id'  => 3,
                    'comment_count'  => 1,
                    'is_stickiest'   => true,
                    'is_tag_sticky'  => false,
                ],
            ],
            'posts' => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->subDays(3)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>p1</p></t>'],
                ['id' => 2, 'number' => 1, 'discussion_id' => 2, 'created_at' => Carbon::now()->subDays(2)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>p2</p></t>'],
                ['id' => 3, 'number' => 1, 'discussion_id' => 3, 'created_at' => Carbon::now()->subDays(1)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>p3</p></t>'],
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 2, 'tag_id' => 1],
                ['discussion_id' => 3, 'tag_id' => 1],
            ],
            'discussion_sticky_tag' => [
                ['discussion_id' => 2, 'tag_id' => 1],
            ],
        ]);
    }

    /** @test */
    public function super_sticky_appears_first_in_all_discussions(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $ids  = array_column($body['data'], 'id');

        // Super sticky (id=3) must be first
        $this->assertEquals(3, (int) $ids[0], 'Super sticky should be first');
    }

    /** @test */
    public function tag_sticky_hidden_in_all_discussions_when_setting_off(): void
    {
        $this->setting('huseyinfiliz-stickiest.show_tag_sticky_in_all', '0');

        $response = $this->send(
            $this->request('GET', '/api/discussions')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $ids  = array_column($body['data'], 'id');

        // Tag sticky (id=2) should not appear
        $this->assertNotContains('2', $ids, 'Tag sticky should be hidden in All Discussions when setting is off');
    }

    /** @test */
    public function tag_sticky_shown_in_all_discussions_when_setting_on(): void
    {
        $this->setting('huseyinfiliz-stickiest.show_tag_sticky_in_all', '1');

        $response = $this->send(
            $this->request('GET', '/api/discussions')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $ids  = array_column($body['data'], 'id');

        $this->assertContains('2', $ids, 'Tag sticky should appear in All Discussions when setting is on');
    }

    /** @test */
    public function super_sticky_always_shown_even_when_tag_sticky_filter_is_on(): void
    {
        $this->setting('huseyinfiliz-stickiest.show_tag_sticky_in_all', '0');

        $response = $this->send(
            $this->request('GET', '/api/discussions')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $ids  = array_column($body['data'], 'id');

        $this->assertContains('3', $ids, 'Super sticky must always appear regardless of tag_sticky setting');
        $this->assertEquals(3, (int) $ids[0], 'Super sticky must be first');
    }
}