<?php

namespace Hellonico\Fixtures\Entity;

use WP_CLI;

class Post extends Entity
{
    public $ID;
    public $comment_status;
    public $guid;
    public $menu_order;
    public $ping_status;
    public $post_author;
    public $post_content;
    public $post_content_filtered;
    public $post_date;
    public $post_excerpt;
    public $post_mime_type;
    public $post_modified;
    public $post_name;
    public $post_parent;
    public $post_password;
    public $post_status = 'publish';
    public $post_title;
    public $post_type;
    public $to_ping;
    public $tax_input;
    public $post_category;
    public $meta_input;
    public $acf;
    private $extra = ['meta_input', 'acf'];

    /**
     * {@inheritDoc}
     */
    public function create()
    {
        $this->ID = wp_insert_post([
            'post_title'  => sprintf('post-%s', uniqid()),
            'post_status' => 'publish',
        ]);
        update_post_meta($this->ID, '_fake', true);
    }

    /**
     * {@inheritDoc}
     */
    public function persist()
    {
        if (!$this->ID) {
            return false;
        }

        // Update entity
        $post_id = wp_update_post($this->getData(), true);

        // Handle errors
        if (is_wp_error($post_id)) {
            wp_delete_post($this->ID, true);
            WP_CLI::error(html_entity_decode($term_id->get_error_message()), false);
            WP_CLI::error(sprintf('An error occured while updating the post ID %d, it has been deleted.', $this->ID), false);
            $this->setCurrentId(false);
            return false;
        }

        // Add a 'fake' flag to dynamically created non hierarchical terms
        if ($this->tax_input && is_array($this->tax_input)) {
            foreach ($this->tax_input as $taxonomy => $terms) {
                if (is_taxonomy_hierarchical($taxonomy)) {
                    continue;
                }
                if (!is_array($terms) && $terms) {
                    $terms = (array) $terms;
                }
                foreach ($terms as $term) {
                    if (($term_obj = get_term_by('name', $term, $taxonomy)) !== false) {
                        update_term_meta($term_obj->term_id, '_fake', true);
                    }
                }
            }
        }

        // Save meta
        $meta = $this->getMetaData();
        foreach ($meta as $meta_key => $meta_value) {
            update_post_meta($this->ID, $meta_key, $meta_value);
        }

        // Save ACF fields
        if (class_exists('acf') && !empty($this->acf) && is_array($this->acf)) {
            foreach ($this->acf as $name => $value) {
                $field = acf_get_field($name);
                update_field($field['key'], $value, $postId);
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function exists($id)
    {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare("
            SELECT ID
            FROM {$wpdb->posts}
            WHERE ID = %d
            LIMIT 1
        ", absint($id)));
    }

    /**
     * {@inheritDoc}
     */
    public function setCurrentId($id)
    {
        $this->ID = $id;
    }

    /**
     * {@inheritDoc}
     */
    public static function delete()
    {
        global $wpdb;

        // Get all fake post IDs
        $ids = $wpdb->get_col("
            SELECT post_id
            FROM {$wpdb->prefix}postmeta
            WHERE meta_key = '_fake'
            AND meta_value = 1
        ");

        if (empty($ids)) {
            WP_CLI::line(WP_CLI::colorize('%BInfo:%n No fake posts to delete'));
            return false;
        }

        // Delete posts
        foreach ($ids as $id) {
            wp_delete_post($id, true);
        }

        WP_CLI::success(sprintf('Deleted %s posts', count($ids)));
    }
}
