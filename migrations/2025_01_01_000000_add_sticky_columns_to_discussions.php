<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        if (!$schema->hasColumn('discussions', 'is_stickiest')) {
            $schema->table('discussions', function (Blueprint $table) {
                $table->boolean('is_stickiest')->default(false)->after('is_sticky');
                $table->boolean('is_tag_sticky')->default(false)->after('is_stickiest');
            });
        }
    },

    'down' => function (Builder $schema) {
        $schema->table('discussions', function (Blueprint $table) {
            $table->dropColumn(['is_stickiest', 'is_tag_sticky']);
        });
    },
];
