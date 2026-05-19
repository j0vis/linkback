<?php
/**
 * Link status transition engine for LinkBack
 *
 * Encapsulates the state-machine logic that governs how a link's
 * reciprocal_status and dead counters change in response to a
 * verification result.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LinkBack_Verification_State {

	/**
	 * Apply the correct state transition for a link based on the
	 * result returned by LinkBack_Reciprocal_Checker::verify_backlink().
	 *
	 * @param object $link   The link row object.
	 * @param array  $result Array with 'found' and 'reachable' keys.
	 */
	public static function apply( $link, $result ) {
		$now = current_time( 'mysql' );

		if ( ! $result['reachable'] ) {
			self::transition_unreachable( $link, $now );
			return;
		}

		// Link is reachable again — clear dead counters if needed.
		if ( $link->is_dead || $link->dead_fail_count > 0 ) {
			self::reset_dead( $link, $now );
		}

		if ( $result['found'] ) {
			self::transition_found( $link, $now );
		} else {
			self::transition_missing( $link, $now );
		}
	}

	/**
	 * Link returned HTTP error or was unreachable.
	 */
	private static function transition_unreachable( $link, $now ) {
		$dead_threshold = absint( get_option( 'linkback_dead_threshold_checks', 3 ) );
		$new_dead_count = (int) $link->dead_fail_count + 1;
		$is_dead        = ( $new_dead_count >= $dead_threshold ) ? 1 : 0;

		LinkBack_Link::update( $link->id, array(
			'dead_fail_count'         => $new_dead_count,
			'last_dead_check'         => $now,
			'is_dead'                 => $is_dead,
			'reciprocal_last_checked' => $now,
		) );
	}

	/**
	 * Reset dead-link counters when a previously dead link becomes reachable.
	 */
	private static function reset_dead( $link, $now ) {
		LinkBack_Link::update( $link->id, array(
			'is_dead'         => 0,
			'dead_fail_count' => 0,
			'last_dead_check' => $now,
		) );
	}

	/**
	 * Backlink was found on the partner page.
	 */
	private static function transition_found( $link, $now ) {
		$was_restored = ( $link->reciprocal_status !== 'ok' );

		if ( $was_restored ) {
			LinkBack_Link::update( $link->id, array(
				'reciprocal_status'       => 'ok',
				'reciprocal_last_checked' => $now,
				'reciprocal_fail_since'   => null,
			) );

			if ( get_option( 'linkback_notify_on_restore', 1 ) ) {
				LinkBack_Mailer::send( $link, 'restore' );
			}
		} else {
			LinkBack_Link::update( $link->id, array(
				'reciprocal_last_checked' => $now,
			) );
		}
	}

	/**
	 * Backlink is missing from the partner page.
	 */
	private static function transition_missing( $link, $now ) {
		if ( $link->reciprocal_status === 'ok' ) {
			// First time missing — enter grace period.
			LinkBack_Link::update( $link->id, array(
				'reciprocal_status'       => 'grace',
				'reciprocal_last_checked' => $now,
				'reciprocal_fail_since'   => $now,
			) );

			if ( get_option( 'linkback_notify_on_fail', 1 ) ) {
				LinkBack_Mailer::send( $link, 'grace' );
			}
		} else {
			LinkBack_Link::update( $link->id, array(
				'reciprocal_last_checked' => $now,
			) );
		}
	}
}
