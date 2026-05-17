<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
$link    = $edit_id ? LinkBack_Link::get( $edit_id ) : null;
$title   = $link ? __( 'Edit Link', 'linkback' ) : __( 'Add New Link', 'linkback' );

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
	'reciprocal_required' => get_option( 'linkback_default_reciprocal', 1 ),
	'payment_status'      => 'free',
	'payment_amount'      => '',
	'is_featured'         => 0,
	'display_order'       => 0,
	'is_active'           => 1,
);

if ( $link ) {
	$data = array(
		'site_name'           => $link->site_name,
		'site_url'            => $link->site_url,
		'backlink_url'        => $link->backlink_url,
		'email'               => $link->email,
		'description'         => $link->description,
		'logo_url'            => $link->logo_url,
		'twitter_handle'      => $link->twitter_handle,
		'anchor_text'         => $link->anchor_text,
		'category'            => $link->category,
		'reciprocal_required' => $link->reciprocal_required,
		'payment_status'      => $link->payment_status,
		'payment_amount'      => $link->payment_amount,
		'is_featured'         => $link->is_featured,
		'display_order'       => $link->display_order,
		'is_active'           => $link->is_active,
	);
} else {
	$data = $defaults;
}

$categories = LinkBack_Link::get_categories();
?>
<div class="wrap linkback-admin">
	<h1><?php echo esc_html( $title ); ?></h1>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=linkback-add' ) ); ?>">
		<?php wp_nonce_field( 'linkback_save_link', 'linkback_nonce' ); ?>
		<input type="hidden" name="linkback_action" value="save_link">
		<?php if ( $edit_id ) : ?>
			<input type="hidden" name="link_id" value="<?php echo absint( $edit_id ); ?>">
		<?php endif; ?>

		<table class="form-table">
			<tr>
				<th><label for="site_name"><?php esc_html_e( 'Site Name', 'linkback' ); ?></label></th>
				<td><input type="text" name="site_name" id="site_name" class="regular-text" value="<?php echo esc_attr( $data['site_name'] ); ?>" required></td>
			</tr>
			<tr>
				<th><label for="site_url"><?php esc_html_e( 'Site URL', 'linkback' ); ?></label></th>
				<td><input type="url" name="site_url" id="site_url" class="regular-text" value="<?php echo esc_attr( $data['site_url'] ); ?>" required></td>
			</tr>
			<tr>
				<th><label for="backlink_url"><?php esc_html_e( 'Partner Page URL', 'linkback' ); ?></label></th>
				<td>
					<input type="url" name="backlink_url" id="backlink_url" class="regular-text" value="<?php echo esc_attr( $data['backlink_url'] ); ?>">
					<p class="description"><?php esc_html_e( 'The page on their site that references your site.', 'linkback' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="email"><?php esc_html_e( 'Contact Email', 'linkback' ); ?></label></th>
				<td><input type="email" name="email" id="email" class="regular-text" value="<?php echo esc_attr( $data['email'] ); ?>"></td>
			</tr>
			<tr>
				<th><label for="description"><?php esc_html_e( 'Description', 'linkback' ); ?></label></th>
				<td><textarea name="description" id="description" class="large-text" rows="3"><?php echo esc_textarea( $data['description'] ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="logo_url"><?php esc_html_e( 'Logo / Favicon URL', 'linkback' ); ?></label></th>
				<td>
					<input type="url" name="logo_url" id="logo_url" class="regular-text" value="<?php echo esc_attr( $data['logo_url'] ); ?>">
					<p class="description"><?php esc_html_e( 'Optional direct image URL. If empty, a screenshot is generated automatically.', 'linkback' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="twitter_handle"><?php esc_html_e( 'Twitter / X Handle', 'linkback' ); ?></label></th>
				<td><input type="text" name="twitter_handle" id="twitter_handle" class="regular-text" value="<?php echo esc_attr( $data['twitter_handle'] ); ?>"></td>
			</tr>
			<tr>
				<th><label for="anchor_text"><?php esc_html_e( 'Preferred Anchor Text', 'linkback' ); ?></label></th>
				<td>
					<input type="text" name="anchor_text" id="anchor_text" class="regular-text" value="<?php echo esc_attr( $data['anchor_text'] ); ?>">
					<p class="description"><?php esc_html_e( 'Suggested anchor text for the partner link.', 'linkback' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="category"><?php esc_html_e( 'Category', 'linkback' ); ?></label></th>
				<td>
					<input type="text" name="category" id="category" class="regular-text" value="<?php echo esc_attr( $data['category'] ); ?>" list="linkback-categories">
					<datalist id="linkback-categories">
						<?php foreach ( $categories as $cat ) : ?>
							<option value="<?php echo esc_attr( $cat ); ?>">
						<?php endforeach; ?>
					</datalist>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Partner Link Required', 'linkback' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="reciprocal_required" value="1" <?php checked( $data['reciprocal_required'], 1 ); ?>>
						<?php esc_html_e( 'Require a partner reference link from this site.', 'linkback' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><label for="payment_status"><?php esc_html_e( 'Payment Status', 'linkback' ); ?></label></th>
				<td>
					<select name="payment_status" id="payment_status">
						<option value="free" <?php selected( $data['payment_status'], 'free' ); ?>><?php esc_html_e( 'Free', 'linkback' ); ?></option>
						<option value="pending" <?php selected( $data['payment_status'], 'pending' ); ?>><?php esc_html_e( 'Pending', 'linkback' ); ?></option>
						<option value="paid" <?php selected( $data['payment_status'], 'paid' ); ?>><?php esc_html_e( 'Paid', 'linkback' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="payment_amount"><?php esc_html_e( 'Payment Amount', 'linkback' ); ?></label></th>
				<td>
					<input type="number" name="payment_amount" id="payment_amount" class="small-text" step="0.01" value="<?php echo esc_attr( $data['payment_amount'] ); ?>">
					<p class="description"><?php esc_html_e( 'Amount paid (if applicable).', 'linkback' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Featured Listing', 'linkback' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="is_featured" value="1" <?php checked( $data['is_featured'], 1 ); ?>>
						<?php esc_html_e( 'Display as featured (badge + priority sorting).', 'linkback' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><label for="display_order"><?php esc_html_e( 'Display Order', 'linkback' ); ?></label></th>
				<td>
					<input type="number" name="display_order" id="display_order" class="small-text" value="<?php echo esc_attr( $data['display_order'] ); ?>">
					<p class="description"><?php esc_html_e( 'Lower numbers appear first.', 'linkback' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Active', 'linkback' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="is_active" value="1" <?php checked( $data['is_active'], 1 ); ?>>
						<?php esc_html_e( 'Show this link on the site.', 'linkback' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<?php submit_button( $link ? __( 'Update Link', 'linkback' ) : __( 'Add Link', 'linkback' ) ); ?>
	</form>
</div>
