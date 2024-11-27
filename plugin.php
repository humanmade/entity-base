<?php
/**
 * Plugin Name: Entity Base
 * Description: Analyse website content for entities to build a database.
 * Author: Human Made Limited
 * Author URI: https://humanmade.com
 * License: GPLv2
 */

namespace EntityBase;

if ( ! class_exists( 'TextRazorSettings' ) ) {
	require_once __DIR__ . '/lib/TextRazor.php';
}

require_once __DIR__ . '/inc/utils.php';
require_once __DIR__ . '/inc/namespace.php';

require_once __DIR__ . '/inc/admin.php';
require_once __DIR__ . '/inc/admin-single.php';
require_once __DIR__ . '/inc/cleanup.php';
require_once __DIR__ . '/inc/cli.php';
require_once __DIR__ . '/inc/export.php';
require_once __DIR__ . '/inc/extract.php';
require_once __DIR__ . '/inc/single.php';

setup();
Admin\setup();
AdminSingle\setup();
Cleanup\setup();
CLI\setup();
Export\setup();
Single\setup();
