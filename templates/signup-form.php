<?php
/**
 * LinkBack Signup Form Template
 *
 * Override this template by copying it to yourtheme/linkback/signup-form.php
 *
 * Available variables via $args:
 * @var string $error_msg
 * @var string $success_msg
 * @var array  $categories
 * @var bool   $enable_captcha
 * @var string $captcha_site_key
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$error_msg        = $args['error_msg'] ?? '';
$success_msg      = $args['success_msg'] ?? '';
$categories       = $args['categories'] ?? array();
$enable_captcha   = $args['enable_captcha'] ?? false;
$captcha_site_key = $args['captcha_site_key'] ?? '';
?>
<div class="linkback-signup-card">

	<h2><?php esc_html_e( 'Get Listed in Our Directory', 'linkback' ); ?></h2>
	<p class="linkback-signup-intro"><?php esc_html_e( 'Submit your site to be featured in our partner directory. To keep our network high-quality, we ask that you add a reference to our site on your page first.', 'linkback' ); ?></p>

	<?php if ( $success_msg ) : ?>
		<div class="linkback-alert linkback-alert-success"><?php echo esc_html( $success_msg ); ?></div>
	<?php else : ?>
		<?php if ( $error_msg ) : ?>
			<div class="linkback-alert linkback-alert-error"><?php echo esc_html( $error_msg ); ?></div>
		<?php endif; ?>

		<!-- Dynamic Link Code Box -->
		<div class="linkback-code-box-container">
			<span class="linkback-code-box-header"><?php esc_html_e( 'Add This Link to Your Site', 'linkback' ); ?></span>
			<div class="linkback-code-box-row">
				<input type="text" id="linkback-copy-input" class="linkback-code-box-input" value="<?php echo esc_attr( '<a href="' . esc_url( home_url() ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</a>' ); ?>" readonly>
				<button type="button" class="linkback-code-box-btn" onclick="let input = document.getElementById('linkback-copy-input'); input.select(); document.execCommand('copy'); let btn = this; btn.innerText = '<?php esc_attr_e( 'Copied!', 'linkback' ); ?>'; setTimeout(() => btn.innerText = '<?php esc_attr_e( 'Copy Code', 'linkback' ); ?>', 2000);"><?php esc_html_e( 'Copy Code', 'linkback' ); ?></button>
			</div>
		</div>

		<form method="post" action="">
			<?php wp_nonce_field( 'linkback_signup_action', 'linkback_signup_nonce' ); ?>

			<?php if ( get_option( 'linkback_enable_honeypot', 1 ) ) : ?>
			<div class="linkback-honeypot" aria-hidden="true">
				<input type="text" name="linkback_website" tabindex="-1" autocomplete="off">
			</div>
			<?php endif; ?>

			<div class="linkback-form-group">
				<label for="linkback-site-name"><?php esc_html_e( 'Site Name *', 'linkback' ); ?></label>
				<input type="text" id="linkback-site-name" name="site_name" class="linkback-form-control" value="<?php echo isset( $_POST['site_name'] ) ? esc_attr( wp_unslash( $_POST['site_name'] ) ) : ''; ?>" required>
			</div>

			<div class="linkback-form-group">
				<label for="linkback-site-url"><?php esc_html_e( 'Site URL *', 'linkback' ); ?></label>
				<input type="url" id="linkback-site-url" name="site_url" class="linkback-form-control" value="<?php echo isset( $_POST['site_url'] ) ? esc_url( wp_unslash( $_POST['site_url'] ) ) : ''; ?>" required>
			</div>

			<div class="linkback-form-group">
				<label for="linkback-backlink-url"><?php esc_html_e( 'Page Where Our Link Appears *', 'linkback' ); ?></label>
				<input type="url" id="linkback-backlink-url" name="backlink_url" class="linkback-form-control" value="<?php echo isset( $_POST['backlink_url'] ) ? esc_url( wp_unslash( $_POST['backlink_url'] ) ) : ''; ?>" placeholder="https://example.com/links" required>
			</div>

			<div class="linkback-form-group">
				<label for="linkback-email"><?php esc_html_e( 'Contact Email *', 'linkback' ); ?></label>
				<input type="email" id="linkback-email" name="email" class="linkback-form-control" value="<?php echo isset( $_POST['email'] ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" required>
			</div>

			<div class="linkback-form-group">
				<label for="linkback-description"><?php esc_html_e( 'Description (Optional)', 'linkback' ); ?></label>
				<textarea id="linkback-description" name="description" class="linkback-form-control" rows="3"><?php echo isset( $_POST['description'] ) ? esc_textarea( wp_unslash( $_POST['description'] ) ) : ''; ?></textarea>
			</div>

			<div class="linkback-form-group">
				<label for="linkback-logo-url"><?php esc_html_e( 'Logo / Favicon URL (Optional)', 'linkback' ); ?></label>
				<input type="url" id="linkback-logo-url" name="logo_url" class="linkback-form-control" value="<?php echo isset( $_POST['logo_url'] ) ? esc_url( wp_unslash( $_POST['logo_url'] ) ) : ''; ?>">
			</div>

			<div class="linkback-form-group">
				<label for="linkback-twitter"><?php esc_html_e( 'Twitter / X Handle (Optional)', 'linkback' ); ?></label>
				<input type="text" id="linkback-twitter" name="twitter_handle" class="linkback-form-control" value="<?php echo isset( $_POST['twitter_handle'] ) ? esc_attr( wp_unslash( $_POST['twitter_handle'] ) ) : ''; ?>">
			</div>

			<div class="linkback-form-group">
				<label for="linkback-anchor"><?php esc_html_e( 'Preferred Anchor Text (Optional)', 'linkback' ); ?></label>
				<input type="text" id="linkback-anchor" name="anchor_text" class="linkback-form-control" value="<?php echo isset( $_POST['anchor_text'] ) ? esc_attr( wp_unslash( $_POST['anchor_text'] ) ) : ''; ?>">
			</div>

			<?php if ( ! empty( $categories ) ) : ?>
			<div class="linkback-form-group">
				<label for="linkback-category"><?php esc_html_e( 'Category', 'linkback' ); ?></label>
				<select id="linkback-category" name="category" class="linkback-form-control">
					<option value=""><?php esc_html_e( '— Select —', 'linkback' ); ?></option>
					<?php echo LinkBack_Link::render_category_options( $categories, isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '' ); ?>
				</select>
			</div>
			<?php endif; ?>

			<?php if ( $enable_captcha && ! empty( $captcha_site_key ) ) : ?>
			<div class="linkback-form-group">
				<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $captcha_site_key ); ?>"></div>
				<script src="https://www.google.com/recaptcha/api.js" async defer></script>
			</div>
			<?php endif; ?>

			<button type="submit" name="linkback_signup_submit" class="linkback-btn"><?php esc_html_e( 'Submit Listing', 'linkback' ); ?></button>
		</form>
	<?php endif; ?>
</div>
