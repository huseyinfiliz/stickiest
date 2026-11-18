<?php

namespace HuseyinFiliz\Stickiest\Tests\integration;

use Flarum\Testing\integration\TestCase;

abstract class StickyTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'huseyinfiliz-stickiest');
        $discussions = $posts = $discussionTags = [];

        foreach ([1 => 1, 2 => 2, 3 => 1] as $id => $tagId) {
            $discussions[] = [
                'id' => $id, 'title' => 'Discussion '.$id, 'slug' => 'discussion-'.$id,
                'created_at' => '2026-01-01 00:00:00', 'last_posted_at' => '2026-01-01 00:00:00',
                'user_id' => 1, 'first_post_id' => $id, 'last_post_id' => $id,
                'comment_count' => 1, 'last_post_number' => 1,
                'is_stickiest' => false, 'is_tag_sticky' => false,
            ];
            $posts[] = [
                'id' => $id, 'number' => 1, 'discussion_id' => $id,
                'created_at' => '2026-01-01 00:00:00', 'user_id' => 1,
                'type' => 'comment', 'content' => '<t><p>Test discussion.</p></t>',
            ];
            $discussionTags[] = ['discussion_id' => $id, 'tag_id' => $tagId];
        }

        $this->prepareDatabase([
            'users' => [[
                'id' => 2, 'username' => 'delegate', 'email' => 'delegate@example.com',
                'password' => password_hash('password', PASSWORD_BCRYPT), 'is_email_confirmed' => 1,
            ]],
            'tags' => [
                ['id' => 1, 'name' => 'Allowed', 'slug' => 'allowed', 'is_restricted' => 1],
                ['id' => 2, 'name' => 'Denied', 'slug' => 'denied', 'is_restricted' => 1],
                ['id' => 3, 'name' => 'Unrelated', 'slug' => 'unrelated', 'is_restricted' => 0],
            ],
            'discussions' => $discussions,
            'posts' => $posts,
            'discussion_tag' => $discussionTags,
            'group_user' => [['user_id' => 2, 'group_id' => 4]],
            'group_permission' => [
                ['group_id' => 4, 'permission' => 'tag1.viewForum'],
                ['group_id' => 4, 'permission' => 'tag2.viewForum'],
                ['group_id' => 4, 'permission' => 'tag1.discussion.tagSticky'],
                ['group_id' => 4, 'permission' => 'tag1.discussion.stickiest'],
            ],
        ]);
    }

    protected function updateSticky(int $id, array $attributes, int $actor = 2)
    {
        return $this->send($this->request('PATCH', '/api/discussions/'.$id, [
            'authenticatedAs' => $actor,
            'json' => ['data' => ['type' => 'discussions', 'id' => (string) $id, 'attributes' => $attributes]],
        ]));
    }

    protected function stickyTagIds(int $discussionId): array
    {
        return $this->database()->table('discussion_sticky_tag')->where('discussion_id', $discussionId)
            ->orderBy('tag_id')->pluck('tag_id')->map(fn ($id) => (int) $id)->all();
    }
}
