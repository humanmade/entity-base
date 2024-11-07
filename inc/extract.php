<?php
/**
 * Extract entities from a post.
 */

namespace EntityBase\Extract;

use EntityBase\Admin;
use EntityBase\Utils;
use Exception;
use WP_Post;

function extract_entities( WP_Post $post ): array {
	$title = $post->post_title;
	$content = apply_filters( 'entitybase_filter_post_content', $post->post_content );
	$excerpt = $post->post_excerpt;

	Utils\debug_log( '|----------------------------------------|' );
	Utils\debug_log( sprintf( 'Processing post ID %s', $post->ID ) );

	$payload = implode( "\n\n", [ $title, $excerpt, $content ] );
	$cache_key = sprintf( 'entitybase_%d_%s', $post->ID, hash( 'crc32', $payload ) );

	try {
		// Call the TextRazor analyze method with the payload.
		$maybe_result = get_transient( $cache_key );
		if ( $maybe_result === false ) {
			Utils\debug_log( sprintf( '🪒 Fetching new analysis for post ID %s', $post->ID ) );
			$result = Utils\get_textrazor_client()->analyze( $payload );
			set_transient( $cache_key, $result, 0 );
		} else {
			Utils\debug_log( sprintf( '💾 Using cached response for post ID %s', $post->ID ) );
			$result = $maybe_result;
		}

		if ( is_array( $result ) && ! empty( $result['error'] ) ) {
			Utils\error_log( sprintf( 'Error: %s. Cache key: %s', $result['error'], $cache_key ), false );

			return [];
		}

		// Filter entities.
		$post_entities = array_filter( $result['response']['entities'] ?? [], function ( $entity ) {
			Utils\debug_log( sprintf( '⭐ Processing entity %s', $entity['entityId'] ) );

			// Always include entities on the allowlist.
			if (
				! empty( Utils\get_entity_allowlist() )
				&& in_array( $entity['entityId'], Utils\get_entity_allowlist(), true )
			) {
				Utils\debug_log( sprintf( '✅ On allowlist: %s', $entity['entityId'] ) );

				return true;
			}

			// Remove entities on the blocklist.
			if (
				! empty( Utils\get_entity_blocklist() )
				&& in_array( $entity['entityId'], Utils\get_entity_blocklist(), true )
			) {
				Utils\debug_log( sprintf( '❌ On blocklist: %s', $entity['entityId'] ) );

				return false;
			}

			// Ensure we have at least one type.
			if ( empty( $entity['type'] ) && empty( $entity['freebaseTypes'] ) ) {
				Utils\debug_log( sprintf( '❌ No DBPedia type or Freebase type: %s', $entity['entityId'] ) );

				return false;
			}

			// Filter by DBPedia type.
			if ( empty( $entity['type'] ) ) {
				Utils\debug_log( sprintf( '❕ No DBPedia type: %s', $entity['entityId'] ) );
			} else {
				Utils\debug_log( sprintf( 'ℹ️ DBPedia type: %s', implode( ',', $entity['type'] ?? [] ) ) );

				if (
					! empty( Utils\get_dbpedia_filters() )
					&& empty( array_intersect( (array) $entity['type'], Utils\get_dbpedia_filters() ) )
				) {
					Utils\debug_log( sprintf( '❌ Not in DBPedia type filter: %s', $entity['entityId'] ) );

					return false;
				} else if (
					! empty( Utils\get_dbpedia_blocklist() )
					&& ! empty( array_intersect( (array) $entity['type'], Utils\get_dbpedia_blocklist() ) )
				) {
					Utils\debug_log( sprintf( '❌ On DBPedia type blocklist: %s', $entity['entityId'] ) );

					return false;
				}
			}

			// Filter by Freebase type.
			if ( empty( $entity['freebaseTypes'] ) ) {
				Utils\debug_log( sprintf( '❕ No Freebase type: %s', $entity['entityId'] ) );
			} else {
				Utils\debug_log( sprintf( 'ℹ️ Freebase type: %s', implode( ',', $entity['freebaseTypes'] ?? [] ) ) );

				if (
					! empty( Utils\get_freebase_filters() )
					&& empty( array_intersect( (array) $entity['freebaseTypes'], Utils\get_freebase_filters() ) )
				) {
					Utils\debug_log( sprintf( '❌ Not in Freebase type filter: %s', $entity['entityId'] ) );

					return false;
				} else if (
					! empty( Utils\get_freebase_blocklist() )
					&& ! empty( array_intersect( (array) $entity['freebaseTypes'], Utils\get_freebase_blocklist() ) )
				) {
					Utils\debug_log( sprintf( '❌ On Freebase type blocklist: %s', $entity['entityId'] ) );

					return false;
				}
			}

			// Ensure we meet minimum confidence and relevance scores.
			if (
				( $entity['confidenceScore'] ?? 0 ) < (float) get_option( Admin\OPTION_MIN_CONFIDENCE, 0 )
				&& ( $entity['relevanceScore'] ?? 0 ) < (float) get_option( Admin\OPTION_MIN_RELEVANCE, 0 )
			) {
				Utils\debug_log( sprintf( '❌ Minimum score not met: %s', $entity['entityId'] ) );

				return false;
			}

			// Success.
			Utils\debug_log( sprintf( '✅ Criteria met: %s', $entity['entityId'] ) );

			return true;
		} );

		$entity_keys = array_column( $post_entities, 'entityId' );
		$post_entities = array_combine( $entity_keys, $post_entities );

		// Update queryable meta fields.
		$meta = get_post_meta( $post->ID );
		foreach ( $meta as $key => $value ) {
			if ( strpos( $key, '_entity_' ) === 0 ) {
				delete_post_meta( $post->ID, $key );
			}
		}

		update_post_meta( $post->ID, '_entities', $post_entities );

		foreach ( $entity_keys as $key ) {
			$slug = sanitize_title( $key );
			update_post_meta(
				$post->ID,
				mb_strcut( '_entity_' . $slug, 0, 256 ),
				$post_entities[ $key ]['confidenceScore']
			);
			update_post_meta(
				$post->ID,
				mb_strcut( '_entity_rel_' . $slug, 0, 256 ),
				$post_entities[ $key ]['relevanceScore']
			);
		}

	} catch ( Exception $error ) {
		Utils\error_log( sprintf( ' - Error: %s', $error->getMessage() ), false );

		set_transient( $cache_key, [ 'error' => $error->getMessage() ], 0 );
	}

	return $post_entities;
}
