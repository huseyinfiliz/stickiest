<?php

/*
 * This file is part of Stickiest.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $connection = $schema->getConnection();

        $hasIndex1 = count($connection->select(
            "SHOW INDEX FROM `discussions` WHERE `Key_name` = 'discussions_is_tag_sticky_last_posted_at_index'"
        )) > 0;

        $hasIndex2 = count($connection->select(
            "SHOW INDEX FROM `discussions` WHERE `Key_name` = 'discussions_is_tag_sticky_created_at_index'"
        )) > 0;

        $schema->table('discussions', function (Blueprint $table) use ($hasIndex1, $hasIndex2) {
            if (!$hasIndex1) {
                $table->index(['is_tag_sticky', 'last_posted_at']);
            }
            if (!$hasIndex2) {
                $table->index(['is_tag_sticky', 'created_at']);
            }
        });
    },

    'down' => function (Builder $schema) {
        $connection = $schema->getConnection();

        $hasIndex1 = count($connection->select(
            "SHOW INDEX FROM `discussions` WHERE `Key_name` = 'discussions_is_tag_sticky_last_posted_at_index'"
        )) > 0;

        $hasIndex2 = count($connection->select(
            "SHOW INDEX FROM `discussions` WHERE `Key_name` = 'discussions_is_tag_sticky_created_at_index'"
        )) > 0;

        $schema->table('discussions', function (Blueprint $table) use ($hasIndex1, $hasIndex2) {
            if ($hasIndex1) {
                $table->dropIndex(['is_tag_sticky', 'last_posted_at']);
            }
            if ($hasIndex2) {
                $table->dropIndex(['is_tag_sticky', 'created_at']);
            }
        });
    },
];