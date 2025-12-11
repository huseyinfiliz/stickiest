<?php

namespace HuseyinFiliz\Stickiest\Search;

use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\SearchCriteria;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\TagRepository;

class PinStickyToTop
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected TagRepository $tags
    ) {}

    public function __invoke(DiscussionSearcher $searcher, DatabaseSearchState $state, SearchCriteria $criteria): void
    {
        if (!$criteria->sortIsDefault) {
            return;
        }

        $query = $state->getQuery();

        // Super sticky her zaman en üstte
        $query->orderByDesc('is_stickiest');

        // Tag sayfasındaysak tag sticky'leri de üste al
        $tagSlug = $criteria->query['tag'] ?? null;
        
        if ($tagSlug) {
            $query->orderByDesc('is_tag_sticky');
            $query->orderByDesc('is_sticky');
        } else {
            // All Discussions'da tag sticky'leri gösterme ayarı
            $showTagSticky = (bool) $this->settings->get('huseyinfiliz-stickiest.show_tag_sticky_in_all', false);
            
            if (!$showTagSticky) {
                $query->where(function ($q) {
                    $q->where('is_tag_sticky', false)
                      ->orWhere('is_stickiest', true);
                });
            }
            
            $query->orderByDesc('is_sticky');
        }
    }
}
