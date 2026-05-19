<?php
/**
 * Anti-cheat reciprocal link verification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LinkBack_Reciprocal_Checker {

	/**
	 * Check all links that require a reciprocal link
	 */
	public static function check_all() {
		$links = LinkBack_Link::get_all( array(
			'is_active'           => 1,
			'reciprocal_required' => 1,
			'limit'               => 0,
		) );

		foreach ( $links as $link ) {
			self::check_link( $link );
		}
	}

	/**
	 * Check a single link.
	 *
	 * Orchestrates caching, HTTP verification, and delegates state
	 * transitions to LinkBack_Verification_State.
	 */
	public static function check_link( $link ) {
		if ( empty( $link->backlink_url ) ) {
			return;
		}

		// Verification caching: skip if recently checked and OK.
		$cache_hours = absint( get_option( 'linkback_verification_cache_hours', 12 ) );
		if ( $cache_hours > 0 && 'ok' === $link->reciprocal_status && ! empty( $link->reciprocal_last_checked ) ) {
			$last_check = strtotime( $link->reciprocal_last_checked );
			if ( ( time() - $last_check ) < ( $cache_hours * HOUR_IN_SECONDS ) ) {
				return;
			}
		}

		$result = self::verify_backlink( $link );
		LinkBack_Verification_State::apply( $link, $result );
	}

	/**
	 * Verify backlink on remote page
	 */
	public static function verify_backlink( $link ) {
		$home_url = home_url();
		$domain   = wp_parse_url( $home_url, PHP_URL_HOST );

		$response = wp_remote_get( $link->backlink_url, array(
			'timeout'     => 15,
			'user-agent'  => 'LinkBack Checker/' . LINKBACK_VERSION,
			'sslverify'   => false,
		) );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) >= 400 ) {
			return array(
				'found'      => false,
				'reachable'  => false,
				'warnings'   => array( 'Page unreachable or returned error.' ),
			);
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return array(
				'found'      => false,
				'reachable'  => true,
				'warnings'   => array( 'Empty response body.' ),
			);
		}

		$method   = get_option( 'linkback_verification_method', 'domain' );
		$string   = get_option( 'linkback_verification_string', '' );
		$found    = false;
		$warnings = array();

		if ( 'string' === $method && ! empty( $string ) ) {
			$found = ( false !== stripos( $body, $string ) );
		} else {
			$found = ( false !== stripos( $body, $domain ) );
		}

		// Anti-cheat: check for nofollow
		if ( $found && preg_match( '/<a[^>]*href="[^"]*' . preg_quote( $domain, '/' ) . '[^"]*"[^>]*>/i', $body, $matches ) ) {
			if ( false !== stripos( $matches[0], 'rel=' ) && preg_match( '/rel=["\']?[^"\']*nofollow/i', $matches[0] ) ) {
				$warnings[] = __( 'Partner link has rel="nofollow".', 'linkback' );
			}
		}

		// Anti-cheat: robots.txt check (basic)
		$backlink_host = wp_parse_url( $link->backlink_url, PHP_URL_HOST );
		if ( $found && $backlink_host ) {
			$robots = wp_remote_get( 'https://' . $backlink_host . '/robots.txt', array( 'timeout' => 10 ) );
			if ( ! is_wp_error( $robots ) ) {
				$robots_body = wp_remote_retrieve_body( $robots );
				$backlink_path = wp_parse_url( $link->backlink_url, PHP_URL_PATH );
				if ( $backlink_path && false !== stripos( $robots_body, 'Disallow: ' . $backlink_path ) ) {
					$warnings[] = __( 'Partner page may be blocked by robots.txt.', 'linkback' );
				}
			}
		}

		return array(
			'found'      => $found,
			'reachable'  => true,
			'warnings'   => $warnings,
		);
	}

	/**
	 * Process grace period expirations
	 */
	public static function process_grace_period() {
		global $wpdb;
		$table = LinkBack_Database::table();
		$grace_days = absint( get_option( 'linkback_grace_period_days', 7 ) );
		$cutoff     = date( 'Y-m-d H:i:s', strtotime( "-{$grace_days} days" ) );

		$expired = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE reciprocal_status = 'grace' AND reciprocal_fail_since <= %s AND is_active = 1",
			$cutoff
		) );

		foreach ( $expired as $link ) {
			// Soft disable the link
			LinkBack_Link::update( $link->id, array(
				'is_active'           => 0,
				'reciprocal_status'   => 'removed',
			) );

			if ( get_option( 'linkback_notify_on_remove', 1 ) ) {
				LinkBack_Mailer::send( $link, 'removed' );
			}
		}
	}
}
