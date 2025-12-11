import app from 'flarum/forum/app';
import Discussion from 'flarum/common/models/Discussion';
import Model from 'flarum/common/Model';

import DiscussionStickiestPost from './components/DiscussionStickiestPost';
import extendDiscussionBadges from './extenders/extendDiscussionBadges';
import extendDiscussionControls from './extenders/extendDiscussionControls';
import extendDiscussionListItem from './extenders/extendDiscussionListItem';

export { default as extend } from './extend';

// Negatif öncelik = daha sonra çalışır (flarum-sticky'den sonra)
app.initializers.add(
  'huseyinfiliz-stickiest',
  () => {
    // Post component kaydet
    app.postComponents.discussionStickiest = DiscussionStickiestPost;

    // Model attributes
    Discussion.prototype.isStickiest = Model.attribute('isStickiest');
    Discussion.prototype.isTagSticky = Model.attribute('isTagSticky');
    Discussion.prototype.canStickiest = Model.attribute('canStickiest');
    Discussion.prototype.canTagSticky = Model.attribute('canTagSticky');
    Discussion.prototype.stickyTags = Model.hasMany('stickyTags') as () => any[];

    // Extend UI
    extendDiscussionBadges();
    extendDiscussionControls();
    extendDiscussionListItem();
  },
  -10
);