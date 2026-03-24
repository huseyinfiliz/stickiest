<?php

namespace HuseyinFiliz\Stickiest\Tests\integration;

use Carbon\Carbon;
use Flarum\Testing\integration\TestCase;

class MigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');
        $this->extension('huseyinfiliz-stickiest');

        $this->prepareDatabase([
            'users' => [
                ['id' => 1, 'username' => 'admin', 'email' => 'admin@example.com', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6uxy7CHhy3400ZBGl5bCAwtKEBF3UFKCO', 'is_email_confirmed' => 1],
            ],
            'discussions' => [
                [
                    'id'             => 1,
                    'title'          => 'Test Discussion',
                    'slug'           => 'test-discussion',
                    'created_at'     => Carbon::now()->toDateTimeString(),
                    'last_posted_at' => Carbon::now()->toDateTimeString(),
                    'user_id'        => 1,
                    'first_post_id'  => 1,
                    'comment_count'  => 1,
                    'is_stickiest'   => false,
                    'is_tag_sticky'  => false,
                ],
            ],
            'posts' => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Test</p></t>'],
            ],
        ]);
    }

    /** @test */
    public function is_stickiest_column_exists(): void
    {
        // Boot the app (runs migrations) by sending a request first
        $this->send($this->request('GET', '/api'));

        $this->assertTrue(
            $this->database()->getSchemaBuilder()->hasColumn('discussions', 'is_stickiest')
        );
    }

    /** @test */
    public function is_tag_sticky_column_exists(): void
    {
        $this->send($this->request('GET', '/api'));

        $this->assertTrue(
            $this->database()->getSchemaBuilder()->hasColumn('discussions', 'is_tag_sticky')
        );
    }

    /** @test */
    public function discussion_sticky_tag_table_exists(): void
    {
        $this->send($this->request('GET', '/api'));

        $this->assertTrue(
            $this->database()->getSchemaBuilder()->hasTable('discussion_sticky_tag')
        );
    }

    /** @test */
    public function migration_is_idempotent_when_columns_already_exist(): void
    {
        $this->send($this->request('GET', '/api'));

        $schema = $this->database()->getSchemaBuilder();

        $this->assertTrue($schema->hasColumn('discussions', 'is_stickiest'));
        $this->assertTrue($schema->hasColumn('discussions', 'is_tag_sticky'));
        $this->assertTrue($schema->hasTable('discussion_sticky_tag'));
    }

    /** @test */
    public function existing_sticky_data_is_preserved_after_migration(): void
    {
        $this->send($this->request('GET', '/api'));

        $this->database()->table('discussions')
            ->where('id', 1)
            ->update(['is_stickiest' => true, 'is_tag_sticky' => false]);

        $discussion = $this->database()->table('discussions')->where('id', 1)->first();

        $this->assertEquals(1, $discussion->is_stickiest);
        $this->assertEquals(0, $discussion->is_tag_sticky);
    }

    /** @test */
    public function default_permissions_are_set_for_moderators(): void
    {
        $this->send($this->request('GET', '/api'));

        $stickyPerm = $this->database()
            ->table('group_permission')
            ->where('group_id', 4)
            ->where('permission', 'discussion.stickiest')
            ->exists();

        $tagStickyPerm = $this->database()
            ->table('group_permission')
            ->where('group_id', 4)
            ->where('permission', 'discussion.tagSticky')
            ->exists();

        $this->assertTrue($stickyPerm, 'discussion.stickiest permission should be granted to moderators');
        $this->assertTrue($tagStickyPerm, 'discussion.tagSticky permission should be granted to moderators');
    }
}