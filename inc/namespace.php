<?php

namespace EntityBase;

use WP_Post;
use EntityBase\Utils;

function setup() {
	add_action( 'init', __NAMESPACE__ . '\\register_entity_post_type' );
	add_filter( 'manage_entity_posts_columns', __NAMESPACE__ . '\\add_connected_posts_column' );
	add_action( 'manage_entity_posts_custom_column', __NAMESPACE__ . '\\render_connected_posts_column', 10, 2 );
	add_filter( 'manage_edit-entity_sortable_columns', __NAMESPACE__ . '\\make_connected_posts_column_sortable' );
	add_action( 'pre_get_posts', __NAMESPACE__ . '\\sort_by_connected_posts' );
	add_action( 'pre_wp_update_comment_count_now', __NAMESPACE__ . '\\filter_pre_wp_update_comment_count_now', 10, 3 );
}

/**
 * Create a post type to store found entities.
 */
function register_entity_post_type() : void {
	$labels = [
		'name'                  => _x( 'Entities', 'Post Type General Name', 'entitybase' ),
		'singular_name'         => _x( 'Entity', 'Post Type Singular Name', 'entitybase' ),
		'menu_name'             => __( 'Entities', 'entitybase' ),
		'name_admin_bar'        => __( 'Entity', 'entitybase' ),
		'archives'              => __( 'Entity Archives', 'entitybase' ),
		'attributes'            => __( 'Entity Attributes', 'entitybase' ),
		'parent_item_colon'     => __( 'Parent Entity:', 'entitybase' ),
		'all_items'             => __( 'All Entities', 'entitybase' ),
		'add_new_item'          => __( 'Add New Entity', 'entitybase' ),
		'add_new'               => __( 'Add New', 'entitybase' ),
		'new_item'              => __( 'New Entity', 'entitybase' ),
		'edit_item'             => __( 'Edit Entity', 'entitybase' ),
		'update_item'           => __( 'Update Entity', 'entitybase' ),
		'view_item'             => __( 'View Entity', 'entitybase' ),
		'view_items'            => __( 'View Entities', 'entitybase' ),
		'search_items'          => __( 'Search Entity', 'entitybase' ),
		'not_found'             => __( 'Not found', 'entitybase' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'entitybase' ),
		'featured_image'        => __( 'Featured Image', 'entitybase' ),
		'set_featured_image'    => __( 'Set featured image', 'entitybase' ),
		'remove_featured_image' => __( 'Remove featured image', 'entitybase' ),
		'use_featured_image'    => __( 'Use as featured image', 'entitybase' ),
		'insert_into_item'      => __( 'Insert into entity', 'entitybase' ),
		'uploaded_to_this_item' => __( 'Uploaded to this entity', 'entitybase' ),
		'items_list'            => __( 'Entities list', 'entitybase' ),
		'items_list_navigation' => __( 'Entities list navigation', 'entitybase' ),
		'filter_items_list'     => __( 'Filter entities list', 'entitybase' ),
	];

	$args = [
		'label'                 => __( 'Entity', 'entitybase' ),
		'description'           => __( 'Organisations, companies and notable people referenced in your content', 'entitybase' ),
		'labels'                => $labels,
		'supports'              => [ 'title', 'editor' ],
		'hierarchical'          => false,
		'public'                => false,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'show_in_rest'          => false,
		'publicly_queryable'    => false,
		'capability_type'       => 'page',
		'menu_icon'             => 'dashicons-id-alt'
	];

	register_post_type( 'entity', $args );

	$taxonomy_labels = [
		'name'              => _x( 'DBPedia Types', 'taxonomy general name', 'entitybase' ),
		'singular_name'     => _x( 'DBPedia Type', 'taxonomy singular name', 'entitybase' ),
		'search_items'      => __( 'Search Types', 'entitybase' ),
		'all_items'         => __( 'All Types', 'entitybase' ),
		'parent_item'       => __( 'Parent Type', 'entitybase' ),
		'parent_item_colon' => __( 'Parent Type:', 'entitybase' ),
		'edit_item'         => __( 'Edit Type', 'entitybase' ),
		'update_item'       => __( 'Update Type', 'entitybase' ),
		'add_new_item'      => __( 'Add New Type', 'entitybase' ),
		'new_item_name'     => __( 'New Type Name', 'entitybase' ),
		'menu_name'         => __( 'DBPedia Types', 'entitybase' ),
	];

	$taxonomy_args = [
		'labels'            => $taxonomy_labels,
		'hierarchical'      => false,
		'public'            => false,
		'show_ui'           => true,
		'show_admin_column' => true,
		'show_in_nav_menus' => false,
	];

	register_taxonomy( 'entity_type', 'entity', $taxonomy_args );

	$taxonomy_labels = [
		'name'              => _x( 'Freebase Types', 'taxonomy general name', 'entitybase' ),
		'singular_name'     => _x( 'Freebase Type', 'taxonomy singular name', 'entitybase' ),
		'search_items'      => __( 'Search Types', 'entitybase' ),
		'all_items'         => __( 'All Types', 'entitybase' ),
		'parent_item'       => __( 'Parent Type', 'entitybase' ),
		'parent_item_colon' => __( 'Parent Type:', 'entitybase' ),
		'edit_item'         => __( 'Edit Type', 'entitybase' ),
		'update_item'       => __( 'Update Type', 'entitybase' ),
		'add_new_item'      => __( 'Add New Type', 'entitybase' ),
		'new_item_name'     => __( 'New Type Name', 'entitybase' ),
		'menu_name'         => __( 'Freebase Types', 'entitybase' ),
	];

	$taxonomy_args = [
		'labels'            => $taxonomy_labels,
		'hierarchical'      => false,
		'public'            => false,
		'show_ui'           => true,
		'show_admin_column' => true,
		'show_in_nav_menus' => false,
	];

	register_taxonomy( 'entity_freebase_type', 'entity', $taxonomy_args );
}

/**
 * Add a column to display the number of connected posts.
 *
 * @param array $columns The existing columns.
 * @return array The modified columns.
 */
function add_connected_posts_column( array $columns ): array {
	$columns['connected_posts'] = __( 'Connected Posts', 'entitybase' );
	return $columns;
}

/**
 * Render the connected posts column.
 *
 * @param string $column The column name.
 * @param int $post_id The post ID.
 */
function render_connected_posts_column( string $column, int $post_id ): void {
	if ( 'connected_posts' === $column ) {
		$entity_post = get_post( $post_id );
		echo absint( $entity_post->comment_count );
	}
}

/**
 * Make the connected posts column sortable.
 *
 * @param array $columns The existing sortable columns.
 * @return array The modified sortable columns.
 */
function make_connected_posts_column_sortable( array $columns ): array {
	$columns['connected_posts'] = 'connected_posts';
	return $columns;
}

/**
 * Sort the entities by the number of connected posts.
 *
 * @param \WP_Query $query The current query.
 */
function sort_by_connected_posts( \WP_Query $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( 'connected_posts' === $query->get( 'orderby' ) ) {
		$query->set( 'orderby', 'comment_count' );
	}
}

/**
 * Filters comment count for entity post types and store connected post count.
 *
 * @param int|null $new_count The new comment count.
 * @param int $old_count The old comment count.
 * @param int $post_id   The post ID.
 *
 * @return int The filtered comment count - either connected posts count for entities or original count.
 */
function filter_pre_wp_update_comment_count_now( ?int $new_count, int $old_count, int $post_id ): ?int {
	$entity_post = get_post( $post_id );

	if ( 'entity' !== $entity_post->post_type ) {
		return $new_count;
	}

	return Utils\get_connected_posts_count( $entity_post );
}
