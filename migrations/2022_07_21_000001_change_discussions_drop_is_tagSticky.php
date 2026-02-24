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
            if ($schema->hasColumn('discussions', 'is_tagSticky')) {
                $table->dropColumn('is_tagSticky');
            }
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('discussions', function (Blueprint $table) use ($schema) {
            if (!$schema->hasColumn('discussions', 'is_tagSticky')) {
                $table->boolean('is_tagSticky')->default(0);
            }
        });
    },
];
