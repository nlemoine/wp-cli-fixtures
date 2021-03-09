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
}
