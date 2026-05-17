<?php
/**
 * CSV import/export handler for LinkBack
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LinkBack_CSV_Handler {

	/**
	 * Export all listings to CSV and exit.
	 */
	public static function export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'linkback' ) );
		}

		$links = LinkBack_Link::get_all( array( 'limit' => 0 ) );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=linkback-export-' . date( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'site_name', 'site_url', 'backlink_url', 'email', 'description', 'category', 'reciprocal_status', 'is_active', 'payment_status', 'payment_amount' ) );

		foreach ( $links as $link ) {
			fputcsv( $output, array(
				$link->site_name,
				$link->site_url,
				$link->backlink_url,
				$link->email,
				$link->description,
				$link->category,
				$link->reciprocal_status,
				$link->is_active,
				$link->payment_status,
				$link->payment_amount,
			) );
		}
		fclose( $output );
		exit;
	}

	/**
	 * Import listings from an uploaded CSV file.
	 *
	 * @return int Number of imported rows.
	 * @throws Exception On file read errors.
	 */
	public static function import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'linkback' ) );
		}

		if ( empty( $_FILES['linkback_csv']['tmp_name'] ) ) {
			throw new Exception( 'no_file' );
		}

		$file = sanitize_text_field( wp_unslash( $_FILES['linkback_csv']['tmp_name'] ) );
		$handle = fopen( $file, 'r' );
		if ( false === $handle ) {
			throw new Exception( 'read' );
		}

		$header = fgetcsv( $handle );
		$expected = array( 'site_name', 'site_url', 'backlink_url', 'email', 'description', 'category' );
		if ( ! is_array( $header ) ) {
			fclose( $handle );
			throw new Exception( 'invalid_csv' );
		}

		$map = array();
		foreach ( $expected as $col ) {
			$map[ $col ] = array_search( $col, $header, true );
		}

		$imported = 0;
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			if ( false === $map['site_name'] || false === $map['site_url'] ) {
				continue;
			}
			$site_name = sanitize_text_field( $row[ $map['site_name'] ] ?? '' );
			$site_url  = esc_url_raw( $row[ $map['site_url'] ] ?? '' );
			if ( empty( $site_name ) || empty( $site_url ) ) {
				continue;
			}
			$data = array(
				'site_name'         => $site_name,
				'site_url'          => $site_url,
				'backlink_url'      => false !== $map['backlink_url'] ? esc_url_raw( $row[ $map['backlink_url'] ] ?? '' ) : '',
				'email'             => false !== $map['email'] ? sanitize_email( $row[ $map['email'] ] ?? '' ) : '',
				'description'       => false !== $map['description'] ? sanitize_textarea_field( $row[ $map['description'] ] ?? '' ) : '',
				'category'          => false !== $map['category'] ? sanitize_text_field( $row[ $map['category'] ] ?? '' ) : '',
				'is_active'         => 0,
				'reciprocal_status' => 'pending',
			);
			LinkBack_Link::insert( $data );
			$imported++;
		}
		fclose( $handle );

		return $imported;
	}
}
