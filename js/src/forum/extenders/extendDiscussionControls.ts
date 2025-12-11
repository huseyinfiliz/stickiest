import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import Button from 'flarum/common/components/Button';
import ItemList from 'flarum/common/utils/ItemList';
import Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';
import StickyModal from '../components/StickyModal';

export default function extendDiscussionControls() {
  extend(DiscussionControls, 'moderationControls', function (items: ItemList<Mithril.Children>, discussion: Discussion) {
    // Flarum sticky butonunu her zaman kaldır (bizimki onu kapsıyor)
    if (items.has('sticky')) {
      items.remove('sticky');
    }

    // canSticky flarum/sticky'den geliyor, yoksa undefined
    const canSticky = typeof discussion.canSticky === 'function' ? discussion.canSticky() : false;

    // Bizim sticky butonumuzu ekle
    if (canSticky || discussion.canStickiest() || discussion.canTagSticky()) {
      items.add(
        'stickiest',
        Button.component(
          {
            icon: 'fas fa-thumbtack',
            onclick: () => app.modal.show(StickyModal, { discussion }),
          },
          app.translator.trans('huseyinfiliz-stickiest.forum.discussion_controls.sticky_button')
        ),
        10
      );
    }
  });
}