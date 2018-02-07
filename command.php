<?php

namespace Hellonico\Fixtures;

use WP_CLI;

if (!class_exists('WP_CLI')) {
    return;
}

WP_CLI::add_command('fixtures', __NAMESPACE__ . '\\Command');
