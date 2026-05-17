<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$default_title                = get_option( 'linkback_default_title', __( 'Partner Links', 'linkback' ) );
$enable_signup_link           = get_option( 'linkback_enable_signup_link', 1 );
$signup_url                   = get_option( 'linkback_signup_url', '' );
$check_frequency              = get_option( 'linkback_check_frequency', 'daily' );
$grace_period_days            = get_option( 'linkback_grace_period_days', 7 );
$default_reciprocal           = get_option( 'linkback_default_reciprocal', 1 );
$max_display                  = get_option( 'linkback_max_display', 10 );
$verification_method          = get_option( 'linkback_verification_method', 'domain' );
$verification_string          = get_option( 'linkback_verification_string', '' );
$notify_on_fail               = get_option( 'linkback_notify_on_fail', 1 );
$notify_on_remove             = get_option( 'linkback_notify_on_remove', 1 );
$notify_on_restore            = get_option( 'linkback_notify_on_restore', 1 );
$admin_email                  = get_option( 'linkback_admin_email', get_option( 'admin_email' ) );
$enable_honeypot              = get_option( 'linkback_enable_honeypot', 1 );
$enable_rate_limit            = get_option( 'linkback_enable_rate_limit', 1 );
$signup_rate_limit            = get_option( 'linkback_signup_rate_limit', 3 );
$signup_rate_limit_window     = get_option( 'linkback_signup_rate_limit_window', 3600 );
$enable_captcha               = get_option( 'linkback_enable_captcha', 0 );
$captcha_site_key             = get_option( 'linkback_captcha_site_key', '' );
$captcha_secret_key           = get_option( 'linkback_captcha_secret_key', '' );
$dark_mode                    = get_option( 'linkback_dark_mode', 'auto' );
$verification_cache_hours     = get_option( 'linkback_verification_cache_hours', 12 );
$dead_threshold_checks        = get_option( 'linkback_dead_threshold_checks', 3 );
$featured_priority            = get_option( 'linkback_featured_priority', 1 );
$email_template_grace         = get_option( 'linkback_email_template_grace', '' );
$email_template_remove        = get_option( 'linkback_email_template_remove', '' );
$email_template_restore       = get_option( 'linkback_email_template_restore', '' );
$email_header_html            = get_option( 'linkback_email_header_html', 0 );
$require_payment              = get_option( 'linkback_require_payment', 0 );
$payment_amount_default       = get_option( 'linkback_payment_amount_default', 0.00 );
?>
<div class="wrap linkback-admin">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Settings saved.', 'linkback' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=linkback-settings' ) ); ?>">
		<?php wp_nonce_field( 'linkback_save_settings', 'linkback_nonce' ); ?>
		<input type="hidden" name="linkback_action" value="save_settings">

		<h2><?php esc_html_e( 'General', 'linkback' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="default_title"><?php esc_html_e( 'Default Directory Title', 'linkback' ); ?></label></th>
				<td>
					<input type="text" name="default_title" id="default_title" class="regular-text" value="<?php echo esc_attr( $default_title ); ?>">
					<p class="description"><?php esc_html_e( 'Default heading text for the links directory (e.g. "Partner Links" or "Our Partners").', 'linkback' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Submit Your Site Button', 'linkback' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="enable_signup_link" id="enable_signup_link" value="1" <?php checked( $enable_signup_link, 1 ); ?>>
						<?php esc_html_e( 'Display a "Submit Your Site" link/button below the partner links directory.', 'linkback' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><label for="signup_url"><?php esc_html_e( 'Signup Page URL', 'linkback' ); ?></label></th>
				<td>
					<input type="url" name="signup_url" id="signup_url" class="regular-text" value="<?php echo esc_url( $signup_url ); ?>">
					<p class="description"><?php esc_html_e( 'Optional. Enter a custom URL for the signup page. If left empty, the plugin will automatically look for a page containing the [linkback_signup] shortcode.', 'linkback' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="dark_mode"><?php esc_html_e( 'Dark Mode', 'linkback' ); ?></label></th>
				<td>
					<select name="dark_mode" id="dark_mode">
						<option value="auto" <?php selected( $dark_mode, 'auto' ); ?>><?php esc_html_e( 'Auto (follows system preference)', 'linkback' ); ?></option>
						<option value="light" <?php selected( $dark_mode, 'light' ); ?>><?php esc_html_e( 'Force Light', 'linkback' ); ?></option>
						<option value="dark" <?php selected( $dark_mode, 'dark' ); ?>><?php esc_html_e( 'Force Dark', 'linkback' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="max_display"><?php esc_html_e( 'Max Links to Display', 'linkback' ); ?></label></th>
				<td>
					<input type="number" name="max_display" id="max_display" class="small-text" value="<?php echo esc_attr( $max_display ); ?>" min="1">
					<p class="description"><?php esc_html_e( 'Default maximum number of links shown in widgets and shortcodes.', 'linkback' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Verification & Anti-Cheat', 'linkback' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="check_frequency"><?php esc_html_e( 'Check Frequency', 'linkback' ); ?></label></th>
				<td>
					<select name="check_frequency" id="check_frequency">
						<option value="hourly" <?php selected( $check_frequency, 'hourly' ); ?>><?php esc_html_e( 'Hourly', 'linkback' ); ?></option>
						<option value="twicedaily" <?php selected( $check_frequency, 'twicedaily' ); ?>><?php esc_html_e( 'Twice Daily', 'linkback' ); ?></option>
						<option value="daily" <?php selected( $check_frequency, 'daily' ); ?>><?php esc_html_e( 'Daily', 'linkback' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'How often to verify partner links.', 'linkback' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="verification_cache_hours"><?php esc_html_e( 'Verification Cache (Hours)', 'linkback' ); ?></label></th>
				<td>
					<input type="number" name="verification_cache_hours" id="verification_cache_hours" class="small-text" value="<?php echo esc_attr( $verification_cache_hours ); ?>" min="0">
					<p class="description"><?php esc_html_e( 'Skip re-checking links that are OK if checked within this many hours. Set 0 to disable caching.', 'linkback' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="grace_period_days"><?php esc_html_e( 'Grace Period (Days)', 'linkback' ); ?></label></th>
				<td>
					<input type="number" name="grace_period_days" id="grace_period_days" class="small-text" value="<?php echo esc_attr( $grace_period_days ); ?>" min="1" max="30">
					<p class="description"><?php esc_html_e( 'Days to wait before removing a listing with a missing partner reference.', 'linkback' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Default Partner Link', 'linkback' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="default_reciprocal" value="1" <?php checked( $default_reciprocal, 1 ); ?>>
						<?php esc_html_e( 'Require partner reference links by default for new listings.', 'linkback' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><label for="verification_method"><?php esc_html_e( 'Verification Method', 'linkback' ); ?></label></th>
				<td>
					<select name="verification_method" id="verification_method">
						<option value="domain" <?php selected( $verification_method, 'domain' ); ?>><?php esc_html_e( 'Domain Name', 'linkback' ); ?></option>
						<option value="string" <?php selected( $verification_method, 'string' ); ?>><?php esc_html_e( 'Custom String', 'linkback' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'What to look for on the partner page.', 'linkback' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="verification_string"><?php esc_html_e( 'Verification String', 'linkback' ); ?></label></th>
				<td>
					<input type="text" name="verification_string" id="verification_string" class="regular-text" value="<?php echo esc_attr( $verification_string ); ?>">
					<p class="description"><?php esc_html_e( 'Custom text to search for if using Custom String mode.', 'linkback' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="dead_threshold_checks"><?php esc_html_e( 'Dead Link Threshold', 'linkback' ); ?></label></th>
				<td>
					<input type="number" name="dead_threshold_checks" id="dead_threshold_checks" class="small-text" value="<?php echo esc_attr( $dead_threshold_checks ); ?>" min="1">
					<p class="description"><?php esc_html_e( 'Number of consecutive failed checks before a link is marked dead.', 'linkback' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Featured Listings', 'linkback' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Featured Priority', 'linkback' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="featured_priority" value="1" <?php checked( $featured_priority, 1 ); ?>>
						<?php esc_html_e( 'Always show featured listings above non-featured ones.', 'linkback' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Spam Protection', 'linkback' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Honeypot Field', 'linkback' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="enable_honeypot" value="1" <?php checked( $enable_honeypot, 1 ); ?>>
						<?php esc_html_e( 'Enable invisible honeypot field on signup form.', 'linkback' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Rate Limiting', 'linkback' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="enable_rate_limit" value="1" <?php checked( $enable_rate_limit, 1 ); ?>>
						<?php esc_html_e( 'Limit submissions per IP.', 'linkback' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><label for="signup_rate_limit"><?php esc_html_e( 'Max Submissions', 'linkback' ); ?></label></th>
				<td>
					<input type="number" name="signup_rate_limit" id="signup_rate_limit" class="small-text" value="<?php echo esc_attr( $signup_rate_limit ); ?>" min="1">
				</td>
			</tr>
			<tr>
				<th><label for="signup_rate_limit_window"><?php esc_html_e( 'Rate Limit Window (seconds)', 'linkback' ); ?></label></th>
				<td>
					<input type="number" name="signup_rate_limit_window" id="signup_rate_limit_window" class="small-text" value="<?php echo esc_attr( $signup_rate_limit_window ); ?>" min="60">
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'reCAPTCHA v2', 'linkback' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="enable_captcha" value="1" <?php checked( $enable_captcha, 1 ); ?>>
						<?php esc_html_e( 'Enable reCAPTCHA on signup form.', 'linkback' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><label for="captcha_site_key"><?php esc_html_e( 'Site Key', 'linkback' ); ?></label></th>
				<td><input type="text" name="captcha_site_key" id="captcha_site_key" class="regular-text" value="<?php echo esc_attr( $captcha_site_key ); ?>"></td>
			</tr>
			<tr>
				<th><label for="captcha_secret_key"><?php esc_html_e( 'Secret Key', 'linkback' ); ?></label></th>
				<td><input type="text" name="captcha_secret_key" id="captcha_secret_key" class="regular-text" value="<?php echo esc_attr( $captcha_secret_key ); ?>"></td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Payment', 'linkback' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Require Payment', 'linkback' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="require_payment" value="1" <?php checked( $require_payment, 1 ); ?>>
						<?php esc_html_e( 'Require payment for new submissions.', 'linkback' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><label for="payment_amount_default"><?php esc_html_e( 'Default Payment Amount', 'linkback' ); ?></label></th>
				<td>
					<input type="number" name="payment_amount_default" id="payment_amount_default" class="small-text" step="0.01" value="<?php echo esc_attr( $payment_amount_default ); ?>">
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Notifications', 'linkback' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Events', 'linkback' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="notify_on_fail" value="1" <?php checked( $notify_on_fail, 1 ); ?>>
						<?php esc_html_e( 'Notify when a partner link goes missing (enters grace period).', 'linkback' ); ?>
					</label>
					<br>
					<label>
						<input type="checkbox" name="notify_on_remove" value="1" <?php checked( $notify_on_remove, 1 ); ?>>
						<?php esc_html_e( 'Notify when a listing is removed after grace period.', 'linkback' ); ?>
					</label>
					<br>
					<label>
						<input type="checkbox" name="notify_on_restore" value="1" <?php checked( $notify_on_restore, 1 ); ?>>
						<?php esc_html_e( 'Notify when a partner link is restored before removal.', 'linkback' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><label for="admin_email"><?php esc_html_e( 'Admin Email', 'linkback' ); ?></label></th>
				<td>
					<input type="email" name="admin_email" id="admin_email" class="regular-text" value="<?php echo esc_attr( $admin_email ); ?>">
					<p class="description"><?php esc_html_e( 'Email address for notifications.', 'linkback' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Email Format', 'linkback' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="email_header_html" value="1" <?php checked( $email_header_html, 1 ); ?>>
						<?php esc_html_e( 'Send emails as HTML instead of plain text.', 'linkback' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><label for="email_template_grace"><?php esc_html_e( 'Grace Period Template', 'linkback' ); ?></label></th>
				<td>
					<textarea name="email_template_grace" id="email_template_grace" class="large-text" rows="4"><?php echo esc_textarea( $email_template_grace ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Leave empty for default. Placeholders: {{site_name}}, {{partner_name}}, {{partner_url}}, {{backlink_url}}, {{grace_days}}', 'linkback' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="email_template_remove"><?php esc_html_e( 'Removal Template', 'linkback' ); ?></label></th>
				<td>
					<textarea name="email_template_remove" id="email_template_remove" class="large-text" rows="4"><?php echo esc_textarea( $email_template_remove ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th><label for="email_template_restore"><?php esc_html_e( 'Restore Template', 'linkback' ); ?></label></th>
				<td>
					<textarea name="email_template_restore" id="email_template_restore" class="large-text" rows="4"><?php echo esc_textarea( $email_template_restore ); ?></textarea>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save Settings', 'linkback' ) ); ?>
	</form>
</div>
