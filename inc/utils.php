<?php

namespace EntityBase\Utils;

use EntityBase\Admin;
use TextRazor;
use TextRazorSettings;
use WP_CLI;

function get_textrazor_client() : TextRazor {
	TextRazorSettings::setApiKey( get_option( Admin\OPTION_API_KEY, '' ) );

	$textrazor = new TextRazor();

	// @TODO add admin settings for these options
	$textrazor->setLanguageOverride( 'eng' );
	$textrazor->setExtractors( [ 'entities' ] );
	$textrazor->setClassifiers( [ 'textrazor_mediatopics_2023Q1' ] );

	return $textrazor;
}

/**
 * Check if a posts exists for an entity and create one if it doesn't.
 *
 * @param array $entity Entity data returned from TextRazor
 */
function maybe_create_entity( array $entity ): void {
	$slug = sanitize_title( $entity['entityId'] );

	// Check we don't have an existing post for this Entity.
	$existing_entity_post = get_page_by_path( $slug, OBJECT, 'entity' );

	if ( ! empty( $existing_entity_post ) ) {
		return;
	}

	// Create an Entity post.
	$entity_post_args = [
		'post_type' => 'entity',
		'post_title' => $entity['entityId'],
		'post_name' => $slug,
		'post_status' => 'publish',
		'meta_input' => [
			'raw_data' => $entity,
		],
	];

	$entity_post_id = wp_insert_post( $entity_post_args );

	// Add DBPedia types.
	if ( ! empty( $entity['type'] ) ) {
		wp_set_post_terms( $entity_post_id, $entity['type'], 'entity_type', false );
	}

	// Add freebase types.
	if ( ! empty( $entity['freebaseTypes'] ) ) {
		wp_set_post_terms( $entity_post_id, $entity['freebaseTypes'], 'entity_freebase_type', false );
	}

	/**
	 * Fires once an entity has been created.
	 *
	 * @param int $entity_post_id The ID of the entity post.
	 */
	do_action( 'entitybase_entity_created', $entity_post_id );
}

/**
 * Add context-aware error log messages.
 *
 * @param string $message Log message content.
 * @param bool $exit Whether to exit execution on error. Default true.
 */
function error_log( string $message, bool $exit = true ): void {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::error( $message, $exit );

		return;
	}

	\error_log( $message );

	return;
}

/**
 * Add context-aware debug log messages.
 *
 * @param string $message Log message content.
 */
function debug_log( string $message ): void {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::debug( $message );
	}

	return;
}

/**
 * Get an array of entities to include in processing regardless of filters.
 *
 * @return string[] Array of entity names
 */
function get_entity_allowlist(): array {
	return array_filter( explode( "\r\n", get_option( Admin\OPTION_ENTITY_ALLOWLIST, '' ) ) );
}

/**
 * Get an array of entities to exclude from processing.
 *
 * @return string[] Array of entity names
 */
function get_entity_blocklist(): array {
	return array_filter( explode( "\r\n", get_option( Admin\OPTION_ENTITY_BLOCKLIST, '' ) ) );
}

/**
 * Get an array of DBPedia types to include.
 *
 * @return string[] Array of DBPedia types
 */
function get_dbpedia_filters(): array {
	return array_filter( explode( "\r\n", get_option( Admin\OPTION_DBPEDIA_FILTERS, '' ) ) );
}

/**
 * Get an array of Freebase types to include.
 *
 * @return string[] Array of Freebase types
 */
function get_freebase_filters(): array {
	return array_filter( explode( "\r\n", get_option( Admin\OPTION_FREEBASE_FILTERS, '' ) ) );
}

/**
 * Get an array of DBPedia types to exclude from processing.
 *
 * @return string[] Array of DBPedia types
 */
function get_dbpedia_blocklist(): array {
	return array_filter( explode( "\r\n", get_option( Admin\OPTION_DBPEDIA_BLOCKLIST, '' ) ) );
}

/**
 * Get an array of Freebase types to exclude from processing.
 *
 * @return string[] Array of Freebase types
 */
function get_freebase_blocklist(): array {
	return array_filter( explode( "\r\n", get_option( Admin\OPTION_FREEBASE_BLOCKLIST, '' ) ) );
}
