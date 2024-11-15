<?php

namespace EntityBase\Export;

use WP_REST_Request;
use WP_REST_Server;
use WP_Query;

function setup() {
	add_action( 'rest_api_init', __NAMESPACE__ . '\\register_routes' );
}

/**
 * Registers the route.
 */
function register_routes() : void {
	register_rest_route(
		'entitybase/v1',
		'export',
		[
			'args' => [
				'chunk_size' => [
					'description' => 'How many records to return per chunk.',
					'type' => 'number',
					'default' => 300,
				],
				'max_urls' => [
					'description' => 'How many URLs / posts to return per entity.',
					'type' => 'number',
					'default' => 100,
				],
				'format' => [
					'description' => 'The data format to get results in, one of json or csv.',
					'type' => 'string',
					'enum' => [ 'json', 'csv' ],
					'default' => 'json',
				],
			],
			[
				'methods' => WP_REST_Server::READABLE,
				'callback' => __NAMESPACE__ . '\\get_items',
				'permission_callback' => __NAMESPACE__ . '\\get_item_permissions_check',
			],
		]
	);
}

/**
 * Analyze all posts on the site for entities.
 *
 * @param array $args
 * @param array $assoc_args
 */
function get_items( WP_REST_Request $request ) {
	// Raise memory and timeout limit for export request.
	wp_raise_memory_limit( 'entitybase' );
	set_time_limit( 0 );

	// Manually stream the response, as there is likely too much to hold in memory at once.
	header( 'X-Accel-Buffering: no' );

	// Check accept header for format.
	$format = $request->get_param( 'format' ) ?? 'csv';

	// Set content type header.
	if ( $format === 'json' ) {
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ), true );
	} else {
		header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ), true );
	}

	// Determine the chunk size.
	$chunk_size = $request->get_param( 'chunk_size' ) ?: 300; // Should not be more than 10000.
	$chunk_size = max( 1, $chunk_size );

	$query_args = [
		'post_type' => 'entity',
		'posts_per_page' => 1,
		'post_status' => [ 'publish', 'draft', 'future' ],
		'paged' => 1,
	];

	// Initial query gets us the total posts.
	$posts = new WP_Query( $query_args );

	$total = intval( $posts->found_posts ?? 0 );

	// Don't do the follow up calc rows query for subsequent lookups.
	$query_args['no_found_rows'] = true;

	// Set total found results header.
	header( sprintf( 'X-WP-Total: %d', $total ) );

	// Determine number of pages.
	$total_pages = (int) ceil( $total / $chunk_size );

	$columns = [
		'entity_id',
		'post_id',
		'title',
		'path',
		'date',
		'confidence',
		'relevance',
		'wiki_link',
		'wiki_data_link',
		'dbpedia_types',
		'freebase_types',
	];

	$fp = fopen( 'php://output', 'w' );

	if ( $format === 'csv' ) {
		fputcsv( $fp, $columns );
	}

	// Begin output.
	if ( $format === 'json' ) {
		fwrite( $fp, "[\n" );
		flush();
	}

	// Page through the events for the desired day.
	for ( $page = 0; $page < $total_pages; $page++ ) {

		$query_args['posts_per_page'] = $chunk_size;
		$posts = new WP_Query( $query_args );
		$query_args['paged']++;

		$results = [];

		foreach ( $posts->posts as $post ) {

			$meta = get_post_meta( $post->ID, 'raw_data', true );

			$connected_posts = new WP_Query( [
				'post_type' => 'any',
				'post_status' => 'publish',
				'meta_key' => mb_strcut( '_entity_' . $post->post_name, 0, 256 ),
				'meta_compare' => 'EXISTS',
				'orderby' => 'meta_value_num',
				'order' => 'desc',
				'posts_per_page' => $request->get_param( 'max_urls' ) ?: 300,
				'no_found_rows' => true,
			] );

			$dbpedia_types = wp_get_post_terms( $post->ID, 'entity_type', [ 'fields' => 'names' ] );
			$freebase_types = wp_get_post_terms( $post->ID, 'entity_freebase_type', [ 'fields' => 'names' ] );

			foreach ( $connected_posts->posts as $connected_post ) {
				$confidence = (float) get_post_meta( $connected_post->ID, mb_strcut( '_entity_' . $post->post_name, 0, 256 ), true );
				$relevance = (float) get_post_meta( $connected_post->ID, mb_strcut( '_entity_rel_' . $post->post_name, 0, 256 ), true );

				$row = [
					$post->ID,
					$connected_post->ID,
					$post->post_title,
					wp_parse_url( get_the_permalink( $connected_post ), PHP_URL_PATH ),
					$connected_post->post_date,
					$confidence,
					$relevance,
					$meta['wikiLink'] ?? '',
					empty( $meta['wikidataId'] ) ? '' : 'https://www.wikidata.org/wiki/' . $meta['wikidataId'],
					is_wp_error( $dbpedia_types ) ? '' : implode( ',', $dbpedia_types ),
					is_wp_error( $freebase_types ) ? '' : implode( ',', $freebase_types ),
				];

				if ( $format === 'csv' ) {
					fputcsv( $fp, $row );
				}

				if ( $format === 'json' ) {
					$results[] = array_combine( $columns, $row );
				}
			}
		}

		if ( $format === 'json' ) {
			$results = array_map( 'wp_json_encode', $results );
			$results = implode( "\n,", $results );
			if ( ! empty( $results ) ) {
				// Add a comma in between result sets except for the last page.
				$results .= $page === $total_pages - 1 ? '' : ",\n";
			}
			fwrite( $fp, $results );
		}

		// Avoid overloading memory.
		wp_cache_flush_runtime();

		flush();
	}

	// Close out the JSON array.
	if ( $format === 'json' ) {
		fwrite( $fp, ']' );
		flush();
	}

	fclose( $fp );

	exit;
}

function get_item_permissions_check() {
	return current_user_can( 'manage_options' );
}
