<?php

namespace Hellonico\Fixtures\Entity;

use WP_CLI;

class Term extends Entity
{
    public $term_id;
    public $name;
    public $slug;
    public $taxonomy = 'category';
    public $description;
    public $parent;

    /**
     * {@inheritDoc}
     */
    public function create()
    {
        $term = wp_insert_term(sprintf('term-%s', uniqid()), $this->taxonomy);
        if (is_wp_error($term)) {
            WP_CLI::error(html_entity_decode($term->get_error_message()), false);
            $this->setCurrentId(false);
            return $term;
        }
        $this->term_id = $term['term_id'];
        update_term_meta($this->term_id, '_fake', true);
        return $this->term_id;
    }

    /**
     * {@inheritDoc}
     */
    public function persist()
    {
        if (!$this->term_id) {
            return false;
        }

        // Change taxonomy before updating term
        global $wpdb;
        $wpdb->update(
            $wpdb->term_taxonomy,
            [
                'taxonomy' => $this->taxonomy
            ],
            [
                'taxonomy' => 'category',
                'term_id' => $this->term_id
            ]
        );

        $term_id = wp_update_term($this->term_id, $this->taxonomy, $this->getData());

        if (is_wp_error($term_id)) {
            wp_delete_term($this->term_id, $this->taxonomy);
            WP_CLI::error(html_entity_decode($term_id->get_error_message()), false);
            WP_CLI::error(sprintf('An error occured while updating the term ID %d, it has been deleted.', $this->term_id), false);
            $this->setCurrentId(false);
            return false;
        }

        // Save meta
        $meta = $this->getMetaData();
        foreach ($meta as $meta_key => $meta_value) {
            update_term_meta($this->term_id, $meta_key, $meta_value);
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
            SELECT term_id
            FROM {$wpdb->terms}
            WHERE term_id = %d
            LIMIT 1
        ", absint($id)));
    }

    /**
     * {@inheritDoc}
     */
    public function setCurrentId($id)
    {
        $this->term_id = $id;
    }

    /**
     * {@inheritDoc}
     */
    public static function delete()
    {
        global $wpdb;

        $ids = $wpdb->get_col("
            SELECT term_id
            FROM {$wpdb->termmeta}
            WHERE meta_key = '_fake'
            AND meta_value = 1
        ");

        if (empty($ids)) {
            WP_CLI::line(WP_CLI::colorize('%BInfo:%n No fake terms to delete'));
            return false;
        }

        foreach ($ids as $id) {
            $term = get_term($id);
            if (!isset($term->taxonomy)) {
                continue;
            }
            wp_delete_term($id, $term->taxonomy);
        }

        WP_CLI::success(sprintf('Deleted %s terms', count($ids)));
    }
}
