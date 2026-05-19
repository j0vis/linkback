<?php
/**
 * Email notification handler for LinkBack
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LinkBack_Mailer {

	/**
	 * Send a notification for a link event.
	 *
	 * @param object $link The link object.
	 * @param string $type grace|removed|restore.
	 */
	public static function send( $link, $type ) {
		$admin_email = get_option( 'linkback_admin_email', get_option( 'admin_email' ) );
		$site_name   = get_bloginfo( 'name' );
		$grace_days  = absint( get_option( 'linkback_grace_period_days', 7 ) );

		$templates = array(
			'grace'   => get_option( 'linkback_email_template_grace', '' ),
			'removed' => get_option( 'linkback_email_template_remove', '' ),
			'restore' => get_option( 'linkback_email_template_restore', '' ),
		);

		$subject = self::get_subject( $type, $site_name, $link->site_name );
		$message = self::get_message( $type, $link, $grace_days );

		if ( ! empty( $templates[ $type ] ) ) {
			$message = str_replace(
				array( '{{site_name}}', '{{partner_name}}', '{{partner_url}}', '{{backlink_url}}', '{{grace_days}}' ),
				array( $site_name, $link->site_name, $link->site_url, $link->backlink_url, $grace_days ),
				$templates[ $type ]
			);
		}

		$use_html = get_option( 'linkback_email_header_html', 0 );
		$headers  = array( 'Content-Type: text/' . ( $use_html ? 'html' : 'plain' ) . '; charset=UTF-8' );

		wp_mail( $admin_email, $subject, $message, $headers );

		if ( ! empty( $link->email ) && is_email( $link->email ) ) {
			wp_mail( $link->email, $subject, $message, $headers );
		}
	}

	/**
	 * Get default subject line.
	 */
	private static function get_subject( $type, $site_name, $partner_name ) {
		$map = array(
			'grace'   => __( '[%1$s] Partner reference link missing: %2$s', 'linkback' ),
			'removed' => __( '[%1$s] Link removed after grace period: %2$s', 'linkback' ),
			'restore' => __( '[%1$s] Partner link restored: %2$s', 'linkback' ),
		);
		return sprintf( $map[ $type ], $site_name, $partner_name );
	}

	/**
	 * Get default message body.
	 */
	private static function get_message( $type, $link, $grace_days ) {
		$map = array(
			'grace'   => sprintf(
				__( "Hello,\n\nThe partner reference link for '%s' is missing from %s.\n\nThey have %d days to restore it before the listing is automatically removed from our directory.\n\n---\nLinkBack System", 'linkback' ),
				$link->site_name,
				$link->backlink_url,
				$grace_days
			),
			'removed' => sprintf(
				__( "Hello,\n\nThe listing for '%s' has been automatically removed because the partner reference link was not restored within the grace period.\n\n---\nLinkBack System", 'linkback' ),
				$link->site_name
			),
			'restore' => sprintf(
				__( "Hello,\n\nGood news! The partner reference link for '%s' has been restored on %s. The listing remains active.\n\n---\nLinkBack System", 'linkback' ),
				$link->site_name,
				$link->backlink_url
			),
		);
		return $map[ $type ];
	}
}
