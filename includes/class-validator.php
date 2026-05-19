<?php
/**
 * Shared validation utilities for LinkBack
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LinkBack_Validator {

	/**
	 * Validate signup submission data.
	 *
	 * @param array $data Raw input data.
	 * @return string Empty if valid, error message otherwise.
	 */
	public static function validate_signup( $data ) {
		$site_name    = isset( $data['site_name'] ) ? sanitize_text_field( $data['site_name'] ) : '';
		$site_url     = isset( $data['site_url'] ) ? esc_url_raw( $data['site_url'] ) : '';
		$backlink_url = isset( $data['backlink_url'] ) ? esc_url_raw( $data['backlink_url'] ) : '';
		$email        = isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '';

		if ( empty( $site_name ) || empty( $site_url ) || empty( $backlink_url ) || empty( $email ) ) {
			return __( 'Please fill in all required fields.', 'linkback' );
		}
		if ( ! is_email( $email ) ) {
			return __( 'Please enter a valid email address.', 'linkback' );
		}

		// Honeypot
		if ( get_option( 'linkback_enable_honeypot', 1 ) && ! empty( $data['linkback_website'] ) ) {
			return __( 'Spam detected.', 'linkback' );
		}

		// Rate limit
		if ( get_option( 'linkback_enable_rate_limit', 1 ) ) {
			$ip        = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
			$transient = 'linkback_rate_' . md5( $ip );
			$count     = (int) get_transient( $transient );
			$limit     = absint( get_option( 'linkback_signup_rate_limit', 3 ) );
			$window    = absint( get_option( 'linkback_signup_rate_limit_window', 3600 ) );
			if ( $count >= $limit ) {
				return __( 'Too many submissions. Please try again later.', 'linkback' );
			}
			set_transient( $transient, $count + 1, $window );
		}

		// Duplicate guard
		if ( LinkBack_Link::exists_by_url_or_email( $site_url, $email ) ) {
			return __( 'This site or email is already submitted.', 'linkback' );
		}

		// reCAPTCHA
		if ( get_option( 'linkback_enable_captcha', 0 ) ) {
			$secret = get_option( 'linkback_captcha_secret_key', '' );
			$token  = isset( $data['g-recaptcha-response'] ) ? sanitize_text_field( $data['g-recaptcha-response'] ) : '';
			if ( ! empty( $secret ) && ! empty( $token ) ) {
				$response = wp_remote_post(
					'https://www.google.com/recaptcha/api/siteverify',
					array(
						'body' => array(
							'secret'   => $secret,
							'response' => $token,
							'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
						),
					)
				);
				if ( ! is_wp_error( $response ) ) {
					$result = json_decode( wp_remote_retrieve_body( $response ), true );
					if ( empty( $result['success'] ) ) {
						return __( 'CAPTCHA verification failed.', 'linkback' );
					}
				}
			}
		}

		return '';
	}
}
