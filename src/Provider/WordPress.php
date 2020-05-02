<?php

namespace Hellonico\Fixtures\Provider;

use Faker\Provider\Base;
use Faker\Provider\File;

class WordPress extends Base
{

    /**
     * Get permalink
     *
     * @param int $id
     * @return void
     */
    public function permalink($id)
    {
        return get_permalink($id);
    }

    /**
     * Get a file content
     *
     * @param  string $file
     * @return string
     */
    public function fileContent($file)
    {
        if (!is_file($file)) {
            throw new \InvalidArgumentException(sprintf('File %s does not exist.', $file));
        }

        return file_get_contents($file);
    }

    /**
     * Get files
     *
     * @param  string  $src
     * @param  string  $target
     * @param  boolean $fullpath
     * @return string
     */
    public function fileIn($src = '/tmp', $target = false, $fullpath = true)
    {
        if (false === $target) {
            $target = $this->uploadDir();
        }

        return File::file($src, $target, $fullpath);
    }

    /**
     * Get upload dir.
     *
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
     * Get a random post ID.
     *
     * @param array|string $args
     *
     * @return int|bool
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
     * Get a random attachment ID.
     *
     * @param array|string $args
     *
     * @return int|bool
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
     * Get a random user ID.
     *
     * @param array|string $args
     *
     * @return int|bool
     */
    public function userId($args = [])
    {
        $query_args = array_merge(wp_parse_args($args), [
            'number' => -1,
            'fields' => ['ID'],
        ]);

        $users = get_users($query_args);

        if (empty($users)) {
            return false;
        }

        return absint(self::randomElement(wp_list_pluck($users, 'ID')));
    }

    /**
     * Get a random term ID.
     *
     * @param array|string $args
     *
     * @return int|bool
     */
    public function termId($args = [])
    {
        $query_args = array_merge(wp_parse_args($args, ['taxonomy' => 'category']), [
            'number'     => 0,
            'fields'     => 'ids',
            'hide_empty' => false,
        ]);

        $terms = get_terms($query_args);

        if (empty($terms) || is_wp_error($terms)) {
            return false;
        }

        return absint(self::randomElement($terms));
    }
}
