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
        $schema->table('discussions', function (Blueprint $table) use ($schema) {
            if (!$schema->hasColumn('discussions', 'is_tag_sticky')) {
                $table->boolean('is_tag_sticky')->default(0);
            }
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('discussions', function (Blueprint $table) use ($schema) {
            if ($schema->hasColumn('discussions', 'is_tag_sticky')) {
                $table->dropColumn('is_tag_sticky');
            }
        });
    },
];
