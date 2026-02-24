<?php

/*
 * This file is part of Stickiest.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace HuseyinFiliz\Stickiest\Provider;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Sticky\PinStickiedDiscussionsToTop as StickyPin;
use HuseyinFiliz\Stickiest\PinStickiedDiscussionsToTop as StickiestPin;

class PinProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->bind(StickyPin::class, StickiestPin::class);
    }
}
