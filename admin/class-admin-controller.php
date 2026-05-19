<?php
/**
 * Admin action controller for LinkBack
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LinkBack_Admin_Controller {

	/**
	 * Handle form submissions and actions
	 */
	public static function handle_actions() {
		if ( ! isset( $_REQUEST['linkback_action'] ) ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_REQUEST['linkback_action'] ) );

		$nonce_map = array(
			'approve_link'   => 'linkback_approve_link',
			'reject_link'    => 'linkback_reject_link',
			'save_link'      => 'linkback_save_link',
			'delete_link'    => 'linkback_delete_link',
			'bulk_action'    => 'linkback_bulk_action',
			'verify_link'    => 'linkback_verify_link',
			'save_settings'  => 'linkback_save_settings',
			'import_csv'     => 'linkback_import_csv',
			'export_csv'     => 'linkback_export_csv',
		);

		if ( isset( $nonce_map[ $action ] ) && check_admin_referer( $nonce_map[ $action ], 'linkback_nonce' ) ) {
			$method = $action;
			if ( method_exists( __CLASS__, $method ) ) {
				self::$method();
			}
		}
	}

	/**
	 * Save a link
	 */
	private static function save_link() {
		$id = ! empty( $_POST['link_id'] ) ? absint( $_POST['link_id'] ) : 0;

		$data = array(
			'site_name'           => isset( $_POST['site_name'] ) ? sanitize_text_field( wp_unslash( $_POST['site_name'] ) ) : '',
			'site_url'            => isset( $_POST['site_url'] ) ? esc_url_raw( wp_unslash( $_POST['site_url'] ) ) : '',
			'backlink_url'        => isset( $_POST['backlink_url'] ) ? esc_url_raw( wp_unslash( $_POST['backlink_url'] ) ) : '',
			'email'               => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
			'description'         => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
			'logo_url'            => isset( $_POST['logo_url'] ) ? esc_url_raw( wp_unslash( $_POST['logo_url'] ) ) : '',
			'twitter_handle'      => isset( $_POST['twitter_handle'] ) ? sanitize_text_field( wp_unslash( $_POST['twitter_handle'] ) ) : '',
			'anchor_text'         => isset( $_POST['anchor_text'] ) ? sanitize_text_field( wp_unslash( $_POST['anchor_text'] ) ) : '',
			'category'            => isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '',
			'reciprocal_required' => isset( $_POST['reciprocal_required'] ) ? 1 : 0,
			'payment_status'      => isset( $_POST['payment_status'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_status'] ) ) : 'free',
			'payment_amount'      => isset( $_POST['payment_amount'] ) ? floatval( wp_unslash( $_POST['payment_amount'] ) ) : 0.00,
			'is_featured'         => isset( $_POST['is_featured'] ) ? 1 : 0,
			'display_order'       => isset( $_POST['display_order'] ) ? absint( wp_unslash( $_POST['display_order'] ) ) : 0,
			'is_active'           => isset( $_POST['is_active'] ) ? 1 : 0,
		);

		if ( empty( $data['site_name'] ) || empty( $data['site_url'] ) ) {
			wp_redirect( admin_url( 'admin.php?page=linkback-add&error=1' ) );
			exit;
		}

		if ( $id ) {
			LinkBack_Link::update( $id, $data );
			$redirect = admin_url( 'admin.php?page=linkback&updated=1' );
		} else {
			$id = LinkBack_Link::insert( $data );
			$redirect = admin_url( 'admin.php?page=linkback&created=1' );
		}

		wp_redirect( $redirect );
		exit;
	}

	/**
	 * Delete a link
	 */
	private static function delete_link() {
		$id = ! empty( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( $id ) {
			LinkBack_Link::delete( $id );
		}
		wp_redirect( admin_url( 'admin.php?page=linkback&deleted=1' ) );
		exit;
	}

	/**
	 * Bulk actions
	 */
	private static function bulk_action() {
		$ids    = isset( $_POST['link_ids'] ) ? array_map( 'intval', wp_unslash( $_POST['link_ids'] ) ) : array();
		$bulk   = isset( $_POST['bulk_action'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) ) : '';

		if ( empty( $ids ) || empty( $bulk ) ) {
			wp_redirect( admin_url( 'admin.php?page=linkback' ) );
			exit;
		}

		foreach ( $ids as $id ) {
			if ( 'delete' === $bulk || 'reject' === $bulk ) {
				LinkBack_Link::delete( $id );
			} elseif ( 'activate' === $bulk ) {
				LinkBack_Link::update( $id, array( 'is_active' => 1 ) );
			} elseif ( 'deactivate' === $bulk ) {
				LinkBack_Link::update( $id, array( 'is_active' => 0 ) );
			} elseif ( 'approve' === $bulk ) {
				self::do_approve( $id );
			} elseif ( 'verify' === $bulk ) {
				self::do_verify( $id );
			}
		}

		wp_redirect( admin_url( 'admin.php?page=linkback&bulk=1' ) );
		exit;
	}

	/**
	 * Approve a pending link request
	 */
	private static function approve_link() {
		$id = ! empty( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( $id ) {
			self::do_approve( $id );
		}
		wp_redirect( admin_url( 'admin.php?page=linkback&approved=1' ) );
		exit;
	}

	/**
	 * Reject/Deny a pending link request
	 */
	private static function reject_link() {
		$id = ! empty( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( $id ) {
			LinkBack_Link::delete( $id );
		}
		wp_redirect( admin_url( 'admin.php?page=linkback&rejected=1' ) );
		exit;
	}

	/**
	 * Verify a single link now
	 */
	private static function verify_link() {
		$id = ! empty( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( $id ) {
			self::do_verify( $id );
		}
		wp_redirect( admin_url( 'admin.php?page=linkback&verified=1' ) );
		exit;
	}

	/**
	 * Shared approval logic
	 */
	private static function do_approve( $id ) {
		LinkBack_Link::update( $id, array(
			'is_active'         => 1,
			'reciprocal_status' => 'ok',
		) );
		$link = LinkBack_Link::get( $id );
		if ( $link ) {
			LinkBack_Reciprocal_Checker::check_link( $link );
		}
	}

	/**
	 * Shared verification logic
	 */
	private static function do_verify( $id ) {
		$link = LinkBack_Link::get( $id );
		if ( $link ) {
			LinkBack_Reciprocal_Checker::check_link( $link );
		}
	}

	/**
	 * Save settings
	 */
	private static function save_settings() {
		$settings = array(
			// text
			'default_title'               => array( 'type' => 'text', 'default' => __( 'Partner Links', 'linkback' ) ),
			'signup_url'                  => array( 'type' => 'text', 'default' => '' ),
			'check_frequency'             => array( 'type' => 'text', 'default' => 'daily' ),
			'verification_method'         => array( 'type' => 'text', 'default' => 'domain' ),
			'verification_string'         => array( 'type' => 'text', 'default' => '' ),
			'admin_email'                 => array( 'type' => 'email', 'default' => get_option( 'admin_email' ) ),
			'dark_mode'                   => array( 'type' => 'text', 'default' => 'auto' ),
			'captcha_site_key'            => array( 'type' => 'text', 'default' => '' ),
			'captcha_secret_key'          => array( 'type' => 'text', 'default' => '' ),
			// number
			'grace_period_days'           => array( 'type' => 'int', 'default' => 7 ),
			'max_display'                 => array( 'type' => 'int', 'default' => 10 ),
			'signup_rate_limit'           => array( 'type' => 'int', 'default' => 3 ),
			'signup_rate_limit_window'    => array( 'type' => 'int', 'default' => 3600 ),
			'verification_cache_hours'    => array( 'type' => 'int', 'default' => 12 ),
			'dead_threshold_checks'       => array( 'type' => 'int', 'default' => 3 ),
			'payment_amount_default'      => array( 'type' => 'float', 'default' => 0.00 ),
			// textarea
			'email_template_grace'        => array( 'type' => 'textarea', 'default' => '' ),
			'email_template_remove'       => array( 'type' => 'textarea', 'default' => '' ),
			'email_template_restore'      => array( 'type' => 'textarea', 'default' => '' ),
			// bool
			'enable_signup_link'          => array( 'type' => 'bool' ),
			'adult_mode'                  => array( 'type' => 'bool' ),
			'default_reciprocal'          => array( 'type' => 'bool' ),
			'notify_on_fail'              => array( 'type' => 'bool' ),
			'notify_on_remove'            => array( 'type' => 'bool' ),
			'notify_on_restore'           => array( 'type' => 'bool' ),
			'enable_honeypot'             => array( 'type' => 'bool' ),
			'enable_rate_limit'           => array( 'type' => 'bool' ),
			'enable_captcha'              => array( 'type' => 'bool' ),
			'featured_priority'           => array( 'type' => 'bool' ),
			'email_header_html'           => array( 'type' => 'bool' ),
			'require_payment'             => array( 'type' => 'bool' ),
		);

		foreach ( $settings as $key => $config ) {
			$option_name = 'linkback_' . $key;
			$post_key    = $key;

			switch ( $config['type'] ) {
				case 'text':
					$value = sanitize_text_field( wp_unslash( $_POST[ $post_key ] ?? $config['default'] ) );
					break;
				case 'email':
					$value = sanitize_email( wp_unslash( $_POST[ $post_key ] ?? $config['default'] ) );
					break;
				case 'int':
					$value = absint( $_POST[ $post_key ] ?? $config['default'] );
					break;
				case 'float':
					$value = floatval( wp_unslash( $_POST[ $post_key ] ?? $config['default'] ) );
					break;
				case 'textarea':
					$value = sanitize_textarea_field( wp_unslash( $_POST[ $post_key ] ?? $config['default'] ) );
					break;
				case 'bool':
					$value = isset( $_POST[ $post_key ] ) ? 1 : 0;
					break;
				default:
					$value = sanitize_text_field( wp_unslash( $_POST[ $post_key ] ?? '' ) );
			}

			update_option( $option_name, $value );
		}

		// Create default signup page if none exists and signup link is enabled.
		if ( get_option( 'linkback_enable_signup_link', 1 ) ) {
			LinkBack_Link::create_default_signup_page();
		}

		LinkBack_Cron::reschedule();

		wp_redirect( admin_url( 'admin.php?page=linkback-settings&saved=1' ) );
		exit;
	}

	/**
	 * Export CSV
	 */
	private static function export_csv() {
		LinkBack_CSV_Handler::export();
	}

	/**
	 * Import CSV
	 */
	private static function import_csv() {
		try {
			$imported = LinkBack_CSV_Handler::import();
			wp_redirect( admin_url( 'admin.php?page=linkback-import-export&imported=' . $imported ) );
		} catch ( Exception $e ) {
			wp_redirect( admin_url( 'admin.php?page=linkback-import-export&error=' . $e->getMessage() ) );
		}
		exit;
	}
}
