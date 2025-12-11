import app from 'flarum/admin/app';

export { default as extend } from './extend';

app.initializers.add('huseyinfiliz-stickiest', () => {
  app.registry
    .for('huseyinfiliz-stickiest')
    .registerSetting({
      setting: 'huseyinfiliz-stickiest.show_tag_sticky_in_all',
      label: app.translator.trans('huseyinfiliz-stickiest.admin.settings.show_tag_sticky_label'),
      help: app.translator.trans('huseyinfiliz-stickiest.admin.settings.show_tag_sticky_help'),
      type: 'boolean',
    })
    .registerSetting({
      setting: 'huseyinfiliz-stickiest.stickiest_icon',
      label: app.translator.trans('huseyinfiliz-stickiest.admin.settings.stickiest_icon_label'),
      help: app.translator.trans('huseyinfiliz-stickiest.admin.settings.stickiest_icon_help'),
      type: 'text',
      placeholder: 'fas fa-star',
    })
    .registerPermission(
      {
        icon: 'fas fa-star',
        label: app.translator.trans('huseyinfiliz-stickiest.admin.permissions.stickiest_label'),
        permission: 'discussion.stickiest',
      },
      'moderate',
      95
    )
    .registerPermission(
      {
        icon: 'fas fa-thumbtack',
        label: app.translator.trans('huseyinfiliz-stickiest.admin.permissions.tag_sticky_label'),
        permission: 'discussion.tagSticky',
      },
      'moderate',
      94
    );
});