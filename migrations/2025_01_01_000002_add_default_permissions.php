<?php

use Flarum\Database\Migration;
use Flarum\Group\Group;

return Migration::addPermissions([
    'discussion.stickiest' => Group::MODERATOR_ID,
    'discussion.tagSticky' => Group::MODERATOR_ID,
]);
