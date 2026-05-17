<?php
/**
 * REST API endpoints for LinkBack
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LinkBack_REST_API {

	const NAMESPACE = 'linkback/v1';

	public static function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/links',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_links' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'count'     => array( 'default' => 10, 'sanitize_callback' => 'absint' ),
					'order'     => array( 'default' => 'display_order', 'sanitize_callback' => 'sanitize_text_field' ),
					'direction' => array( 'default' => 'ASC', 'sanitize_callback' => 'sanitize_text_field' ),
					'category'  => array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/signup',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_signup' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/track/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'track_outgoing' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public static function get_links( WP_REST_Request $request ) {
		$count     = $request->get_param( 'count' );
		$order_by  = $request->get_param( 'order' );
		$order     = $request->get_param( 'direction' );
		$category  = $request->get_param( 'category' );

		$overrides = array(
			'order_by' => $order_by,
			'order'    => $order,
			'limit'    => $count,
		);
		if ( $category ) {
			$overrides['category'] = $category;
		}

		$links = LinkBack_Link::get_public_links( $overrides );
		$data  = array();

		foreach ( $links as $link ) {
			$data[] = array(
				'id'             => (int) $link->id,
				'site_name'      => $link->site_name,
				'site_url'       => $link->site_url,
				'backlink_url'   => $link->backlink_url,
				'logo_url'       => $link->logo_url,
				'description'    => $link->description,
				'category'       => $link->category,
				'hits_in'        => (int) $link->hits_in,
				'hits_out'       => (int) $link->hits_out,
				'is_featured'    => (bool) $link->is_featured,
				'payment_status' => $link->payment_status,
			);
		}

		return new WP_REST_Response( $data, 200 );
	}

	public static function create_signup( WP_REST_Request $request ) {
		$body = $request->get_json_params();

		$raw = array(
			'site_name'    => isset( $body['site_name'] ) ? $body['site_name'] : '',
			'site_url'     => isset( $body['site_url'] ) ? $body['site_url'] : '',
			'backlink_url' => isset( $body['backlink_url'] ) ? $body['backlink_url'] : '',
			'email'        => isset( $body['email'] ) ? $body['email'] : '',
		);

		$error = LinkBack_Validator::validate_signup( $raw );
		if ( $error ) {
			return new WP_REST_Response( array( 'error' => $error ), 400 );
		}

		$inserted_id = LinkBack_Link::insert(
			array(
				'site_name'           => sanitize_text_field( $raw['site_name'] ),
				'site_url'            => esc_url_raw( $raw['site_url'] ),
				'backlink_url'        => esc_url_raw( $raw['backlink_url'] ),
				'email'               => sanitize_email( $raw['email'] ),
				'description'         => isset( $body['description'] ) ? sanitize_textarea_field( $body['description'] ) : '',
				'category'            => isset( $body['category'] ) ? sanitize_text_field( $body['category'] ) : '',
				'is_active'           => 0,
				'reciprocal_required' => 1,
				'reciprocal_status'   => 'pending',
			)
		);

		if ( $inserted_id ) {
			return new WP_REST_Response( array( 'success' => true, 'id' => $inserted_id ), 201 );
		}

		return new WP_REST_Response( array( 'error' => __( 'Failed to create listing.', 'linkback' ) ), 500 );
	}

	public static function track_outgoing( WP_REST_Request $request ) {
		$link_id = absint( $request->get_param( 'id' ) );
		$link    = LinkBack_Link::get( $link_id );

		if ( ! $link || ! $link->is_active ) {
			return new WP_REST_Response( array( 'error' => __( 'Link not found.', 'linkback' ) ), 404 );
		}

		LinkBack_Link::increment_hits_out( $link_id );
		LinkBack_Link::record_stat( $link_id, 'hits_out' );

		return new WP_REST_Response(
			array(
				'success'  => true,
				'site_url' => $link->site_url,
			),
			200
		);
	}
}
