<?php

namespace Hellonico\Fixtures\Entity;

use WP_CLI;
use WP_Query;

class Attachment extends Post
{
    public $file;
    public $post_type   = 'attachment';
    public $post_status = 'inherit';

    /**
     * {@inheritdoc}
     */
    public function create()
    {
        $this->ID = wp_insert_post([
            'post_title'  => sprintf('attachment-%s', uniqid()),
            'post_type'   => $this->post_type,
            'post_status' => $this->post_status,
        ]);
        update_post_meta($this->ID, '_fake', true);
    }

    /**
     * {@inheritdoc}
     */
    public function persist()
    {
        if (!$this->ID || empty($this->file)) {
            if (is_file($this->file)) {
                @unlink($this->file);
            }
            WP_CLI::error(sprintf('An error occured while updating the attachment ID %s, it has been deleted.', $this->file), false);
            wp_delete_attachment($this->ID, true);

            return false;
        }

        $file_name  = basename($this->file);
        $upload_dir = wp_upload_dir();

        // Image has been saved to sys temp dir
        if (false === strpos($this->file, $upload_dir['basedir'])) {
            $upload = wp_upload_bits($file_name, null, file_get_contents($this->file));
            if ($upload['error']) {
                wp_delete_attachment($this->ID, true);
                WP_CLI::error(sprintf('An error occured while updating the attachment ID %d, it has been deleted.', $this->ID), false);
                $this->setCurrentId(false);

                return false;
            } else {
                $this->file = $upload['file'];
            }
        }

        $file_type = wp_check_filetype($file_name);

        // Set required attachment properties
        $this->post_mime_type = $file_type['type'];
        $this->guid           = $upload_dir['url'] . '/' . $file_name;

        // Set post title from file name
        if (empty($this->post_title)) {
            $this->post_title = sanitize_file_name(pathinfo($file_name, PATHINFO_FILENAME));
        }

        // Update entity
        $attachment_id = wp_insert_attachment($this->getData(), $this->file);

        // Update guid and slug (can't be updated once post is created)
        global $wpdb;
        $wpdb->update(
            $wpdb->posts,
            [
                'guid'      => $this->guid,
                'post_name' => sanitize_title($this->post_title),
            ],
            [
                'ID' => $this->ID,
            ]
        );

        // Handle errors
        if (empty($attachment_id)) {
            wp_delete_attachment($this->ID, true);
            WP_CLI::error(sprintf('An error occured while updating the attachment ID %d, it has been deleted.', $this->ID), false);
            $this->setCurrentId(false);

            return false;
        }

        // Generate attachment metadata
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $this->file);

        // Assign metadata to attachment
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        // Save meta
        $meta = $this->getMetaData();
        foreach ($meta as $meta_key => $meta_value) {
            update_post_meta($this->ID, $meta_key, $meta_value);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function delete()
    {
        $query = new WP_Query([
            'fields'     => 'ids',
            'meta_query' => [
                [
                    'key'   => '_fake',
                    'value' => true,
                ],
            ],
            'post_status'    => 'any',
            'post_type'      => 'attachment',
            'posts_per_page' => -1,
        ]);

        if (empty($query->posts)) {
            WP_CLI::line(WP_CLI::colorize('%BInfo:%n No fake attachments to delete'));

            return false;
        }

        foreach ($query->posts as $id) {
            wp_delete_attachment($id, true);
        }
        $count = count($query->posts);

        WP_CLI::success(sprintf('%s attachment%s have been successfully deleted', $count, $count > 1 ? 's' : ''));
    }
}
