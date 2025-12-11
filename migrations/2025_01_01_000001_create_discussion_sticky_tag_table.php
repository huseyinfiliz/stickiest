<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        if (!$schema->hasTable('discussion_sticky_tag')) {
            $schema->create('discussion_sticky_tag', function (Blueprint $table) {
                $table->unsignedInteger('discussion_id');
                $table->unsignedInteger('tag_id');
                $table->primary(['discussion_id', 'tag_id']);
                
                $table->foreign('discussion_id')
                    ->references('id')
                    ->on('discussions')
                    ->onDelete('cascade');
                    
                $table->foreign('tag_id')
                    ->references('id')
                    ->on('tags')
                    ->onDelete('cascade');
            });
        }
    },

    'down' => function (Builder $schema) {
        $schema->dropIfExists('discussion_sticky_tag');
    },
];
