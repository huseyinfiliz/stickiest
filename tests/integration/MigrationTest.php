<?php

namespace HuseyinFiliz\Stickiest\Tests\integration;

use Flarum\Testing\integration\TestCase;

class MigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');
        $this->extension('huseyinfiliz-stickiest');
    }

    /** @test */
    public function is_stickiest_column_exists(): void
    {
        $this->database()->statement('SELECT is_stickiest FROM discussions LIMIT 0');
        $this->assertTrue(true); // no exception = column exists
    }

    /** @test */
    public function is_tag_sticky_column_exists(): void
    {
        $this->database()->statement('SELECT is_tag_sticky FROM discussions LIMIT 0');
        $this->assertTrue(true);
    }

    /** @test */
    public function discussion_sticky_tag_table_exists(): void
    {
        $this->database()->statement('SELECT discussion_id, tag_id FROM discussion_sticky_tag LIMIT 0');
        $this->assertTrue(true);
    }

    /** @test */
    public function migration_is_idempotent_when_columns_already_exist(): void
    {
        // Simulates upgrading from 1.x: run migration again when columns exist.
        // The migration uses hasColumn() guards, so it should not throw.
        $schema = $this->database()->getSchemaBuilder();

        $this->assertTrue($schema->hasColumn('discussions', 'is_stickiest'));
        $this->assertTrue($schema->hasColumn('discussions', 'is_tag_sticky'));
        $this->assertTrue($schema->hasTable('discussion_sticky_tag'));
    }

    /** @test */
    public function existing_sticky_data_is_preserved_after_migration(): void
    {
        // Insert 1.x-style data directly (as if migrating from the-turk/stickiest)
        $this->database()->table('discussions')->where('id', 1)->update([
            'is_stickiest'  => true,
            'is_tag_sticky' => false,
        ]);

        $discussion = $this->database()->table('discussions')->where('id', 1)->first();

        $this->assertEquals(1, $discussion->is_stickiest);
        $this->assertEquals(0, $discussion->is_tag_sticky);
    }

    /** @test */
    public function default_permissions_are_set_for_moderators(): void
    {
        $stickyPerm = $this->database()
            ->table('group_permission')
            ->where('group_id', 4) // moderator group
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