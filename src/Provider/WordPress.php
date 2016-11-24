<?php

namespace Hellonico\Fixtures\Provider;

use Faker\Provider\Base;

class WordPress extends Base
{

    /**
     * Get upload dir
     * @return string
     */
    public function uploadDir()
    {
        $upload_dir = wp_upload_dir();
        if (isset($upload_dir['path']) && is_dir($upload_dir['path']) && is_writable($upload_dir['path'])) {
            return $upload_dir['path'];
        }
        return sys_get_temp_dir();
    }

    /**
     * Get a random post ID
     * @param  array|string  $args
     * @return int|boolean
     */
    public function postId($args = [])
    {
        $query_args = array_merge(wp_parse_args($args), [
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);

        $posts = get_posts($query_args);
        if (empty($posts)) {
            return false;
        }

        return absint(self::randomElement($posts));
    }

    /**
     * Get a random attachment ID
     * @param  array|string  $args
     * @return int|boolean
     */
    public function attachmentId($args = [])
    {
        $defaults = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => ['image/jpeg', 'image/gif', 'image/png', 'image/bmp', 'image/tiff', 'image/x-icon'],
        ];
        return $this->postId(array_merge(wp_parse_args($args), $defaults));
    }

    /**
     * Get a random user ID
     * @param  array|string  $args
     * @return int|boolean
     */
    public function userId($args = [])
    {
        $query_args = array_merge(wp_parse_args($args), [
            'number'  => -1,
            'fields'  => ['ID'],
        ]);

        $users = get_users($query_args);

        if (empty($users)) {
            return false;
        }

        return absint(self::randomElement(wp_list_pluck($users, 'ID')));
    }

    /**
     * Get a random term ID
     * @param  array|string  $args
     * @return int|boolean
     */
    public function termId($args = [])
    {
        $query_args = array_merge(wp_parse_args($args), [
            'number'     => 0,
            'fields'     => 'ids',
            'hide_empty' => false,
        ]);

        $terms = get_terms($query_args);

        if (empty($terms)) {
            return false;
        }

        return absint(self::randomElement($terms));
    }
}
