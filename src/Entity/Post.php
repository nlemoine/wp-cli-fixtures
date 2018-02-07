<?php

namespace Hellonico\Fixtures\Entity;

use WP_CLI;
use WP_Query;

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
    public $post_type = 'post';
    public $to_ping;
    public $tax_input;
    public $post_category;
    public $meta_input;
    public $acf;
    private $extra = ['meta_input', 'acf'];

    /**
     * {@inheritdoc}
     */
    public function create()
    {
        $this->ID = wp_insert_post([
            'post_title'  => sprintf('post-%s', uniqid()),
            'post_status' => 'draft',
        ]);
        update_post_meta($this->ID, '_fake', true);

        return $this->ID;
    }

    /**
     * {@inheritdoc}
     */
    public function persist()
    {
        if (!$this->ID) {
            return false;
        }

        // Remove tax input and save it, terms will be added later
        // wp_set_post_tags does not accept IDs
        $tax_input       = $this->tax_input;
        $this->tax_input = [];

        // Cast post_category as array
        $this->post_category = (array) $this->post_category;

        // Update entity
        $post_id = wp_insert_post($this->getData(), true);

        // Handle errors
        if (is_wp_error($post_id)) {
            wp_delete_post($this->ID, true);
            WP_CLI::error(html_entity_decode($post_id->get_error_message()), false);
            WP_CLI::error(sprintf('An error occured while updating the post ID %d, it has been deleted.', $this->ID), false);
            $this->setCurrentId(false);

            return false;
        }

        // Save terms
        if ($tax_input && is_array($tax_input)) {
            foreach ($tax_input as $taxonomy => $terms) {
                $terms = (array) $terms;

                // Object in taxonomy
                if (!is_object_in_taxonomy($this->post_type, $taxonomy)) {
                    continue;
                }

                // Hierachical
                if (is_taxonomy_hierarchical($taxonomy)) {
                    $terms = array_unique(array_map('intval', $terms));
                }

                // Check for terms
                if (empty(array_filter($terms))) {
                    continue;
                }

                // Add terms
                $tt_ids = wp_set_object_terms($post_id, $terms, $taxonomy);

                // Add fake flag to created terms
                // foreach ($terms as $term) {
                //     if (($term_obj = get_term_by('name', $term, $taxonomy)) !== false) {
                //         update_term_meta($term_obj->term_id, '_fake', true);
                //     }
                // }
            }
        }

        // Save meta
        $meta = $this->getMetaData();
        foreach ($meta as $meta_key => $meta_value) {
            update_post_meta($this->ID, $meta_key, $meta_value);
        }

        // Save ACF fields
        // if (class_exists('acf') && !empty($this->acf) && is_array($this->acf)) {
        //     foreach ($this->acf as $name => $value) {
        //         $field = acf_get_field($name);
        //         update_field($field['key'], $value, $post_id);
        //     }
        // }

        return true;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function setCurrentId($id)
    {
        $this->ID = $id;
    }

    /**
     * {@inheritdoc}
     */
    public static function delete()
    {
        global $wpdb;

        // Get all posts types
        $types = $wpdb->get_col("
            SELECT DISTINCT post_type
            FROM {$wpdb->prefix}posts
        ");
        if (!$types) {
            return false;
        }

        // Remove attachment from types to delete
        $attachment_index = array_search('attachment', $types);
        if (false !== $attachment_index) {
            unset($types[$attachment_index]);
        }

        $query = new WP_Query([
            'fields'     => 'ids',
            'meta_query' => [
                [
                    'key'   => '_fake',
                    'value' => true,
                ],
            ],
            'post_status'    => 'any',
            'post_type'      => $types,
            'posts_per_page' => -1,
        ]);

        if (empty($query->posts)) {
            WP_CLI::line(WP_CLI::colorize('%BInfo:%n No fake posts to delete'));

            return false;
        }

        // Delete posts
        foreach ($query->posts as $id) {
            wp_delete_post($id, true);
        }
        $count = count($query->posts);

        WP_CLI::success(sprintf('%s post%s have been successfully deleted', $count, $count > 0 ? 's' : ''));
    }
}
