<?php

namespace EntityBase\Admin;

const OPTION_API_KEY = 'entitybase_textrazor_api_key';
const OPTION_ENTITY_ALLOWLIST = 'entitybase_entity_allowlist';
const OPTION_ENTITY_BLOCKLIST = 'entitybase_entity_blocklist';
const OPTION_DBPEDIA_FILTERS = 'entitybase_dbpedia_filters';
const OPTION_FREEBASE_FILTERS = 'entitybase_freebase_filters';
const OPTION_DBPEDIA_BLOCKLIST = 'entitybase_dbpedia_blocklist';
const OPTION_FREEBASE_BLOCKLIST = 'entitybase_freebase_blocklist';
const OPTION_MIN_CONFIDENCE = 'entitybase_min_confidence';
const OPTION_MIN_RELEVANCE = 'entitybase_min_relevance';

function setup() {
	add_action( 'admin_menu', __NAMESPACE__ . '\\settings_page' );
	add_action( 'admin_init', __NAMESPACE__ . '\\settings_init' );
}

// Add submenu page under "entity" post type menu
function settings_page() {
	add_submenu_page(
		'edit.php?post_type=entity',
		__( 'Entity Settings', 'entitybase' ),
		__( 'Settings', 'entitybase' ),
		'manage_options',
		'entitybase-settings',
		__NAMESPACE__ . '\\settings_page_callback'
	);
}

// Callback function for the submenu page
function settings_page_callback() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'EntityBase Settings', 'entitybase' ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'entitybase_settings' );
			do_settings_sections( 'entitybase-settings' );
			submit_button();
			?>
		</form>
		<h2><?php esc_html_e( 'Export', 'entitybase' ) ?></h2>
		<div>
			<a class="button button-secondary" href="<?php echo esc_attr( rest_url( 'entitybase/v1/export?format=csv&_wpnonce=' . wp_create_nonce( 'wp_rest' ) ) ) ?>"><?php esc_html_e( 'Download CSV', 'entitybase' ) ?></a>
		</div>
	</div>
	<?php
}

// Register settings and fields
function settings_init() {
	register_setting( 'entitybase_settings', OPTION_API_KEY );
	register_setting( 'entitybase_settings', OPTION_ENTITY_ALLOWLIST );
	register_setting( 'entitybase_settings', OPTION_ENTITY_BLOCKLIST );
	register_setting( 'entitybase_settings', OPTION_DBPEDIA_FILTERS );
	register_setting( 'entitybase_settings', OPTION_FREEBASE_FILTERS );
	register_setting( 'entitybase_settings', OPTION_DBPEDIA_BLOCKLIST );
	register_setting( 'entitybase_settings', OPTION_FREEBASE_BLOCKLIST );
	register_setting( 'entitybase_settings', OPTION_MIN_CONFIDENCE );
	register_setting( 'entitybase_settings', OPTION_MIN_RELEVANCE );

	add_settings_section(
		'entitybase_settings_section',
		__( 'TextRazor Settings', 'entitybase' ),
		__NAMESPACE__ . '\\settings_section_callback',
		'entitybase-settings'
	);

	add_settings_field(
		OPTION_API_KEY,
		__( 'API Key', 'entitybase' ),
		__NAMESPACE__ . '\\textrazor_api_key_callback',
		'entitybase-settings',
		'entitybase_settings_section'
	);

	add_settings_field(
		OPTION_ENTITY_ALLOWLIST,
		__( 'Entity Allowlist', 'entitybase' ),
		__NAMESPACE__ . '\\entity_allowlist_callback',
		'entitybase-settings',
		'entitybase_settings_section'
	);

	add_settings_field(
		OPTION_ENTITY_BLOCKLIST,
		__( 'Entity Blocklist', 'entitybase' ),
		__NAMESPACE__ . '\\entity_blocklist_callback',
		'entitybase-settings',
		'entitybase_settings_section'
	);

	add_settings_field(
		OPTION_DBPEDIA_FILTERS,
		__( 'DBPedia Filters', 'entitybase' ),
		__NAMESPACE__ . '\\dbpedia_filters_callback',
		'entitybase-settings',
		'entitybase_settings_section'
	);

	add_settings_field(
		OPTION_FREEBASE_FILTERS,
		__( 'Freebase Filters', 'entitybase' ),
		__NAMESPACE__ . '\\freebase_filters_callback',
		'entitybase-settings',
		'entitybase_settings_section'
	);

	add_settings_field(
		OPTION_DBPEDIA_BLOCKLIST,
		__( 'DBPedia Blocklist', 'entitybase' ),
		__NAMESPACE__ . '\\dbpedia_blocklist_callback',
		'entitybase-settings',
		'entitybase_settings_section'
	);

	add_settings_field(
		OPTION_FREEBASE_BLOCKLIST,
		__( 'Freebase Blocklist', 'entitybase' ),
		__NAMESPACE__ . '\\freebase_blocklist_callback',
		'entitybase-settings',
		'entitybase_settings_section'
	);

	add_settings_field(
		OPTION_MIN_CONFIDENCE,
		__( 'Minimum Confidence Score', 'entitybase' ),
		__NAMESPACE__ . '\\minimum_confidence_callback',
		'entitybase-settings',
		'entitybase_settings_section'
	);

	add_settings_field(
		OPTION_MIN_RELEVANCE,
		__( 'Minimum Relevance Score', 'entitybase' ),
		__NAMESPACE__ . '\\minimum_relevance_callback',
		'entitybase-settings',
		'entitybase_settings_section'
	);
}

// Callback function for the settings section
function settings_section_callback() {
}

// Callback function for the API key field.
function textrazor_api_key_callback() {
	printf(
		'<input class="widefat regular-code" type="text" name="%s" value="%s" />',
		esc_attr( OPTION_API_KEY ),
		esc_attr( get_option( OPTION_API_KEY, false ) )
	);
}

// Callback function for the entity allowlist field.
function entity_allowlist_callback() {
	printf(
		'<textarea name="%s" cols="35" rows="5">%s</textarea>',
		esc_attr( OPTION_ENTITY_ALLOWLIST ),
		esc_textarea( get_option( OPTION_ENTITY_ALLOWLIST, false ) )
	);
}

// Callback function for the entity blocklist field.
function entity_blocklist_callback() {
	printf(
		'<textarea name="%s" cols="35" rows="5">%s</textarea>',
		esc_attr( OPTION_ENTITY_BLOCKLIST ),
		esc_textarea( get_option( OPTION_ENTITY_BLOCKLIST, false ) )
	);
}

// Callback function for the DBPedia filters field.
function dbpedia_filters_callback() {
	printf(
		'<textarea name="%s" cols="35" rows="5">%s</textarea>',
		esc_attr( OPTION_DBPEDIA_FILTERS ),
		esc_textarea( get_option( OPTION_DBPEDIA_FILTERS, false ) )
	);
}

// Callback function for the Freebase filters field.
function freebase_filters_callback() {
	printf(
		'<textarea name="%s" cols="35" rows="5">%s</textarea>',
		esc_attr( OPTION_FREEBASE_FILTERS ),
		esc_textarea( get_option( OPTION_FREEBASE_FILTERS, false ) )
	);
}

// Callback function for the DBPedia blocklist field.
function dbpedia_blocklist_callback() {
	printf(
		'<textarea name="%s" cols="35" rows="5">%s</textarea>',
		esc_attr( OPTION_DBPEDIA_BLOCKLIST ),
		esc_textarea( get_option( OPTION_DBPEDIA_BLOCKLIST, false ) )
	);
}

// Callback function for the Freebase blocklist field.
function freebase_blocklist_callback() {
	printf(
		'<textarea name="%s" cols="35" rows="5">%s</textarea>',
		esc_attr( OPTION_FREEBASE_BLOCKLIST ),
		esc_textarea( get_option( OPTION_FREEBASE_BLOCKLIST, false ) )
	);
}

// Callback function for the minimum confidence field.
function minimum_confidence_callback() {
	printf(
		'<input type="number" min="0" max="10" step="0.1" name="%s" value="%s" />',
		esc_attr( OPTION_MIN_CONFIDENCE ),
		esc_attr( get_option( OPTION_MIN_CONFIDENCE, 0 ) )
	);
	printf( '<p class="description">%s</p>', esc_html__( 'Unbounded but typically between 0.5 and 10 with 10 representing the highest confidence that this is a valid entity.', 'entitybase' ) );
}

// Callback function for the minimum relevance field.
function minimum_relevance_callback() {
	printf(
		'<input type="number" min="0" max="1" step="0.01" name="%s" value="%s" />',
		esc_attr( OPTION_MIN_RELEVANCE ),
		esc_attr( get_option( OPTION_MIN_RELEVANCE, 0 ) )
	);
	printf( '<p class="description">%s</p>', esc_html__( 'Between 0 and 1', 'entitybase' ) );
}
