<?php

namespace Hellonico\Fixtures\Entity;

use WP_CLI;
use WP_Query;

class NavMenuItem extends Post {

	public $menu_item_object_id;
	public $menu_item_object;
	public $menu_item_parent_id;
	public $menu_item_position;
	public $menu_item_type;
	public $menu_item_title;
	public $menu_item_url;
	public $menu_item_description;
	public $menu_item_attr_title;
	public $menu_item_target;
	public $menu_item_classes;
	public $menu_item_xfn;
	public $menu_item_status = 'publish';
	public $menu_id;

	/**
	 * {@inheritdoc}
	 */
	public function persist() {
		if ( ! $this->ID ) {
			return false;
		}

		// Change post before updating
		global $wpdb;
		$wpdb->update(
			$wpdb->posts,
			[
				'post_type' => 'nav_menu_item',
			],
			[
				'ID' => $this->ID,
			]
		);
		clean_post_cache( $this->ID );

		$data = $this->transformData( $this->getData() );

		$post_id = wp_update_nav_menu_item( $this->menu_id, $this->ID, $data );

		// Handle errors
		if ( is_wp_error( $post_id ) ) {
			wp_delete_post( $this->ID, true );
			WP_CLI::error( html_entity_decode( $post_id->get_error_message() ), false );
			WP_CLI::error( sprintf( 'An error occured while updating the post ID %d, it has been deleted.', $this->ID ), false );
			$this->setCurrentId( false );

			return false;
		}

		return true;
	}

	private function transformData( array $data ) {
		unset( $data['tax_input'], $data['post_category'], $data['ID'], $data['menu-id'] );
		foreach ( $data as $key => $item ) {
			if ( false === strpos( $key, 'menu_' ) ) {
				unset( $data[ $key ] );

				continue;
			}

			// Get properties for objects
			if ( $key === 'menu_item_object' && $item instanceof Entity ) {
				if ( $item instanceof Post ) {
					$data['menu-item-type']      = 'post_type';
					$data['menu-item-object']    = $item->post_type;
					$data['menu-item-object-id'] = $item->ID;
					unset( $data[ $key ] );

					continue;
				}
				if ( $item instanceof Term ) {
					$data['menu-item-type']      = 'taxonomy';
					$data['menu-item-object']    = $item->taxonomy;
					$data['menu-item-object-id'] = $item->term_id;
					unset( $data[ $key ] );

					continue;
				}
			}

			// Transform keys
			$data[ str_replace( '_', '-', $key ) ] = $data[ $key ];
			unset( $data[ $key ] );
		}

		return $data;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function delete() {
		$query = new WP_Query(
			[
				'fields'         => 'ids',
				'meta_query'     => [
					[
						'key'   => '_fake',
						'value' => true,
					],
				],
				'post_status'    => 'any',
				'post_type'      => 'nav_menu_item',
				'posts_per_page' => -1,
			]
		);

		if ( empty( $query->posts ) ) {
			WP_CLI::line( WP_CLI::colorize( '%BInfo:%n No fake navmenuitems to delete' ) );

			return false;
		}

		foreach ( $query->posts as $id ) {
			wp_delete_post( $id, true );
		}
		$count = count( $query->posts );

		WP_CLI::success( sprintf( '%s navmenuitem%s have been successfully deleted', $count, $count > 1 ? 's' : '' ) );
	}
}
