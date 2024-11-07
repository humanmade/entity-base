<?php

namespace EntityBase\CLI;

use EntityBase\Extract;
use EntityBase\Export;
use EntityBase\Utils;
use WP_CLI;
use WP_Query;
use WP_REST_Request;

function setup() {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::add_command( 'entity-base analyse', __NAMESPACE__ . '\\analyse' );
		WP_CLI::add_command( 'entity-base export', __NAMESPACE__ . '\\export' );
	}
}

/**
 * Analyze all posts on the site for entities.
 *
 * @param array $args
 * @param array $assoc_args
 */
function analyse( $args, $assoc_args ) {
	$post_id = isset( $assoc_args['post_id'] ) ? intval( $assoc_args['post_id'] ) : false;
	$posts_per_page = isset( $assoc_args['posts_per_page'] ) ? intval( $assoc_args['posts_per_page'] ) : 100;
	$post_types = isset( $assoc_args['post_types'] ) ? explode( ',', $assoc_args['post_types'] ) : [ 'post' ];
	$max_requests = isset( $assoc_args['max_requests'] ) ? intval( $assoc_args['max_requests'] ) : 100;
	$page = isset( $assoc_args['initial_page'] ) ? intval( $assoc_args['initial_page'] ) : 1;

	$query_args = [
		'posts_per_page' => 1,
		'paged' => $page,
	];

	// If a post ID is supplied, only process that post.
	if ( ! empty( $post_id ) ) {
		$query_args['p'] = $post_id;
		$query_args['post_status'] = [ 'publish', 'draft', 'pending', 'future', 'private' ];
	} else {
		$query_args['post_type'] = $post_types;
		$query_args['post_status'] = [ 'publish' ];
	}

	// Initial query just gives us the total via the found_posts value.
	$posts = new WP_Query( $query_args );
	$processed = 0;
	$elapsed = time();

	// No need to run SQL calc rows again.
	$query_args['no_found_rows'] = true;

	// Use max as upper limit unless 0 supplied.
	$max_requests = absint( min( $max_requests, $posts->found_posts ) ) ?: $posts->found_posts;

	while ( $processed < $max_requests ) {

		$progress = WP_CLI\Utils\make_progress_bar(
			sprintf(
				'Processing posts %d to %d of %d...',
				( $posts_per_page * ( $query_args['paged'] - 1 ) + 1 ),
				min( $posts_per_page * $query_args['paged'], $max_requests ),
				$max_requests
			),
			$max_requests,
			500
		);

		$entities = [];

		$query_args['posts_per_page'] = $posts_per_page;
		$posts = new WP_Query( $query_args );
		$query_args['paged']++;

		WP_CLI::debug( '|----------------------------------------|' );
		WP_CLI::debug( sprintf( '⚙️ Entity allowlist: %s', implode( ',', Utils\get_entity_allowlist() ) ) );
		WP_CLI::debug( sprintf( '⚙️ Entity blocklist: %s', implode( ',', Utils\get_entity_blocklist() ) ) );
		WP_CLI::debug( sprintf( '⚙️ DBPedia filters: %s', implode( ',', Utils\get_dbpedia_filters() ) ) );
		WP_CLI::debug( sprintf( '⚙️ Freebase filters: %s', implode( ',', Utils\get_freebase_filters() ) ) );
		WP_CLI::debug( sprintf( '⚙️ DBPedia blocklist: %s', implode( ',', Utils\get_dbpedia_blocklist() ) ) );
		WP_CLI::debug( sprintf( '⚙️ Freebase blocklist: %s', implode( ',', Utils\get_freebase_blocklist() ) ) );

		foreach ( $posts->posts as $post ) {
			$processed++;

			if ( $processed > $max_requests ) {
				break;
			}

			// Extract entities from this post.
			$post_entities = Extract\extract_entities( $post );

			// Merge entities for bulk processing.
			$entities = array_merge( $entities, $post_entities );

			$progress->tick();
		}

		$progress->finish();

		WP_CLI\Utils\format_items( 'table', $entities, [
			'entityId',
			'confidenceScore',
		] );

		WP_CLI::log( sprintf( 'Elapsed time %s', gmdate( 'H:i:s', time() - $elapsed ) ) );

		WP_CLI::success( sprintf( '%d entities found', count( $entities ) ) );

		$post_progress = WP_CLI\Utils\make_progress_bar( 'Creating entity posts...', count( $entities ) );

		foreach ( $entities as $entity ) {
			Utils\maybe_create_entity( $entity );

			$post_progress->tick();
		}

		$post_progress->finish();

		// Avoid overloading memory.
		wp_cache_flush_runtime();
	}

	WP_CLI::success( 'Done!' );
	exit;
}

/**
 * Export all posts on the site for entities.
 *
 * @param array $args
 * @param array $assoc_args
 */
function export( $args, $assoc_args ) {
	$request = new WP_REST_Request( 'GET', 'entity-base/v1/export' );
	$request->set_query_params( $assoc_args );

	Export\get_items( $request );
}
