<?php
/**
 * Analyse a single post on save.
 */

namespace EntityBase\Single;

use EntityBase\Extract;
use EntityBase\Utils;
use WP_Post;

function setup(): void {
	add_action( 'save_post', __NAMESPACE__ . '\\analyse_post', 10, 3 );
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
