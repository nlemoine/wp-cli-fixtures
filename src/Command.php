<?php

namespace Hellonico\Fixtures;

use Faker\Factory;
use Hellonico\Fixtures\Provider\WordPress;
use Nelmio\Alice\Loader\NativeLoader;
use ReflectionClass;
use WP_CLI;
use WP_CLI_Command;
use function WP_CLI\Utils\make_progress_bar;

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

        // Remove revisions, it duplicates posts when they are updated
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

        WP_CLI::line('Loading fixtures... This might take some time depending on images number and connection speed');

        // Load file
        $loader = new NativeLoader($generator);

        $object_set = $loader->loadFile($file);
        $objects    = $object_set->getObjects();

        if (empty($objects)) {
            WP_CLI::error('No fixtures has been found.');
        }

        // Perist objects
        $progress = make_progress_bar('Saving fixtures...', count($objects));
        $counts   = [];
        foreach ($objects as $object) {
            if (!$object->persist()) {
                continue;
            }
            // Try to distingish created content types (e.g. comment, post, page, CPT, term, etc.)
            $type = $this->getContentType($object);
            if (!isset($counts[$type])) {
                $counts[$type] = 0;
            }
            ++$counts[$type];
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
     * [--yes]
     * : Delete the fake data without a confirmation prompt.
     *
     * ## EXAMPLES
     *
     *     wp fixtures delete post
     *     wp fixtures delete
     */
    public function delete($args = [], array $assoc_args = [])
    {
        $valid_types = ['post', 'attachment', 'term', 'comment', 'user'];

        // Delete only a fixture type
        if (isset($args[0]) && !empty($args[0])) {
            $type = $args[0];
            if (!in_array($type, $valid_types, true)) {
                WP_CLI::error(sprintf('"%s" is not a valid type, valid types are: %s', $type, implode(', ', $valid_types)));
            }

            $class   = sprintf('%s\Entity\%s', __NAMESPACE__, ucfirst($type));
            $confirm = WP_CLI::confirm(sprintf('Are you sure you want to delete all %s fixtures?', $type), $assoc_args);
            $class::delete();

            return;
        }

        $confirm = WP_CLI::confirm('Are you sure you want to delete all fixtures?');

        foreach ($valid_types as $type) {
            $class = sprintf('%s\Entity\%s', __NAMESPACE__, ucfirst($type));
            $class::delete();
        }
    }

    /**
     * Get content type.
     *
     * @param object $object
     *
     * @return string
     */
    private function getContentType($object)
    {
        $reflect = new ReflectionClass($object);
        $type    = strtolower($reflect->getShortName());

        if ('post' === $type) {
            $type = $object->post_type;
        }

        return $type;
    }
}
