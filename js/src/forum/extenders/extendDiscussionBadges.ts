import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Discussion from 'flarum/common/models/Discussion';
import Badge from 'flarum/common/components/Badge';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

export default function extendDiscussionBadges() {
  extend(Discussion.prototype, 'badges', function (badges: ItemList<Mithril.Children>) {
    // Super Sticky badge
    if (this.isStickiest()) {
      const icon = app.forum.attribute<string>('huseyinfiliz-stickiest.stickiest_icon') || 'fas fa-star';

      badges.add(
        'stickiest',
        Badge.component({
          type: 'stickiest',
          icon: icon,
          label: app.translator.trans('huseyinfiliz-stickiest.forum.badge.stickiest_tooltip'),
        }),
        15
      );
    }

    // Tag Sticky badge (only if not super sticky)
    if (this.isTagSticky() && !this.isStickiest()) {
      badges.add(
        'tagSticky',
        Badge.component({
          type: 'tagSticky',
          icon: 'fas fa-thumbtack',
          label: app.translator.trans('huseyinfiliz-stickiest.forum.badge.tag_sticky_tooltip'),
        }),
        12
      );
    }
  });
}