<?php
/**
 * Cron handler for LinkBack
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LinkBack_Cron {

	const HOOK_CHECK = 'linkback_check_reciprocal';
	const HOOK_GRACE = 'linkback_process_grace';
	const HOOK_STATS = 'linkback_aggregate_stats';

	/**
	 * Schedule cron events
	 */
	public static function schedule() {
		$frequency = get_option( 'linkback_check_frequency', 'daily' );

		if ( ! wp_next_scheduled( self::HOOK_CHECK ) ) {
			wp_schedule_event( time(), $frequency, self::HOOK_CHECK );
		}

		if ( ! wp_next_scheduled( self::HOOK_GRACE ) ) {
			wp_schedule_event( time(), 'daily', self::HOOK_GRACE );
		}

		if ( ! wp_next_scheduled( self::HOOK_STATS ) ) {
			wp_schedule_event( time(), 'daily', self::HOOK_STATS );
		}
	}

	/**
	 * Unschedule cron events
	 */
	public static function unschedule() {
		$timestamp = wp_next_scheduled( self::HOOK_CHECK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK_CHECK );
		}

		$timestamp = wp_next_scheduled( self::HOOK_GRACE );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK_GRACE );
		}

		$timestamp = wp_next_scheduled( self::HOOK_STATS );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK_STATS );
		}
	}

	/**
	 * Reschedule with current settings
	 */
	public static function reschedule() {
		self::unschedule();
		self::schedule();
	}

	/**
	 * Stats aggregation hook.
	 * Currently a no-op placeholder for future batch processing or cleanup.
	 */
	public static function run_stats() {
		do_action( 'linkback_stats_aggregated' );
	}
}

// Hook into cron actions using static callables — no global wrapper functions needed.
add_action( LinkBack_Cron::HOOK_CHECK, array( 'LinkBack_Reciprocal_Checker', 'check_all' ) );
add_action( LinkBack_Cron::HOOK_GRACE, array( 'LinkBack_Reciprocal_Checker', 'process_grace_period' ) );
add_action( LinkBack_Cron::HOOK_STATS, array( 'LinkBack_Cron', 'run_stats' ) );
