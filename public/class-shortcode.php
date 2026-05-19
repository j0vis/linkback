<?php
/**
 * LinkBack Shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LinkBack_Shortcode {

	public function __construct() {
		add_shortcode( 'linkback', array( $this, 'render' ) );
		add_shortcode( 'linkback_signup', array( $this, 'render_signup' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Enqueue frontend styles
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'linkback-public-css', LINKBACK_PLUGIN_URL . 'assets/css/public.css', array(), LINKBACK_VERSION );
	}

	/**
	 * Add dark-mode body class based on plugin setting
	 */
	public function body_class( $classes ) {
		$mode = get_option( 'linkback_dark_mode', 'auto' );
		if ( 'dark' === $mode ) {
			$classes[] = 'linkback-force-dark';
		} elseif ( 'light' === $mode ) {
			$classes[] = 'linkback-force-light';
		}
		return $classes;
	}

	/**
	 * Render the shortcode
	 */
	public function render( $atts ) {
		$atts = shortcode_atts( array(
			'count'         => get_option( 'linkback_max_display', 10 ),
			'order'         => 'display_order',
			'direction'     => 'ASC',
			'show_stats'    => 'false',
			'show_desc'     => 'false',
			'title'         => get_option( 'linkback_default_title', __( 'Partner Links', 'linkback' ) ),
			'category'      => '',
			'stats_period'  => '0',
		), $atts, 'linkback' );

		$count         = absint( $atts['count'] );
		$order_by      = sanitize_text_field( $atts['order'] );
		$order         = sanitize_text_field( $atts['direction'] );
		$show_stats    = 'true' === strtolower( $atts['show_stats'] );
		$show_desc     = 'true' === strtolower( $atts['show_desc'] );
		$title         = sanitize_text_field( $atts['title'] );
		$category      = sanitize_text_field( $atts['category'] );
		$stats_period  = absint( $atts['stats_period'] );

		if ( empty( $title ) || 'Partner Links' === $title ) {
			$title = get_option( 'linkback_default_title', __( 'Partner Links', 'linkback' ) );
		}

		$overrides = array(
			'order_by' => $order_by,
			'order'    => $order,
			'limit'    => $count,
		);
		if ( $category ) {
			$overrides['category'] = $category;
		}

		$links = LinkBack_Link::get_public_links( $overrides );

		if ( empty( $links ) ) {
			return '';
		}

		$list_html = LinkBack_Link::render_list( $links, array(
			'show_stats'   => $show_stats,
			'show_desc'    => $show_desc,
			'stats_period' => $stats_period,
		) );

		$output = '<div class="linkback-shortcode">';
		if ( $title ) {
			$output .= '<h3 class="linkback-title">' . esc_html( $title ) . '</h3>';
		}
		$output .= $list_html;
		$output .= '</div>';

		return $output;
	}

	/**
	 * Render the signup shortcode
	 */
	public function render_signup( $atts ) {
		$error_msg   = '';
		$success_msg = '';

		if ( isset( $_POST['linkback_signup_submit'] ) ) {
			if ( ! isset( $_POST['linkback_signup_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['linkback_signup_nonce'] ) ), 'linkback_signup_action' ) ) {
				$error_msg = __( 'Security verification failed.', 'linkback' );
			} else {
				$raw = array(
					'site_name'    => isset( $_POST['site_name'] ) ? wp_unslash( $_POST['site_name'] ) : '',
					'site_url'     => isset( $_POST['site_url'] ) ? wp_unslash( $_POST['site_url'] ) : '',
					'backlink_url' => isset( $_POST['backlink_url'] ) ? wp_unslash( $_POST['backlink_url'] ) : '',
					'email'        => isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '',
				);
				if ( isset( $_POST['linkback_website'] ) ) {
					$raw['linkback_website'] = wp_unslash( $_POST['linkback_website'] );
				}
				if ( isset( $_POST['g-recaptcha-response'] ) ) {
					$raw['g-recaptcha-response'] = wp_unslash( $_POST['g-recaptcha-response'] );
				}

				$error_msg = LinkBack_Validator::validate_signup( $raw );
				if ( empty( $error_msg ) ) {
					$data = array(
						'site_name'           => sanitize_text_field( wp_unslash( $_POST['site_name'] ) ),
						'site_url'            => esc_url_raw( wp_unslash( $_POST['site_url'] ) ),
						'backlink_url'        => esc_url_raw( wp_unslash( $_POST['backlink_url'] ) ),
						'email'               => sanitize_email( wp_unslash( $_POST['email'] ) ),
						'description'         => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
						'logo_url'            => esc_url_raw( wp_unslash( $_POST['logo_url'] ?? '' ) ),
						'twitter_handle'      => sanitize_text_field( wp_unslash( $_POST['twitter_handle'] ?? '' ) ),
						'anchor_text'         => sanitize_text_field( wp_unslash( $_POST['anchor_text'] ?? '' ) ),
						'category'            => sanitize_text_field( wp_unslash( $_POST['category'] ?? '' ) ),
						'is_active'           => 0,
						'reciprocal_required' => 1,
						'reciprocal_status'   => 'pending',
					);

					$inserted_id = LinkBack_Link::insert( $data );
					if ( $inserted_id ) {
						$success_msg = __( 'Your directory listing has been submitted successfully and is waiting for administrator approval.', 'linkback' );
					} else {
						$error_msg = __( 'An error occurred while submitting your request. Please try again.', 'linkback' );
					}
				}
			}
		}

		return linkback_get_template( 'signup-form.php', array(
			'error_msg'        => $error_msg,
			'success_msg'      => $success_msg,
			'categories'       => LinkBack_Link::get_categories(),
			'enable_captcha'   => get_option( 'linkback_enable_captcha', 0 ),
			'captcha_site_key' => get_option( 'linkback_captcha_site_key', '' ),
		) );
	}
}
