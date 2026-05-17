<?php
/**
 * Database handler for LinkBack
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LinkBack_Database {

	/**
	 * Create custom tables
	 */
	public static function create_tables() {
		global $wpdb;
		$table_name      = $wpdb->prefix . LINKBACK_TABLE;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			site_name varchar(255) NOT NULL DEFAULT '',
			site_url varchar(500) NOT NULL DEFAULT '',
			backlink_url varchar(500) NOT NULL DEFAULT '',
			email varchar(255) NOT NULL DEFAULT '',
			description text,
			logo_url varchar(500) DEFAULT '',
			twitter_handle varchar(100) DEFAULT '',
			anchor_text varchar(255) DEFAULT '',
			category varchar(100) DEFAULT '',
			hits_in int(11) NOT NULL DEFAULT 0,
			hits_out int(11) NOT NULL DEFAULT 0,
			reciprocal_required tinyint(1) NOT NULL DEFAULT 1,
			reciprocal_status varchar(20) NOT NULL DEFAULT 'ok',
			reciprocal_last_checked datetime DEFAULT NULL,
			reciprocal_fail_since datetime DEFAULT NULL,
			payment_status varchar(20) NOT NULL DEFAULT 'free',
			payment_amount decimal(10,2) NOT NULL DEFAULT 0.00,
			is_featured tinyint(1) NOT NULL DEFAULT 0,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			is_dead tinyint(1) NOT NULL DEFAULT 0,
			dead_fail_count int(11) NOT NULL DEFAULT 0,
			last_dead_check datetime DEFAULT NULL,
			display_order int(11) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY is_active (is_active),
			KEY reciprocal_status (reciprocal_status),
			KEY display_order (display_order),
			KEY hits_in (hits_in),
			KEY category (category),
			KEY is_featured (is_featured),
			KEY is_dead (is_dead)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Stats table for time-series data.
		$stats_table = $wpdb->prefix . 'linkback_stats';
		$sql_stats = "CREATE TABLE IF NOT EXISTS {$stats_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			link_id bigint(20) unsigned NOT NULL,
			stat_date date NOT NULL,
			hits_in int(11) NOT NULL DEFAULT 0,
			hits_out int(11) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY link_date (link_id, stat_date),
			KEY stat_date (stat_date)
		) {$charset_collate};";
		dbDelta( $sql_stats );
	}

	/**
	 * Get table name
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . LINKBACK_TABLE;
	}

	/**
	 * Get stats table name
	 */
	public static function stats_table() {
		global $wpdb;
		return $wpdb->prefix . 'linkback_stats';
	}
}
