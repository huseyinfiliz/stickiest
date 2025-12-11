import app from 'flarum/forum/app';
import EventPost from 'flarum/forum/components/EventPost';

export default class DiscussionStickiestPost extends EventPost {
  icon(): string {
    return app.forum.attribute<string>('huseyinfiliz-stickiest.stickiest_icon') || 'fas fa-star';
  }

  descriptionKey(): string {
    const content = this.attrs.post.content() as Record<string, boolean> | null;

    if (content?.stickiest) {
      return 'huseyinfiliz-stickiest.forum.post_stream.discussion_stickiest_text';
    } else {
      return 'huseyinfiliz-stickiest.forum.post_stream.discussion_unstickiest_text';
    }
  }
}
