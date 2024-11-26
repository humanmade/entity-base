<?php
/**
 * Analyse a single post on save.
 */

namespace EntityBase\Single;

use EntityBase;
use EntityBase\Extract;
use EntityBase\Utils;
use WP_Post;

function setup(): void {
	add_action( 'save_post', __NAMESPACE__ . '\\analyse_post', 10, 3 );
	add_action( 'transition_post_status', __NAMESPACE__ . '\\update_connected_entities_count_on_transition_post_status', 10, 4 );
	add_action( 'before_delete_post', __NAMESPACE__ . '\\update_connected_entities_count_on_before_delete', 10, 4 );
}

/**
 * Analyse the post and extract entities on save.
 *
 * @param int $post_id The ID of the post being saved.
 * @param WP_Post $post The post object.
 * @param bool $update Whether this is an existing post being updated or not.
 */
function analyse_post( int $post_id, WP_Post $post, bool $update ): void {
	$allowed_post_types = apply_filters( 'entitybase_allowed_post_types', [ 'post' ] );

	if ( ! in_array( $post->post_type, (array) $allowed_post_types, true ) ) {
		return;
	}

	// Extract entities from this post.
	$entities = Extract\extract_entities( $post );

	// Create entity posts.
	foreach ( $entities as $entity ) {
		Utils\maybe_create_entity( $entity );
	}

	// Avoid overloading memory.
	wp_cache_flush_runtime();
}

/**
 * Update the connected entities count when a post status changes.
 *
 * Update if publishing, or unpublishing a post.
 *
 * @param string $new_status The new post status.
 * @param string $old_status The old post status.
 * @param WP_Post $post The post object.
 */
function update_connected_entities_count_on_transition_post_status( string $new_status, string $old_status, WP_Post $post ): void {
	if (
		( 'publish' === $new_status && 'publish' !== $old_status )
		|| ( 'publish' === $old_status && 'publish' !== $new_status )
	) {
		foreach ( Utils\get_entities_for_post( $post ) as $entity ) {
			EntityBase\update_connected_posts_count( $entity );
		}
	}
}

/**
 * Update the connected entities count when a post is deleted.
 *
 * @param int $post_id The ID of the post being deleted.
 */
function update_connected_entities_count_on_before_delete( int $post_id ): void {
	$post = get_post( $post_id );

	if ( ! $post ) {
		return;
	}

	$entities = Utils\get_entities_for_post( $post );

	if ( empty( $entities ) ) {
		return;
	}

	add_action( 'deleted_post', function( $delete_post_id ) use ( $post_id, $entities ) {
		if ( $post_id !== absint( $delete_post_id ) || empty( $entities ) ) {
			return;
		}

		foreach ( $entities as $entity ) {
			EntityBase\update_connected_posts_count( $entity );
		}
	} );
}
