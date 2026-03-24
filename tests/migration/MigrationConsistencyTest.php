<?php

/*
 * This file is part of Stickiest.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace HuseyinFiliz\Stickiest\Tests\integration\migration;

use Flarum\Testing\integration\TestCase;

/**
 * Migration tutarlılık testleri.
 *
 * Bu testler, uzantı kurulumu sonrasında veritabanı şemasının
 * doğru durumda olduğunu doğrular. Özellikle şu senaryoyu kapsar:
 *
 * - is_tag_sticky (snake_case) sütununun varlığı
 * - is_tagSticky (camelCase) sütununun KALMAMIŞ olması
 * - Doğru snake_case indexlerin varlığı
 * - Eski camelCase indexlerin KALMAMIŞ olması
 *
 * Bu testler v1.0.1'de raporlanan migration sıra hatasına karşı
 * regresyon koruması sağlar.
 *
 * @see https://github.com/huseyinfiliz/stickiest/issues/XXX
 */
class MigrationConsistencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');
        $this->extension('flarum-sticky');
        $this->extension('huseyinfiliz-stickiest');
    }

    private function getConnection(): \Illuminate\Database\Connection
    {
        return $this->app()->getContainer()->make('db')->connection();
    }

    private function getPrefix(): string
    {
        return $this->getConnection()->getTablePrefix();
    }

    private function indexExists(string $indexName): bool
    {
        $prefix = $this->getPrefix();
        $result = $this->getConnection()->select(
            "SHOW INDEX FROM `{$prefix}discussions` WHERE `Key_name` = ?",
            ["{$prefix}{$indexName}"]
        );

        return count($result) > 0;
    }

    // -------------------------------------------------------------------------
    // Sütun varlığı testleri
    // -------------------------------------------------------------------------

    /** @test */
    public function discussions_table_has_is_stickiest_column(): void
    {
        $schema = $this->app()->getContainer()->make('db')->connection()->getSchemaBuilder();

        $this->assertTrue(
            $schema->hasColumn('discussions', 'is_stickiest'),
            'discussions tablosunda is_stickiest sütunu bulunmuyor.'
        );
    }

    /** @test */
    public function discussions_table_has_snake_case_is_tag_sticky_column(): void
    {
        $schema = $this->app()->getContainer()->make('db')->connection()->getSchemaBuilder();

        $this->assertTrue(
            $schema->hasColumn('discussions', 'is_tag_sticky'),
            'discussions tablosunda is_tag_sticky (snake_case) sütunu bulunmuyor. '
            . 'Bu, 2026_03_24_000000 fix migration\'ının çalışmadığını gösterir.'
        );
    }

    /** @test */
    public function discussions_table_does_not_have_camel_case_is_tag_sticky_column(): void
    {
        $schema = $this->app()->getContainer()->make('db')->connection()->getSchemaBuilder();

        $this->assertFalse(
            $schema->hasColumn('discussions', 'is_tagSticky'),
            'discussions tablosunda is_tagSticky (camelCase) sütunu hâlâ mevcut. '
            . '2022_07_21_000001 migration\'ı bu sütunu kaldırmış olmalıydı.'
        );
    }

    // -------------------------------------------------------------------------
    // Doğru (snake_case) index varlığı testleri
    // -------------------------------------------------------------------------

    /** @test */
    public function discussions_table_has_is_stickiest_last_posted_at_index(): void
    {
        $this->assertTrue(
            $this->indexExists('discussions_is_stickiest_last_posted_at_index'),
            'discussions_is_stickiest_last_posted_at_index bulunamadı.'
        );
    }

    /** @test */
    public function discussions_table_has_is_stickiest_created_at_index(): void
    {
        $this->assertTrue(
            $this->indexExists('discussions_is_stickiest_created_at_index'),
            'discussions_is_stickiest_created_at_index bulunamadı.'
        );
    }

    /** @test */
    public function discussions_table_has_snake_case_is_tag_sticky_last_posted_at_index(): void
    {
        $this->assertTrue(
            $this->indexExists('discussions_is_tag_sticky_last_posted_at_index'),
            'discussions_is_tag_sticky_last_posted_at_index (snake_case) bulunamadı. '
            . 'Bu, 2026_03_24_000000 fix migration\'ının çalışmadığını gösterir.'
        );
    }

    /** @test */
    public function discussions_table_has_snake_case_is_tag_sticky_created_at_index(): void
    {
        $this->assertTrue(
            $this->indexExists('discussions_is_tag_sticky_created_at_index'),
            'discussions_is_tag_sticky_created_at_index (snake_case) bulunamadı. '
            . 'Bu, 2026_03_24_000000 fix migration\'ının çalışmadığını gösterir.'
        );
    }

    // -------------------------------------------------------------------------
    // Eski (camelCase) index regresyon testleri
    // -------------------------------------------------------------------------

    /** @test */
    public function discussions_table_does_not_have_camel_case_is_tagsticky_last_posted_at_index(): void
    {
        $this->assertFalse(
            $this->indexExists('discussions_is_tagsticky_last_posted_at_index'),
            'Eski camelCase index discussions_is_tagsticky_last_posted_at_index hâlâ mevcut. '
            . '2026_03_24_000000 fix migration\'ı bunu kaldırmış olmalıydı. '
            . 'Bu, v1.0.1\'de bildirilen "Duplicate key name" hatasının kaynağıdır.'
        );
    }

    /** @test */
    public function discussions_table_does_not_have_camel_case_is_tagsticky_created_at_index(): void
    {
        $this->assertFalse(
            $this->indexExists('discussions_is_tagsticky_created_at_index'),
            'Eski camelCase index discussions_is_tagsticky_created_at_index hâlâ mevcut. '
            . '2026_03_24_000000 fix migration\'ı bunu kaldırmış olmalıydı.'
        );
    }

    // -------------------------------------------------------------------------
    // Pivot tablo testleri
    // -------------------------------------------------------------------------

    /** @test */
    public function discussion_sticky_tag_pivot_table_exists(): void
    {
        $schema = $this->app()->getContainer()->make('db')->connection()->getSchemaBuilder();

        $this->assertTrue(
            $schema->hasTable('discussion_sticky_tag'),
            'discussion_sticky_tag pivot tablosu bulunamadı.'
        );
    }

    /** @test */
    public function discussion_sticky_tag_table_has_correct_columns(): void
    {
        $schema = $this->app()->getContainer()->make('db')->connection()->getSchemaBuilder();

        $this->assertTrue(
            $schema->hasColumn('discussion_sticky_tag', 'discussion_id'),
            'discussion_sticky_tag tablosunda discussion_id sütunu eksik.'
        );

        $this->assertTrue(
            $schema->hasColumn('discussion_sticky_tag', 'tag_id'),
            'discussion_sticky_tag tablosunda tag_id sütunu eksik.'
        );
    }
}