# Stickiest

[![MIT license](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/huseyinfiliz/stickiest/blob/1.x/LICENSE) [![Latest Stable Version](https://img.shields.io/packagist/v/huseyinfiliz/stickiest)](https://packagist.org/packages/huseyinfiliz/stickiest)

This extension allows you to stick, super-stick, or tag-stick discussions to the top of the list. Compatible with Flarum 1.x.

> Looking for Flarum 2.x support? See the [2.x branch](https://github.com/huseyinfiliz/stickiest).

## Features

- **Super Sticky**: Pin discussions to the very top everywhere (All Discussions + all tag pages)
- **Tag Sticky**: Pin discussions only in selected tag pages
- **Normal Sticky**: Full compatibility with `flarum/sticky` extension
- **Event Posts**: Shows activity when super sticky status changes
- **Customizable Badge**: Choose your own icon for super sticky discussions
- **Visual Indicators**: Colored borders and backgrounds for sticky discussions

## Requirements

- Flarum 1.2+
- PHP 7.3+
- [flarum/sticky](https://github.com/flarum/sticky) extension
- [flarum/tags](https://github.com/flarum/tags) extension

## Installation
```bash
composer require huseyinfiliz/stickiest
php flarum migrate
php flarum cache:clear
```

## Updating
```bash
composer update huseyinfiliz/stickiest
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
| Super sticky badge icon | Font Awesome icon class (default: `fas fa-layer-group`) |

## Links

- [GitHub](https://github.com/huseyinfiliz/stickiest/tree/1.x)
- [Issue](https://github.com/huseyinfiliz/stickiest/issues)
- [Packagist](https://packagist.org/packages/huseyinfiliz/stickiest)

## Credits

This extension is a continuation of the-turk/flarum-stickiest, originally built by Hasan Özbey.