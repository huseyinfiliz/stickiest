<?php

namespace HuseyinFiliz\Stickiest\Search;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\SearchCriteria;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\TagRepository;
use Illuminate\Support\Arr;

class StickySearchMutator
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected TagRepository $tags
    ) {}

    public function __invoke(DatabaseSearchState $state, SearchCriteria $criteria): void
    {
        // Sadece varsayılan sıralamada çalış
        if (!$criteria->sortIsDefault) {
            return;
        }

        $query = $state->getQuery();
        $baseQuery = $query->getQuery();
        
        // Tag filtresi var mı kontrol et
        // Flarum 2.x beta8+ filter değerleri array olarak geliyor
        $tagSlug = Arr::get($criteria->filters, 'tag');
        if (is_array($tagSlug)) {
            $tagSlug = Arr::first($tagSlug) ?: null;
        }
        
        if ($tagSlug) {
            // Tag sayfasındayız - tag sticky'leri de üste al
            $tagId = $this->tags->getIdForSlug((string) $tagSlug);
            
            if ($tagId) {
                // Bu tag için sticky olanları üste çıkar
                $query->leftJoin('discussion_sticky_tag as dst', function ($join) use ($tagId) {
                    $join->on('discussions.id', '=', 'dst.discussion_id')
                         ->where('dst.tag_id', '=', $tagId);
                });
                
                $orders = $baseQuery->orders ?? [];
                
                array_unshift($orders, 
                    ['column' => 'is_stickiest', 'direction' => 'desc'],
                    ['column' => 'dst.tag_id', 'direction' => 'desc'],
                    ['column' => 'is_sticky', 'direction' => 'desc']
                );
                
                $baseQuery->orders = $orders;
            }
        } else {
            // All Discussions
            $showTagStickyInAll = (bool) $this->settings->get('huseyinfiliz-stickiest.show_tag_sticky_in_all', false);
            
            // Tag sticky'leri gizle (eğer ayar kapalıysa ve super sticky değilse)
            if (!$showTagStickyInAll) {
                $query->where(function ($q) {
                    $q->where('is_tag_sticky', false)
                      ->orWhere('is_stickiest', true);
                });
            }
            
            // Super stickiest üstte
            $orders = $baseQuery->orders ?? [];
            
            array_unshift($orders, [
                'column' => 'is_stickiest',
                'direction' => 'desc'
            ]);
            
            $baseQuery->orders = $orders;
        }
    }
}