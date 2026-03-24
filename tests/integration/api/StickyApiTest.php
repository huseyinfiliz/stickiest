<?php

namespace HuseyinFiliz\Stickiest\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class StickyApiTest extends TestCase
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
                [
                    'id'                 => 3,
                    'username'           => 'moderator',
                    'email'              => 'mod@example.com',
                    'password'           => '$2y$10$LO59tiT7uggl6Oe23o/O6uxy7CHhy3400ZBGl5bCAwtKEBF3UFKCO',
                    'is_email_confirmed' => 1,
                ],
            ],
            'discussions' => [
                [
                    'id'             => 1,
                    'title'          => 'Normal Discussion',
                    'slug'           => 'normal-discussion',
                    'created_at'     => Carbon::now()->toDateTimeString(),
                    'last_posted_at' => Carbon::now()->toDateTimeString(),
                    'user_id'        => 1,
                    'first_post_id'  => 1,
                    'comment_count'  => 1,
                    'is_stickiest'   => false,
                    'is_tag_sticky'  => false,
                ],
                [
                    'id'             => 2,
                    'title'          => 'Super Sticky Discussion',
                    'slug'           => 'super-sticky-discussion',
                    'created_at'     => Carbon::now()->toDateTimeString(),
                    'last_posted_at' => Carbon::now()->toDateTimeString(),
                    'user_id'        => 1,
                    'first_post_id'  => 2,
                    'comment_count'  => 1,
                    'is_stickiest'   => true,
                    'is_tag_sticky'  => false,
                ],
            ],
            'posts' => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Post 1</p></t>'],
                ['id' => 2, 'number' => 1, 'discussion_id' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Post 2</p></t>'],
            ],
            'group_permission' => [
                ['group_id' => 4, 'permission' => 'discussion.stickiest'],
                ['group_id' => 4, 'permission' => 'discussion.tagSticky'],
            ],
            'group_user' => [
                ['group_id' => 4, 'user_id' => 3],
            ],
        ]);
    }

    /** @test */
    public function guest_can_see_discussion_attributes(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/2')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body  = json_decode($response->getBody()->getContents(), true);
        $attrs = $body['data']['attributes'];

        $this->assertTrue($attrs['isStickiest']);
        $this->assertFalse($attrs['isTagSticky']);
        $this->assertArrayHasKey('canStickiest', $attrs);
        $this->assertArrayHasKey('canTagSticky', $attrs);
    }

    /** @test */
    public function guest_cannot_sticky_discussion(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', [
                'json' => [
                    'data' => [
                        'type'       => 'discussions',
                        'id'         => '1',
                        'attributes' => ['isStickiest' => true],
                    ],
                ],
            ])
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    /** @test */
    public function normal_user_cannot_super_sticky_discussion(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', [
                'authenticatedAs' => 2,
                'json'            => [
                    'data' => [
                        'type'       => 'discussions',
                        'id'         => '1',
                        'attributes' => ['isStickiest' => true],
                    ],
                ],
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function moderator_can_super_sticky_discussion(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'type'       => 'discussions',
                        'id'         => '1',
                        'attributes' => ['isStickiest' => true],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['data']['attributes']['isStickiest']);
    }

    /** @test */
    public function moderator_can_remove_super_sticky(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/2', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'type'       => 'discussions',
                        'id'         => '2',
                        'attributes' => ['isStickiest' => false],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['data']['attributes']['isStickiest']);
    }

    /** @test */
    public function moderator_can_tag_sticky_discussion(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'type'       => 'discussions',
                        'id'         => '1',
                        'attributes' => ['isTagSticky' => true, 'stickyTagIds' => []],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['data']['attributes']['isTagSticky']);
    }

    /** @test */
    public function normal_user_cannot_tag_sticky_discussion(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', [
                'authenticatedAs' => 2,
                'json'            => [
                    'data' => [
                        'type'       => 'discussions',
                        'id'         => '1',
                        'attributes' => ['isTagSticky' => true],
                    ],
                ],
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function moderator_sees_can_sticky_as_true(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body  = json_decode($response->getBody()->getContents(), true);
        $attrs = $body['data']['attributes'];

        $this->assertTrue($attrs['canStickiest']);
        $this->assertTrue($attrs['canTagSticky']);
    }

    /** @test */
    public function normal_user_sees_can_sticky_as_false(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body  = json_decode($response->getBody()->getContents(), true);
        $attrs = $body['data']['attributes'];

        $this->assertFalse($attrs['canStickiest']);
        $this->assertFalse($attrs['canTagSticky']);
    }
}