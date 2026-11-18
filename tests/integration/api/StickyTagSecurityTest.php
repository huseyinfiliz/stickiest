<?php

namespace HuseyinFiliz\Stickiest\Tests\integration\api;

use Flarum\Discussion\Discussion;
use Flarum\User\User;
use HuseyinFiliz\Stickiest\Tests\integration\StickyTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class StickyTagSecurityTest extends StickyTestCase
{
    public function test_nested_pivot_attributes_cannot_override_the_authorized_discussion(): void
    {
        $this->app();
        $actor = User::findOrFail(2);
        $this->assertTrue($actor->can('tagSticky', Discussion::findOrFail(1)));
        $this->assertFalse($actor->can('tagSticky', Discussion::findOrFail(2)));
        $this->assertSame(403, $this->updateSticky(2, ['stickyTagIds' => [2]])->getStatusCode());

        $this->database()->table('discussion_sticky_tag')->insert(['discussion_id' => 1, 'tag_id' => 1]);
        $response = $this->updateSticky(1, ['stickyTagIds' => [['discussion_id' => 2, 'tag_id' => 2]]]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([1], $this->stickyTagIds(1));
        $this->assertSame([], $this->stickyTagIds(2));
    }

    public static function invalidTagIds(): array
    {
        return [
            'associative pivot attributes' => [[1 => ['discussion_id' => 2, 'tag_id' => 2]]],
            'associative scalar map' => [['tag_id' => 1]],
            'nested list' => [[[1]]],
            'numeric string' => [['1']],
            'boolean' => [[true]],
            'float' => [[1.5]],
            'zero' => [[0]],
            'negative' => [[-1]],
            'duplicate' => [[1, 1]],
            'unrelated existing tag' => [[3]],
            'nonexistent tag' => [[999999]],
            'null' => [null],
            'scalar integer' => [1],
        ];
    }

    #[DataProvider('invalidTagIds')]
    public function test_invalid_tag_ids_are_rejected_without_changing_state(mixed $tagIds): void
    {
        $response = $this->updateSticky(1, ['isTagSticky' => true, 'stickyTagIds' => $tagIds]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse((bool) $this->database()->table('discussions')->where('id', 1)->value('is_tag_sticky'));
        $this->assertSame([], $this->stickyTagIds(1));
    }

    public function test_valid_updates_refresh_the_response_and_do_not_leak_callbacks(): void
    {
        $response = $this->updateSticky(1, ['isTagSticky' => true, 'stickyTagIds' => [1]]);
        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals([1], $body['data']['attributes']['stickyTagIds']);

        $other = Discussion::findOrFail(3);
        $other->title = 'An unrelated update';
        $other->save();
        $this->assertSame([], $this->stickyTagIds(3));

        $this->assertSame(200, $this->updateSticky(1, ['isTagSticky' => false, 'stickyTagIds' => []])->getStatusCode());
        $this->assertSame([], $this->stickyTagIds(1));
        $other->save();
        $this->assertSame([], $this->stickyTagIds(3));
    }
}
