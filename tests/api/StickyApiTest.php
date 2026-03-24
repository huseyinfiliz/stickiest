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
 * Stickiest API entegrasyon testleri.
 *
 * Moderatörlerin tartışmaları sticky, super-sticky ve
 * tag-sticky yapabildiğini; normal kullanıcıların yapamadığını doğrular.
 */
class StickyApiTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');
        $this->extension('flarum-sticky');
        $this->extension('huseyinfiliz-stickiest');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                [
                    'id'                 => 3,
                    'username'           => 'moderator',
                    'email'              => 'mod@example.com',
                    'password'           => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', // "too-obscure"
                    'is_email_confirmed' => 1,
                ],
            ],
            'discussions' => [
                [
                    'id'             => 1,
                    'title'          => 'Normal Tartışma',
                    'created_at'     => Carbon::now(),
                    'last_posted_at' => Carbon::now(),
                    'user_id'        => 2,
                    'first_post_id'  => 1,
                    'comment_count'  => 1,
                    'is_sticky'      => 0,
                    'is_stickiest'   => 0,
                    'is_tag_sticky'  => 0,
                ],
                [
                    'id'             => 2,
                    'title'          => 'Zaten Sticky Tartışma',
                    'created_at'     => Carbon::now(),
                    'last_posted_at' => Carbon::now(),
                    'user_id'        => 2,
                    'first_post_id'  => 2,
                    'comment_count'  => 1,
                    'is_sticky'      => 1,
                    'is_stickiest'   => 0,
                    'is_tag_sticky'  => 0,
                ],
                [
                    'id'             => 3,
                    'title'          => 'Zaten Super-Sticky Tartışma',
                    'created_at'     => Carbon::now(),
                    'last_posted_at' => Carbon::now(),
                    'user_id'        => 2,
                    'first_post_id'  => 3,
                    'comment_count'  => 1,
                    'is_sticky'      => 0,
                    'is_stickiest'   => 1,
                    'is_tag_sticky'  => 0,
                ],
            ],
            'posts' => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>İlk post</p></t>'],
                ['id' => 2, 'number' => 1, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>İlk post</p></t>'],
                ['id' => 3, 'number' => 1, 'discussion_id' => 3, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>İlk post</p></t>'],
            ],
            'group_permission' => [
                ['group_id' => 4, 'permission' => 'discussion.sticky'],
                ['group_id' => 4, 'permission' => 'discussion.stickiest'],
                ['group_id' => 4, 'permission' => 'discussion.stickiest.tagSticky'],
            ],
            'group_user' => [
                ['group_id' => 4, 'user_id' => 3], // moderator grubu
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // isStickiest attribute testleri
    // -------------------------------------------------------------------------

    /** @test */
    public function api_returns_is_stickiest_attribute_on_discussion(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/1')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('isStickiest', $body['data']['attributes']);
        $this->assertFalse($body['data']['attributes']['isStickiest']);
    }

    /** @test */
    public function api_returns_is_tag_sticky_attribute_on_discussion(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/1')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('isTagSticky', $body['data']['attributes']);
        $this->assertFalse($body['data']['attributes']['isTagSticky']);
    }

    // -------------------------------------------------------------------------
    // Super sticky (isStickiest) testleri
    // -------------------------------------------------------------------------

    /** @test */
    public function moderator_can_make_discussion_super_sticky(): void
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
    public function moderator_can_remove_super_sticky_from_discussion(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/3', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'type'       => 'discussions',
                        'id'         => '3',
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
    public function normal_user_cannot_make_discussion_super_sticky(): void
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
    public function guest_cannot_make_discussion_super_sticky(): void
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

    // -------------------------------------------------------------------------
    // canStickiest / canTagSticky attribute testleri
    // -------------------------------------------------------------------------

    /** @test */
    public function moderator_sees_can_stickiest_as_true(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['data']['attributes']['canStickiest']);
    }

    /** @test */
    public function normal_user_sees_can_stickiest_as_false(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['data']['attributes']['canStickiest']);
    }

    /** @test */
    public function moderator_sees_can_tag_sticky_as_true(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['data']['attributes']['canTagSticky']);
    }

    // -------------------------------------------------------------------------
    // Tartışma listesi sıralaması testleri
    // -------------------------------------------------------------------------

    /** @test */
    public function super_sticky_discussions_appear_first_in_list(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $ids = array_column($body['data'], 'id');

        // id=3 (is_stickiest=1) en başta olmalı
        $this->assertEquals('3', $ids[0], 'Super sticky tartışma listenin başında görünmüyor.');
    }
}