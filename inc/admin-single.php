<?php
/**
 * Admin Entities Panel
 *
 * Display connected entities in the post edit screen.
 *
 * @package EntityBase
 */

namespace EntityBase\AdminSingle;

use WP_Post;
use function EntityBase\Utils\get_entities_for_post;

function setup(): void {
	add_action( 'add_meta_boxes', __NAMESPACE__ . '\\add_entities_meta_box' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\admin_css' );
}

/**
 * Add the entities meta box to the post edit screen.
 */
function add_entities_meta_box(): void {
	$allowed_post_types = apply_filters( 'entitybase_allowed_post_types', [ 'post' ] );

	add_meta_box(
		'entities_meta_box',
		__( 'Entities', 'entity-base' ),
		__NAMESPACE__ . '\\render_entities_meta_box',
		$allowed_post_types,
		'side',
		'default'
	);

	// The comments meta box is shown when comment count is greater than 0.
	// As we're using this field to store connected entity count, force hide it.
	remove_meta_box( 'commentsdiv', 'entity', 'normal' );
}

/**
 * Render the entities meta box.
 *
 * @param WP_Post $post The current post object.
 */
function render_entities_meta_box( WP_Post $post ): void {
	$entities = get_entities_for_post( $post );

	if ( empty( $entities ) ) {
		echo '<p>' . esc_html__( 'No entities found.', 'entity-base' ) . '</p>';
		return;
	}

	echo '<div class="entities-table-wrapper">';
	echo '<table class="widefat">';
	echo '<thead><tr>';
	echo '<th>' . esc_html__( 'Entity', 'entity-base' ) . '</th>';
	echo '<th style="text-align: right;">' . esc_html__( 'Relevance', 'entity-base' ) . '</th>';
	echo '</tr></thead>';
	echo '<tbody>';

	foreach ( $entities as $entity ) {

		/**
		 * Filter the edit link for the entity in the meta box.
		 *
		 * This filter allows modification of the edit link for the entity displayed in the meta box.
		 *
		 * @param string $edit_link The edit link for the entity.
		 * @param WP_Post $entity The entity object.
		 */
		$edit_link = apply_filters( 'entitybase_entities_meta_box_edit_link', get_edit_post_link( $entity->ID ), $entity );

		echo '<tr>';
		echo '<td><a href="' . esc_url( $edit_link ) . '">' . esc_html( $entity->post_title ) . '</a></td>';
		$relevance = number_format( (float) get_post_meta( $post->ID, '_entity_rel_' . $entity->post_name, true ), 2, '.', '' );
		echo '<td style="text-align: right;">' . esc_html( $relevance ) . '</td>';
		echo '</tr>';
	}

	echo '</tbody>';
	echo '</table>';
	echo '</div>';
}

/**
 * Add Admin CSS.
 */
function admin_css(): void {
	ob_start();
	?>
.entities-table-wrapper {
	overflow-x: auto;
	border: 1px solid #ddd;
}

#entities_meta_box table {
	width: 100%;
	border-collapse: collapse;
	margin: -1px 0 0 -1px;
	border-right: none;
	border-bottom: none;
}

#entities_meta_box th,
#entities_meta_box td {
	padding: 8px;
	border: 1px solid #ddd;
}

#entities_meta_box th:last-child,
#entities_meta_box td:last-child {
	border-right: none;
}

#entities_meta_box tr:last-child td {
	border-bottom: none;
}

#entities_meta_box th {
	background-color: #f9f9f9;
}
	<?php

	$admin_css = trim( ob_get_clean() );
	wp_add_inline_style( 'wp-admin', $admin_css );
}
