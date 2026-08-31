<?php
/**
 * Plugin Name: Bulk Post Generator
 * Plugin URI:  https://khi.freepage.cc/wp/
 * Description: Generate 6 blog post drafts at a time from a clean, modern admin dashboard — using built-in templates only, with optional free stock-photo featured images. No API key or external service required.
 * Version:     1.0.0
 * Author:      Your Name
 * Author URI:  https://khi.freepage.cc/wp/
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bulk-post-generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'BPG_VERSION', '1.0.0' );
define( 'BPG_PATH', plugin_dir_path( __FILE__ ) );
define( 'BPG_URL', plugin_dir_url( __FILE__ ) );
define( 'BPG_DEFAULT_BATCH', 6 );

require_once BPG_PATH . 'includes/class-bpg-generator.php';
require_once BPG_PATH . 'includes/class-bpg-admin.php';

/**
 * Boot the plugin.
 */
function bpg_run_plugin() {
	new BPG_Admin();
}
add_action( 'plugins_loaded', 'bpg_run_plugin' );

/**
 * Default options on activation.
 */
register_activation_hook( __FILE__, function () {
	if ( ! get_option( 'bpg_settings' ) ) {
		add_option(
			'bpg_settings',
			array(
				'generate_images'   => false,
				'default_category'  => get_option( 'default_category' ),
				'post_status'       => 'draft',
				'word_count'        => 'medium',
				'batch_size'        => BPG_DEFAULT_BATCH,
				'business_name'     => '',
				'business_type'     => '',
			)
		);
	}
} );
