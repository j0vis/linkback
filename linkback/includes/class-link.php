<?php
/**
 * Link data model
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LinkBack_Link {

	/**
	 * Get a single link by ID
	 */
	public static function get( $id ) {
		global $wpdb;
		$table = LinkBack_Database::table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
		return $row ? $row : null;
	}

	/**
	 * Build WHERE clauses for queries.
	 *
	 * @param array $filters Key-value pairs of filters.
	 * @return array SQL WHERE clauses.
	 */
	private static function build_where( $filters ) {
		global $wpdb;
		$where = array( '1=1' );

		$map = array(
			'is_active'           => '%d',
			'reciprocal_required' => '%d',
			'reciprocal_status'   => '%s',
			'payment_status'      => '%s',
			'category'            => '%s',
			'is_featured'         => '%d',
			'is_dead'             => '%d',
		);

		foreach ( $map as $key => $format ) {
			if ( isset( $filters[ $key ] ) && null !== $filters[ $key ] ) {
				$where[] = $wpdb->prepare( "{$key} = {$format}", $filters[ $key ] );
			}
		}

		return $where;
	}

	/**
	 * Check whether a column exists on the links table.
	 * Result is cached for the request to avoid repeated SHOW COLUMNS queries.
	 *
	 * @param string $column Column name.
	 * @return bool
	 */
	private static function column_exists( $column ) {
		static $cache = array();
		if ( isset( $cache[ $column ] ) ) {
			return $cache[ $column ];
		}
		global $wpdb;
		$table = LinkBack_Database::table();
		$cache[ $column ] = (bool) $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $column ) );
		return $cache[ $column ];
	}

	/**
	 * Get all links with optional filtering
	 */
	public static function get_all( $args = array() ) {
		global $wpdb;
		$table = LinkBack_Database::table();

		$defaults = array(
			'is_active'           => null,
			'reciprocal_required' => null,
			'reciprocal_status'   => null,
			'payment_status'      => null,
			'category'            => null,
			'is_featured'         => null,
			'order_by'            => 'display_order',
			'order'               => 'ASC',
			'limit'               => 0,
			'offset'              => 0,
		);
		$args = wp_parse_args( $args, $defaults );

		$where = self::build_where( $args );

		$order_by = sanitize_sql_orderby( $args['order_by'] . ' ' . $args['order'] ) ? $args['order_by'] . ' ' . $args['order'] : 'display_order ASC';
		if ( 'random' === strtolower( $args['order_by'] ) ) {
			$order_by = 'RAND()';
		}

		$featured_priority = get_option( 'linkback_featured_priority', 1 );
		if ( $featured_priority && 'random' !== strtolower( $args['order_by'] ) && self::column_exists( 'is_featured' ) ) {
			$order_by = "is_featured DESC, {$order_by}";
		}

		$limit = '';
		if ( $args['limit'] > 0 ) {
			$limit = $wpdb->prepare( 'LIMIT %d, %d', $args['offset'], $args['limit'] );
		}

		$where_sql = implode( ' AND ', $where );
		$sql       = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$order_by} {$limit}";

		return $wpdb->get_results( $sql );
	}

	/**
	 * Fetch public-facing active links with standard frontend arguments.
	 *
	 * @param array $overrides Optional overrides for count, order, category, etc.
	 * @return array Link objects.
	 */
	public static function get_public_links( $overrides = array() ) {
		$defaults = array(
			'is_active' => 1,
			'order_by'  => 'display_order',
			'order'     => 'ASC',
			'limit'     => get_option( 'linkback_max_display', 10 ),
		);
		$args = wp_parse_args( $overrides, $defaults );
		return self::get_all( $args );
	}

	/**
	 * Insert a new link
	 */
	public static function insert( $data ) {
		global $wpdb;
		$table = LinkBack_Database::table();

		$defaults = array(
			'site_name'           => '',
			'site_url'            => '',
			'backlink_url'        => '',
			'email'               => '',
			'description'         => '',
			'logo_url'            => '',
			'twitter_handle'      => '',
			'anchor_text'         => '',
			'category'            => '',
			'hits_in'             => 0,
			'hits_out'            => 0,
			'reciprocal_required' => get_option( 'linkback_default_reciprocal', 1 ),
			'reciprocal_status'   => 'ok',
			'payment_status'      => 'free',
			'payment_amount'      => 0.00,
			'is_featured'         => 0,
			'display_order'       => 0,
			'is_active'           => 1,
		);
		$data = wp_parse_args( $data, $defaults );

		$wpdb->insert( $table, $data );
		return $wpdb->insert_id;
	}

	/**
	 * Update an existing link
	 */
	public static function update( $id, $data ) {
		global $wpdb;
		$table = LinkBack_Database::table();
		return $wpdb->update( $table, $data, array( 'id' => $id ) );
	}

	/**
	 * Delete a link
	 */
	public static function delete( $id ) {
		global $wpdb;
		$table = LinkBack_Database::table();
		return $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Increment hits_out
	 */
	public static function increment_hits_out( $id ) {
		global $wpdb;
		$table = LinkBack_Database::table();
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET hits_out = hits_out + 1 WHERE id = %d", $id ) );
	}

	/**
	 * Increment hits_in
	 */
	public static function increment_hits_in( $id ) {
		global $wpdb;
		$table = LinkBack_Database::table();
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET hits_in = hits_in + 1 WHERE id = %d", $id ) );
	}

	/**
	 * Count total links
	 */
	public static function count( $filters = array() ) {
		global $wpdb;
		$table = LinkBack_Database::table();

		$where = self::build_where( $filters );
		$where_sql = implode( ' AND ', $where );
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}" );
	}

	/**
	 * Check if a URL or email already exists
	 */
	public static function exists_by_url_or_email( $site_url, $email ) {
		global $wpdb;
		$table = LinkBack_Database::table();
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE site_url = %s OR email = %s",
				$site_url,
				$email
			)
		);
		return $count > 0;
	}

	/**
	 * Record a daily stat
	 */
	public static function record_stat( $link_id, $type ) {
		global $wpdb;
		$table = LinkBack_Database::stats_table();
		$date  = current_time( 'Y-m-d' );

		$column = ( 'hits_in' === $type ) ? 'hits_in' : 'hits_out';

		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE link_id = %d AND stat_date = %s",
				$link_id,
				$date
			)
		);

		if ( $exists ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET {$column} = {$column} + 1 WHERE link_id = %d AND stat_date = %s",
					$link_id,
					$date
				)
			);
		} else {
			$wpdb->insert(
				$table,
				array(
					'link_id'  => $link_id,
					'stat_date' => $date,
					$column     => 1,
				)
			);
		}
	}

	/**
	 * Get daily stats for a link over a date range
	 */
	public static function get_stats( $link_id, $days = 30 ) {
		global $wpdb;
		$table = LinkBack_Database::stats_table();
		$since = date( 'Y-m-d', strtotime( "-{$days} days" ) );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT stat_date, hits_in, hits_out FROM {$table} WHERE link_id = %d AND stat_date >= %s ORDER BY stat_date ASC",
				$link_id,
				$since
			)
		);
	}

	/**
	 * Get all distinct categories
	 */
	public static function get_categories() {
		global $wpdb;
		$table = LinkBack_Database::table();

		if ( ! self::column_exists( 'category' ) ) {
			return array();
		}

		$rows = $wpdb->get_col( "SELECT DISTINCT category FROM {$table} WHERE category != '' ORDER BY category ASC" );
		return $rows ? $rows : array();
	}

	/**
	 * Render a list of links (consolidated from shortcode and widget)
	 */
	public static function render_list( $links, $args = array() ) {
		$defaults = array(
			'show_stats'    => false,
			'show_desc'     => false,
			'stats_period'  => 0,
		);
		$args = wp_parse_args( $args, $defaults );

		return linkback_get_template( 'list.php', array_merge( array( 'links' => $links ), $args ) );
	}

	/**
	 * Dynamically locate the page containing the [linkback_signup] shortcode
	 */
	public static function get_signup_url() {
		global $wpdb;
		$post_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT ID FROM $wpdb->posts WHERE post_content LIKE %s AND post_status = 'publish' LIMIT 1",
			'%[linkback_signup]%'
		) );
		if ( $post_id ) {
			return get_permalink( $post_id );
		}
		return '';
	}

	/**
	 * Track incoming hit from referrer URL
	 */
	public static function track_incoming( $referrer ) {
		$referrer_host = wp_parse_url( $referrer, PHP_URL_HOST );
		if ( ! $referrer_host ) {
			return false;
		}

		global $wpdb;
		$table = LinkBack_Database::table();

		// Find a link whose backlink_url or site_url matches the referrer host
		$links = $wpdb->get_results( "SELECT id, backlink_url, site_url FROM {$table} WHERE is_active = 1" );

		foreach ( $links as $link ) {
			$backlink_host = wp_parse_url( $link->backlink_url, PHP_URL_HOST );
			$site_host     = wp_parse_url( $link->site_url, PHP_URL_HOST );

			if ( $referrer_host === $backlink_host || $referrer_host === $site_host ) {
				// Deduplication: one session counts once per link per 30 minutes.
				$cookie_name = 'linkback_hit_in_' . $link->id;
				if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
					self::increment_hits_in( $link->id );
					@setcookie( $cookie_name, '1', time() + 1800, COOKIEPATH, COOKIE_DOMAIN );
				}
				return $link->id;
			}
		}

		return false;
	}
}
