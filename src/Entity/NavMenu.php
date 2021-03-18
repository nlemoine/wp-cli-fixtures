<?php

namespace Hellonico\Fixtures\Entity;

use WP_CLI;
use WP_Term_Query;

class NavMenu extends Term
{
    public $taxonomy = 'nav_menu';
    public $locations;

    public function persist()
    {
        parent::persist();
        if (!empty($this->locations) && is_array($this->locations)) {
            $locations = array_fill_keys($this->locations, $this->term_id);
            set_theme_mod('nav_menu_locations', $locations);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function delete()
    {
        $query = new WP_Term_Query([
            'taxonomy'   => 'nav_menu',
            'fields'     => 'ids',
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key'   => '_fake',
                    'value' => true,
                ],
            ],
        ]);

        if (empty($query->terms)) {
            WP_CLI::line(WP_CLI::colorize('%BInfo:%n No fake navmenus to delete'));

            return false;
        }

        foreach ($query->terms as $id) {
            $term = get_term($id);
            if (!isset($term->taxonomy)) {
                continue;
            }
            wp_delete_term($id, $term->taxonomy);
        }
        $count = count($query->terms);

        WP_CLI::success(sprintf('%s navmenu%s have been successfully deleted', $count, $count > 1 ? 's' : ''));
    }
}
