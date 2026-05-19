<?php
/**
 * Plugin Name: LinkBack
 * Plugin URI:  https://xxxpm.com
 * Description: A partner directory plugin for WordPress with link verification, hit ranking, anti-cheat, and payment support.
 * Version:     1.1.3
 * Author:      Prof
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: linkback
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP:      8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prevent double loading/redeclaration conflicts if multiple copies/versions of the plugin are present.
if ( defined( 'LINKBACK_VERSION' ) ) {
	return;
}

define( 'LINKBACK_VERSION', '1.1.3' );
define( 'LINKBACK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LINKBACK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'LINKBACK_TABLE', 'linkback_links' );

require_once LINKBACK_PLUGIN_DIR . 'includes/class-database.php';
require_once LINKBACK_PLUGIN_DIR . 'includes/class-link.php';
require_once LINKBACK_PLUGIN_DIR . 'includes/class-validator.php';
require_once LINKBACK_PLUGIN_DIR . 'includes/class-mailer.php';
require_once LINKBACK_PLUGIN_DIR . 'includes/class-verification-state.php';
require_once LINKBACK_PLUGIN_DIR . 'includes/class-reciprocal-checker.php';
require_once LINKBACK_PLUGIN_DIR . 'includes/class-cron.php';
require_once LINKBACK_PLUGIN_DIR . 'includes/class-csv-handler.php';
require_once LINKBACK_PLUGIN_DIR . 'admin/class-admin-controller.php';
require_once LINKBACK_PLUGIN_DIR . 'admin/class-admin.php';
require_once LINKBACK_PLUGIN_DIR . 'public/class-widget.php';
require_once LINKBACK_PLUGIN_DIR . 'public/class-shortcode.php';
require_once LINKBACK_PLUGIN_DIR . 'includes/class-rest-api.php';

/**
 * Activation hook
 */
register_activation_hook( __FILE__, 'linkback_activate' );
if ( ! function_exists( 'linkback_activate' ) ) {
	function linkback_activate() {
	LinkBack_Database::create_tables();
	LinkBack_Cron::schedule();

	$defaults = array(
		'check_frequency'           => 'daily',
		'grace_period_days'         => 7,
		'default_reciprocal'        => 1,
		'max_display'               => 10,
		'verification_method'       => 'domain',
		'verification_string'       => '',
		'notify_on_fail'            => 1,
		'notify_on_remove'          => 1,
		'notify_on_restore'         => 1,
		'admin_email'               => get_option( 'admin_email' ),
		'default_title'             => __( 'Partner Links', 'linkback' ),
		'enable_signup_link'        => 1,
		'signup_url'                => '',
		'adult_mode'                => 0,
		'enable_honeypot'           => 1,
		'enable_rate_limit'         => 1,
		'signup_rate_limit'         => 3,
		'signup_rate_limit_window'  => 3600,
		'enable_captcha'            => 0,
		'captcha_site_key'          => '',
		'captcha_secret_key'        => '',
		'dark_mode'                 => 'auto',
		'verification_cache_hours'  => 12,
		'dead_threshold_checks'     => 3,
		'featured_priority'         => 1,
		'email_template_grace'      => '',
		'email_template_remove'     => '',
		'email_template_restore'    => '',
		'email_header_html'         => 0,
		'require_payment'           => 0,
		'payment_amount_default'    => 0.00,
	);

	foreach ( $defaults as $key => $value ) {
		if ( false === get_option( 'linkback_' . $key ) ) {
			add_option( 'linkback_' . $key, $value );
		}
	}

	update_option( 'linkback_db_version', LINKBACK_VERSION );

	// Create default signup page if none exists and signup link is enabled.
	if ( get_option( 'linkback_enable_signup_link', 1 ) ) {
		LinkBack_Link::create_default_signup_page();
	}

	flush_rewrite_rules();
	}
}

/**
 * Deactivation hook
 */
register_deactivation_hook( __FILE__, 'linkback_deactivate' );
if ( ! function_exists( 'linkback_deactivate' ) ) {
	function linkback_deactivate() {
	LinkBack_Cron::unschedule();
	flush_rewrite_rules();
	}
}

/**
 * Check for database upgrades on plugin load.
 *
 * This ensures dbDelta() runs when the plugin is updated without
 * requiring manual deactivation / reactivation.
 */
if ( ! function_exists( 'linkback_check_upgrade' ) ) {
	function linkback_check_upgrade() {
	$db_version = get_option( 'linkback_db_version', '1.0.0' );
	if ( version_compare( $db_version, LINKBACK_VERSION, '<' ) ) {
		LinkBack_Database::create_tables();
		update_option( 'linkback_db_version', LINKBACK_VERSION );
	}
	}
}

/**
 * Init plugin
 */
add_action( 'plugins_loaded', 'linkback_init' );
if ( ! function_exists( 'linkback_init' ) ) {
	function linkback_init() {
	linkback_check_upgrade();
	new LinkBack_Admin();
	new LinkBack_Shortcode();
	add_action( 'widgets_init', 'linkback_register_widget' );
	add_action( 'template_redirect', 'linkback_handle_redirect' );
	add_action( 'rest_api_init', array( 'LinkBack_REST_API', 'register_routes' ) );
	}
}

/**
 * Register widget
 */
if ( ! function_exists( 'linkback_register_widget' ) ) {
	function linkback_register_widget() {
	register_widget( 'LinkBack_Widget' );
	}
}

/**
 * Handle click tracking redirect
 *
 * This endpoint is now primarily used by the JS beacon for outgoing
 * hit tracking. The frontend links are direct (SEO-friendly), so
 * regular clicks no longer route through here.
 */
if ( ! function_exists( 'linkback_handle_redirect' ) ) {
	function linkback_handle_redirect() {
	if ( ! isset( $_GET['linkback_redirect'] ) ) {
		return;
	}

	// Verify nonce to prevent trivial hit inflation.
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'linkback_track_out' ) ) {
		wp_safe_redirect( home_url() );
		exit;
	}

	$link_id = absint( $_GET['linkback_redirect'] );
	if ( ! $link_id ) {
		wp_safe_redirect( home_url() );
		exit;
	}

	$link = LinkBack_Link::get( $link_id );
	if ( ! $link || ! $link->is_active ) {
		wp_safe_redirect( home_url() );
		exit;
	}

	LinkBack_Link::increment_hits_out( $link_id );
	LinkBack_Link::record_stat( $link_id, 'hits_out' );

	// If this is a background beacon/image request, return a minimal
	// response so the browser doesn't waste time following a redirect.
	$is_post = ( 'POST' === strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) ) );
	$accept  = isset( $_SERVER['HTTP_ACCEPT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) : '';
	if ( $is_post || false !== stripos( $accept, 'image/' ) ) {
		status_header( 204 );
		exit;
	}

	// Fallback for old cached links or direct bookmarks.
	wp_redirect( esc_url_raw( $link->site_url ), 302 );
	exit;
	}
}

/**
 * Track incoming hits from referrer
 */
add_action( 'wp', 'linkback_track_incoming' );
if ( ! function_exists( 'linkback_track_incoming' ) ) {
	function linkback_track_incoming() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
		return;
	}

	$referrer = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
	$link_id  = LinkBack_Link::track_incoming( $referrer );

	if ( $link_id ) {
		LinkBack_Link::record_stat( $link_id, 'hits_in' );
	}
	}
}

/**
 * Template loader with theme override support.
 *
 * @param string $template Template file name (e.g., 'list.php').
 * @param array  $args     Variables to extract into the template scope.
 * @return string Rendered HTML.
 */
if ( ! function_exists( 'linkback_get_template' ) ) {
	function linkback_get_template( $template, $args = array() ) {
	$template = sanitize_file_name( $template );

	// Theme override.
	$theme_template = get_stylesheet_directory() . '/linkback/' . $template;
	if ( file_exists( $theme_template ) ) {
		$template_path = $theme_template;
	} elseif ( file_exists( get_template_directory() . '/linkback/' . $template ) ) {
		$template_path = get_template_directory() . '/linkback/' . $template;
	} else {
		$template_path = LINKBACK_PLUGIN_DIR . 'templates/' . $template;
	}

	if ( ! file_exists( $template_path ) ) {
		return '';
	}

	ob_start();
	// Templates receive variables via the $args array instead of extract().
	// This prevents accidental variable collisions and makes dependencies explicit.
	include $template_path;
	return ob_get_clean();
	}
}
