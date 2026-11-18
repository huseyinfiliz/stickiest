<?php

namespace HuseyinFiliz\Stickiest\Tests\integration\api;

use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CoreStickyCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->extension('flarum-tags', 'flarum-sticky', 'huseyinfiliz-stickiest');
        $discussions = $posts = $tags = [];

        for ($id = 1; $id <= 30; $id++) {
            $date = sprintf('2026-01-%02d 00:00:00', $id);
            $discussions[] = [
                'id' => $id, 'title' => 'Discussion '.$id, 'slug' => 'discussion-'.$id,
                'created_at' => $date, 'last_posted_at' => $date, 'user_id' => 1,
                'first_post_id' => $id, 'last_post_id' => $id, 'last_post_number' => 1,
                'comment_count' => 1, 'is_stickiest' => $id === 1,
                'is_sticky' => in_array($id, [2, 3]), 'is_tag_sticky' => $id === 3,
            ];
            $posts[] = ['id' => $id, 'discussion_id' => $id, 'number' => 1, 'created_at' => $date,
                'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Test content.</p></t>'];
            $tags[] = ['discussion_id' => $id, 'tag_id' => 1];
        }

        $this->prepareDatabase([
            'tags' => [['id' => 1, 'name' => 'General', 'slug' => 'general', 'is_restricted' => 0, 'default_sort' => 'newest']],
            'discussions' => $discussions, 'posts' => $posts, 'discussion_tag' => $tags,
            'discussion_sticky_tag' => [['discussion_id' => 3, 'tag_id' => 1]],
        ]);
    }

    private function ids(array $params = []): array
    {
        $response = $this->send($this->request('GET', '/api/discussions')->withQueryParams($params));
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());

        return array_map('intval', array_column(json_decode((string) $response->getBody(), true)['data'], 'id'));
    }

    public static function pinningModes(): array
    {
        return ['unread only' => [true, true], 'all stickies' => [true, false], 'normal pinning off' => [false, true]];
    }

    #[DataProvider('pinningModes')]
    public function test_super_stickies_stay_first_across_core_modes_and_pages(bool $pin, bool $unread): void
    {
        $this->setting('flarum-sticky.pin_sticky_on_all_discussions', $pin);
        $this->setting('flarum-sticky.only_sticky_unread_discussions', $unread);
        $pages = [];

        foreach ([0, 10, 20] as $offset) {
            $pages[] = $this->ids(['page' => ['limit' => '10', 'offset' => (string) $offset]]);
        }

        $this->assertSame(1, $pages[0][0]);
        $all = array_merge(...$pages);
        $this->assertCount(29, $all, json_encode($pages));
        $this->assertCount(29, array_unique($all), json_encode($pages));
        $this->assertNotContains(3, $all, 'The union must not reintroduce excluded tag stickies.');
        $this->assertContains(30, $all);
    }

    public function test_a_tags_explicit_default_sort_preserves_the_sticky_hierarchy(): void
    {
        $ids = $this->ids(['filter' => ['tag' => 'general'], 'sort' => '-createdAt']);
        $this->assertSame([1, 3, 2], array_slice($ids, 0, 3));
    }

    public function test_a_user_selected_nondefault_sort_is_not_overridden(): void
    {
        $ids = $this->ids(['filter' => ['tag' => 'general'], 'sort' => 'createdAt']);
        $this->assertSame([1, 2, 3, 4], array_slice($ids, 0, 4));
    }
}
