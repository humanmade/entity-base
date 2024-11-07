<?php
/**
 * Handle data clean up when entities are deleted.
 */

namespace EntityBase\Cleanup;

use WP_Post;

function setup(): void {
	add_action( 'delete_post', __NAMESPACE__ . '\\cleanup_post_meta', 10, 2 );
}

/**
 * Remove associated post meta when an entity is deleted.
 *
 * @param integer $post_id Deleted post ID.
 * @param WP_Post $post Deleted post object.
 */
function cleanup_post_meta( int $post_id, WP_Post $post ): void {
	if ( $post->post_type !== 'entity' ) {
		return;
	}

	$key = sanitize_title( $post->post_title );

	delete_post_meta_by_key( '_entity_' . $key );
	delete_post_meta_by_key( '_entity_rel_' . $key );
}
