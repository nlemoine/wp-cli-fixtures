<?php

namespace Hellonico\Fixtures;

use WP_CLI_Command;
use WP_CLI;
use function WP_CLI\Utils\make_progress_bar;
use Nelmio\Alice\Loader\NativeLoader;
use Faker\Factory;
use Hellonico\Fixtures\Provider\WordPress;
use ReflectionClass;

class Command extends WP_CLI_Command
{

    /**
     * Loads and save fixtures.
     *
     * ## OPTIONS
     *
     * [--file=<file>]
     * : Specify a custom location for the fixtures file.
     *
     * ## EXAMPLES
     *
     *     wp fixtures load --file=fixtures/data.yml
     *
     */
    public function load($args, $assoc_args)
    {
        $file = isset($assoc_args['file']) ? $assoc_args['file'] : 'fixtures.yml';
        if (!is_file($file)) {
            WP_CLI::error(sprintf('Fixture file %s has not been found.', $file));
        }

        // Set current user as the first administrator
        // Needed to get the right permissions for persisting some objects (post terms for example)
        $administrators = get_users(['role' => 'administrator']);
        if ($administrators) {
            $ids = wp_list_pluck($administrators, 'ID');
            wp_set_current_user(min($ids));
        }

        // Remove revisions, it duplicates posts when there are updated
        remove_action('post_updated', 'wp_save_post_revision');

        // Disable user notifications
        add_filter('send_password_change_email', '__return_false', PHP_INT_MAX);
        add_filter('send_email_change_email', '__return_false', PHP_INT_MAX);

        // Disable comment notifications
        add_filter('notify_post_author', '__return_false', PHP_INT_MAX);
        add_filter('notify_moderator', '__return_false', PHP_INT_MAX);

        // Set locale
        $generator = Factory::create(get_locale());
        // Add provider
        $generator->addProvider(new WordPress($generator));

        // Load file
        $loader = new NativeLoader($generator);
        $objectSet = $loader->loadFile($file);
        $objects = $objectSet->getObjects();

        if (empty($objects)) {
            WP_CLI::error('No fixtures has been found.');
        }

        // Perists objects
        $progress = make_progress_bar('Generating and saving fixtures...', count($objects));
        $counts = [];
        foreach ($objects as $object) {
            if ($object->persist()) {
                $reflect    = new ReflectionClass($object);
                $class_name = strtolower($reflect->getShortName());
                if (!isset($counts[$class_name])) {
                    $counts[$class_name] = 0;
                }
                $counts[$class_name]++;
            }
            $progress->tick();
        }
        $progress->finish();

        foreach ($counts as $type => $count) {
            WP_CLI::success(sprintf('%d %s%s have been successfully created', $count, $type, $count > 0 ? 's' : ''));
        }
    }

    /**
     * Delete all or one type of fixtures.
     *
     * ## OPTIONS
     *
     * [<type>]
     * : Specify the fixture type to delete
     *
     * ## EXAMPLES
     *
     *     wp fixtures delete post
     *     wp fixtures delete
     *
     */
    public function delete($args = [])
    {
        $valid_types = ['post', 'attachment', 'term', 'comment', 'user'];

        // Delete only a fixture type
        if (isset($args[0]) && !empty($args[0])) {
            $type = $args[0];
            if (!in_array($type, $valid_types, true)) {
                WP_CLI::error(sprintf('"%s" is not a valid type, valid types are: %s', $type, implode(', ', $valid_types)));
            }

            $class = sprintf('%s\Entity\%s', __NAMESPACE__, ucfirst($type));
            $class::delete();
        }

        $confirm = WP_CLI::confirm('Are you sure you want to delete all fixtures?');

        foreach ($valid_types as $type) {
            $class = sprintf('%s\Entity\%s', __NAMESPACE__, ucfirst($type));
            $class::delete();
        }
    }
}
