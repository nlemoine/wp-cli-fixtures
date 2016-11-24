<?php

namespace Hellonico\Fixtures\Entity;

use WP_CLI;

class Attachment extends Post
{
    public $file;

    /**
     * {@inheritDoc}
     */
    public function create()
    {
        $this->ID = wp_insert_post([
            'post_title'  => sprintf('attachment-%s', uniqid()),
            'post_type'   => 'attachment',
            'post_status' => 'inherit',
        ]);
        update_post_meta($this->ID, '_fake_attachment', true);
    }

    /**
     * {@inheritDoc}
     */
    public function persist()
    {
        if (!$this->ID) {
            @unlink($this->file);
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
            }
        }

        $file_type  = wp_check_filetype($file_name);

        // Set required attachment properties
        $this->post_type      = 'attachment';
        $this->post_status    = 'inherit';
        $this->post_mime_type = $file_type['type'];
        $this->guid           = $upload_dir['url'] . '/' . $file_name;

        if (empty($this->post_title)) {
            $this->post_title = sanitize_file_name(pathinfo($file_name, PATHINFO_FILENAME));
        }

        // guid can't be updated once post is created
        global $wpdb;
        $wpdb->update($wpdb->posts, [ 'guid' => $this->guid ], [ 'ID' => $this->ID ]);

        // Update entity
        $attachment_id = wp_insert_attachment($this->getData(), $this->file);

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
     * {@inheritDoc}
     */
    public static function delete()
    {
        global $wpdb;

        $ids = $wpdb->get_col("
            SELECT post_id
            FROM {$wpdb->prefix}postmeta
            WHERE meta_key = '_fake_attachment'
            AND meta_value = 1
        ");

        if (empty($ids)) {
            WP_CLI::line(WP_CLI::colorize('%BInfo:%n No fake attachments to delete'));
            return false;
        }

        foreach ($ids as $id) {
            wp_delete_attachment($id, true);
        }

        WP_CLI::success(sprintf('Deleted %s attachments', count($ids)));
    }
}
