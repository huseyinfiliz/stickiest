<?php

/*
 * This file is part of Stickiest.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/**
 * v1.0.1 → v1.0.2 düzeltme migration'ı.
 *
 * Sorun: 2022_07_21_000000 migration'ı is_tag_sticky sütununa index eklemeye
 * çalışıyordu, ancak bu sütun 2022_07_21_000002'de oluşturuluyordu (sıra hatası).
 * Bu durum yeni kurulumlarda PDOException 1072 (Key column doesn't exist) hatasına,
 * Purge & Re-enable sonrasında ise 1061 (Duplicate key name) hatasına yol açıyordu.
 *
 * Bu migration tüm senaryoları idempotent biçimde ele alır:
 * - Eski camelCase (is_tagSticky) indexleri varsa siler
 * - is_tag_sticky sütunu yoksa ekler
 * - Yeni snake_case (is_tag_sticky) indexleri yoksa ekler
 */
return [
    'up' => function (Builder $schema) {
        $connection = $schema->getConnection();
        $prefix = $connection->getTablePrefix();

        // Adım 1: Eski camelCase indexleri varsa sil
        $hasOldIndex1 = count($connection->select(
            "SHOW INDEX FROM `{$prefix}discussions` WHERE `Key_name` = '{$prefix}discussions_is_tagsticky_last_posted_at_index'"
        )) > 0;

        $hasOldIndex2 = count($connection->select(
            "SHOW INDEX FROM `{$prefix}discussions` WHERE `Key_name` = '{$prefix}discussions_is_tagsticky_created_at_index'"
        )) > 0;

        if ($hasOldIndex1 || $hasOldIndex2) {
            $schema->table('discussions', function (Blueprint $table) use ($hasOldIndex1, $hasOldIndex2) {
                if ($hasOldIndex1) {
                    $table->dropIndex(['is_tagSticky', 'last_posted_at']);
                }
                if ($hasOldIndex2) {
                    $table->dropIndex(['is_tagSticky', 'created_at']);
                }
            });
        }

        // Adım 2: is_tag_sticky sütunu yoksa ekle
        if (!$schema->hasColumn('discussions', 'is_tag_sticky')) {
            $schema->table('discussions', function (Blueprint $table) {
                $table->boolean('is_tag_sticky')->default(0);
            });
        }

        // Adım 3: Yeni snake_case indexleri yoksa ekle
        $hasNewIndex1 = count($connection->select(
            "SHOW INDEX FROM `{$prefix}discussions` WHERE `Key_name` = '{$prefix}discussions_is_tag_sticky_last_posted_at_index'"
        )) > 0;

        $hasNewIndex2 = count($connection->select(
            "SHOW INDEX FROM `{$prefix}discussions` WHERE `Key_name` = '{$prefix}discussions_is_tag_sticky_created_at_index'"
        )) > 0;

        $schema->table('discussions', function (Blueprint $table) use ($hasNewIndex1, $hasNewIndex2) {
            if (!$hasNewIndex1) {
                $table->index(['is_tag_sticky', 'last_posted_at']);
            }
            if (!$hasNewIndex2) {
                $table->index(['is_tag_sticky', 'created_at']);
            }
        });
    },

    'down' => function (Builder $schema) {
        $connection = $schema->getConnection();
        $prefix = $connection->getTablePrefix();

        $hasIndex1 = count($connection->select(
            "SHOW INDEX FROM `{$prefix}discussions` WHERE `Key_name` = '{$prefix}discussions_is_tag_sticky_last_posted_at_index'"
        )) > 0;

        $hasIndex2 = count($connection->select(
            "SHOW INDEX FROM `{$prefix}discussions` WHERE `Key_name` = '{$prefix}discussions_is_tag_sticky_created_at_index'"
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