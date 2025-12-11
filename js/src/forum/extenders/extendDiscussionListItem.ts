import { extend } from 'flarum/common/extend';
import DiscussionListItem from 'flarum/forum/components/DiscussionListItem';
import classList from 'flarum/common/utils/classList';

export default function extendDiscussionListItem() {
  extend(DiscussionListItem.prototype, 'elementAttrs', function (attrs) {
    const discussion = this.attrs.discussion;
    
    // isSticky flarum/sticky'den geliyor, yoksa false kabul et
    const isSticky = typeof discussion.isSticky === 'function' ? discussion.isSticky() : false;

    attrs.className = classList(attrs.className, {
      'Stickiest-superSticky': discussion.isStickiest(),
      'Stickiest-tagSticky': discussion.isTagSticky() && !discussion.isStickiest(),
      'Stickiest-sticky': isSticky && !discussion.isStickiest() && !discussion.isTagSticky(),
    });
  });
}