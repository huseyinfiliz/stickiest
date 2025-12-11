import app from 'flarum/forum/app';
import EventPost from 'flarum/forum/components/EventPost';

export default class DiscussionStickiestPost extends EventPost {
  icon() {
    return app.forum.attribute('huseyinfiliz-stickiest.stickiest_icon') || 'fas fa-star';
  }

  descriptionKey() {
    if (this.attrs.post.content().stickiest) {
      return 'huseyinfiliz-stickiest.forum.post_stream.discussion_stickiest_text';
    } else {
      return 'huseyinfiliz-stickiest.forum.post_stream.discussion_unstickiest_text';
    }
  }
}