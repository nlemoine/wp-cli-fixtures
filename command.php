<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wp_fixtures_autoloader = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $wp_fixtures_autoloader ) ) {
	require_once $wp_fixtures_autoloader;
}

WP_CLI::add_command( 'fixtures', 'Hellonico\Fixtures\Command' );
