<?php

namespace HuseyinFiliz\Stickiest\Tests\integration\api;

use Flarum\Audit\AuditLog;
use Flarum\Audit\AuditLogger;
use HuseyinFiliz\Stickiest\Tests\integration\StickyTestCase;

class StickyAuditTest extends StickyTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->extension('flarum-audit', 'flarum-sticky');
    }

    public function test_super_sticky_changes_are_attributed_and_not_duplicated(): void
    {
        $this->assertSame(200, $this->updateSticky(1, ['isStickiest' => true])->getStatusCode());
        $this->assertSame(200, $this->updateSticky(1, ['isStickiest' => true])->getStatusCode());
        $this->assertSame(200, $this->updateSticky(1, ['isStickiest' => false])->getStatusCode());

        $logs = AuditLog::whereIn('action', ['discussion.super_stickied', 'discussion.super_unstickied'])->orderBy('id')->get();
        $this->assertSame(['discussion.super_stickied', 'discussion.super_unstickied'], $logs->pluck('action')->all());
        $this->assertSame([2, 2], $logs->pluck('actor_id')->all());
        $this->assertSame(['discussion_id' => 1], $logs[0]->payload);
        $this->assertContains('discussion.super_stickied', AuditLogger::$registeredActions['huseyinfiliz-stickiest']);
    }

    public function test_tag_sticky_logs_include_persisted_old_and_new_tag_ids(): void
    {
        $this->assertSame(200, $this->updateSticky(1, ['isTagSticky' => true, 'stickyTagIds' => [1]])->getStatusCode());
        $this->assertSame(200, $this->updateSticky(1, ['isTagSticky' => true, 'stickyTagIds' => [1]])->getStatusCode());
        $this->assertSame(200, $this->updateSticky(1, ['stickyTagIds' => []])->getStatusCode());
        $this->assertSame(200, $this->updateSticky(1, ['isTagSticky' => false])->getStatusCode());

        $logs = AuditLog::where('action', 'like', 'discussion.tag_%')->orderBy('id')->get();
        $this->assertSame(['discussion.tag_stickied', 'discussion.tag_sticky_tags_changed', 'discussion.tag_unstickied'], $logs->pluck('action')->all());
        foreach ([0 => [[], [1]], 1 => [[1], []]] as $index => [$oldTagIds, $newTagIds]) {
            $this->assertSame(1, $logs[$index]->payload['discussion_id']);
            $this->assertSame($oldTagIds, $logs[$index]->payload['old_tag_ids']);
            $this->assertSame($newTagIds, $logs[$index]->payload['new_tag_ids']);
        }
        $this->assertSame([2, 2, 2], $logs->pluck('actor_id')->all());
    }

    public function test_rejected_updates_do_not_create_successful_audit_records(): void
    {
        $this->assertSame(403, $this->updateSticky(2, ['stickyTagIds' => [2]])->getStatusCode());
        $this->assertSame(422, $this->updateSticky(1, ['isTagSticky' => true, 'stickyTagIds' => [['discussion_id' => 2, 'tag_id' => 2]]])->getStatusCode());
        $this->assertSame(0, AuditLog::where('action', 'like', 'discussion.tag_%')->count());
    }

    public function test_normal_sticky_is_logged_once_by_core_sticky(): void
    {
        $this->assertSame(200, $this->updateSticky(1, ['isSticky' => true], 1)->getStatusCode());
        $this->assertSame(1, AuditLog::where('action', 'discussion.stickied')->count());
        $this->assertSame(0, AuditLog::where('action', 'discussion.super_stickied')->count());
    }
}
