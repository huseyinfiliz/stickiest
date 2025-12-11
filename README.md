![Stickiest](https://i.ibb.co/VYxXWMKr/Stickiest.png)

# Stickiest

[![MIT license](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/huseyinfiliz/stickiest/blob/main/LICENSE.md) [![Latest Stable Version](https://img.shields.io/packagist/v/huseyinfiliz/stickiest)](https://packagist.org/packages/huseyinfiliz/stickiest) [![Total Downloads](https://img.shields.io/packagist/dt/huseyinfiliz/stickiest.svg)](https://packagist.org/packages/huseyinfiliz/stickiest)

This extension allows you to stick, super-stick, or tag-stick discussions to the top of the list. It is fully compatible with Flarum 2.x and is inspired by [the-turk/flarum-stickiest](https://github.com/the-turk/flarum-stickiest), but entirely rewritten from scratch.

## Features

- **Super Sticky**: Pin discussions to the very top everywhere (All Discussions + all tag pages)
- **Tag Sticky**: Pin discussions only in selected tag pages
- **Normal Sticky**: Full compatibility with `flarum/sticky` extension
- **Event Posts**: Shows activity when super sticky status changes
- **Customizable Badge**: Choose your own icon for super sticky discussions
- **Visual Indicators**: Colored borders and backgrounds for sticky discussions

## Screenshots

![Stickiest Modal](https://i.ibb.co/s9nr7BFw/image.png)

![Stickiest List](https://i.ibb.co/dJrVC9NN/image.png)


## Requirements

- Flarum 2.0+
- PHP 8.2+
- [flarum/tags](https://github.com/flarum/tags) extension

## Installation

```bash
composer require huseyinfiliz/stickiest:"*"
php flarum migrate
php flarum cache:clear
```

## Updating

```bash
composer update huseyinfiliz/stickiest:"*"
php flarum migrate
php flarum cache:clear
```

## Migrating from the-turk/flarum-stickiest

This extension uses the same database structure as `the-turk/flarum-stickiest`. To migrate:

1. Disable and uninstall the old extension
2. Install this extension
3. Your existing sticky data will be preserved

```bash
composer remove the-turk/flarum-stickiest
composer require huseyinfiliz/stickiest:"*"
php flarum migrate
php flarum cache:clear
```

## Usage

1. Enable the extension in admin panel
2. Set permissions for "Super sticky discussions" and "Tag sticky discussions"
3. Optionally customize the super sticky badge icon in settings
4. Click on the "Sticky" button on any discussion to open the sticky modal
5. Choose your sticky type:
   - **Sticky**: Standard sticky (requires `flarum/sticky`)
   - **Super Sticky**: Always at the very top everywhere
   - **Tag Sticky**: Only at the top in selected tags

## Admin Settings

| Setting | Description |
|---------|-------------|
| Show tag stickies in All Discussions | When disabled, tag sticky discussions only appear in their respective tag pages |
| Super sticky badge icon | Font Awesome icon class (default: `fas fa-star`) |

## Links

- [Source code on GitHub](https://github.com/huseyinfiliz/stickiest)
- [Report an issue](https://github.com/huseyinfiliz/stickiest/issues)
- [Download via Packagist](https://packagist.org/packages/huseyinfiliz/stickiest)

## Credits

This extension is a Flarum 2.x rewrite inspired by [the-turk/flarum-stickiest](https://github.com/the-turk/flarum-stickiest) which was built for Flarum 1.x.

## License

MIT License - see [LICENSE.md](LICENSE.md)