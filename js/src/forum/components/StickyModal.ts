import app from 'flarum/forum/app';
import FormModal, { IFormModalAttrs } from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import Switch from 'flarum/common/components/Switch';
import Stream from 'flarum/common/utils/Stream';
import type Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';

interface StickyModalAttrs extends IFormModalAttrs {
  discussion: Discussion;
}

export default class StickyModal extends FormModal<StickyModalAttrs> {
  isSticky!: Stream<boolean>;
  isStickiest!: Stream<boolean>;
  isTagSticky!: Stream<boolean>;
  selectedTagIds!: Stream<number[]>;

  oninit(vnode: Mithril.Vnode<StickyModalAttrs>) {
    super.oninit(vnode);

    const discussion = this.attrs.discussion;

    // canSticky() flarum/sticky yoksa undefined döner
    this.isSticky = Stream(discussion.isSticky?.() || false);
    this.isStickiest = Stream(discussion.isStickiest() || false);
    this.isTagSticky = Stream(discussion.isTagSticky() || false);

    const stickyTagIds = discussion.attribute<number[]>('stickyTagIds') || [];
    this.selectedTagIds = Stream(stickyTagIds);
  }

  className(): string {
    return 'StickyModal Modal--small';
  }

  title(): Mithril.Children {
    const discussionTitle = this.attrs.discussion.title();
    return app.translator.trans('huseyinfiliz-stickiest.forum.sticky_modal.title', {
      title: m('em', {}, discussionTitle),
    });
  }

  content(): Mithril.Children {
    const discussion = this.attrs.discussion;
    const discussionTags: any[] = (discussion as any).tags?.() || [];

    // flarum/sticky yüklü mü kontrol et
    const hasStickyExtension = typeof discussion.canSticky === 'function';

    const items: Mithril.Children[] = [];

    // Normal Sticky - sadece flarum/sticky yüklüyse göster
    if (hasStickyExtension && discussion.canSticky?.()) {
      items.push(
        m('.Form-group', {}, [
          m(
            Switch,
            {
              state: this.isSticky(),
              onchange: (value: boolean) => {
                this.isSticky(value);
                if (value) {
                  this.isStickiest(false);
                  this.isTagSticky(false);
                }
              },
            },
            app.translator.trans('huseyinfiliz-stickiest.forum.sticky_modal.sticky_label')
          ),
          m('p.helpText', {}, app.translator.trans('huseyinfiliz-stickiest.forum.sticky_modal.sticky_help')),
        ])
      );
    }

    // Super Sticky
    if (discussion.canStickiest()) {
      items.push(
        m('.Form-group', {}, [
          m(
            Switch,
            {
              state: this.isStickiest(),
              onchange: (value: boolean) => {
                this.isStickiest(value);
                if (value) {
                  this.isSticky(false);
                }
              },
            },
            app.translator.trans('huseyinfiliz-stickiest.forum.sticky_modal.stickiest_label')
          ),
          m('p.helpText', {}, app.translator.trans('huseyinfiliz-stickiest.forum.sticky_modal.stickiest_help')),
        ])
      );
    }

    // Tag Sticky
    if (discussion.canTagSticky()) {
      items.push(
        m('.Form-group', {}, [
          m(
            Switch,
            {
              state: this.isTagSticky(),
              onchange: (value: boolean) => {
                this.isTagSticky(value);
                if (value) {
                  this.isSticky(false);
                }
              },
            },
            app.translator.trans('huseyinfiliz-stickiest.forum.sticky_modal.tag_sticky_label')
          ),
          m('p.helpText', {}, app.translator.trans('huseyinfiliz-stickiest.forum.sticky_modal.tag_sticky_help')),
        ])
      );
    }

    // Tag Seçimi
    if (discussion.canTagSticky() && this.isTagSticky() && discussionTags.length > 0) {
      const tagCheckboxes = discussionTags.map((tag: any) => {
        const tagId = Number(tag.id());
        return m('label.checkbox', {}, [
          m('input[type=checkbox]', {
            checked: this.selectedTagIds().includes(tagId),
            onchange: (e: Event) => {
              const target = e.target as HTMLInputElement;
              let ids = [...this.selectedTagIds()];

              if (target.checked) {
                if (!ids.includes(tagId)) {
                  ids.push(tagId);
                }
              } else {
                ids = ids.filter((id: number) => id !== tagId);
              }

              this.selectedTagIds(ids);
            },
          }),
          m('span.TagLabel', { style: { backgroundColor: tag.color() || '#888' } }, tag.name()),
        ]);
      });

      items.push(
        m('.Form-group', {}, [
          m('label', {}, app.translator.trans('huseyinfiliz-stickiest.forum.sticky_modal.select_tags')),
          m('.StickyModal-tags', {}, tagCheckboxes),
        ])
      );
    }

    // Submit Button
    items.push(
      m(
        '.Form-group',
        {},
        m(
          Button,
          {
            type: 'submit',
            className: 'Button Button--primary',
            loading: this.loading,
          },
          app.translator.trans('huseyinfiliz-stickiest.forum.sticky_modal.submit_button')
        )
      )
    );

    return m('.Modal-body', {}, m('.Form', {}, items));
  }

  onsubmit(e: SubmitEvent): void {
    e.preventDefault();

    this.loading = true;

    const discussion = this.attrs.discussion;
    const hasStickyExtension = typeof discussion.canSticky === 'function';

    const data: Record<string, any> = {
      isStickiest: this.isStickiest(),
      isTagSticky: this.isTagSticky(),
      stickyTagIds: this.isTagSticky() ? this.selectedTagIds() : [],
    };

    // Sadece flarum/sticky yüklüyse isSticky gönder
    if (hasStickyExtension) {
      data.isSticky = this.isSticky() && !this.isStickiest() && !this.isTagSticky();
    }

    this.attrs.discussion
      .save(data)
      .then(() => {
        this.hide();
        m.redraw();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }
}