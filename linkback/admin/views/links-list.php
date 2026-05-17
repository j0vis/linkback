<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
$search        = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

$args = array(
	'limit' => 0,
	'order_by' => 'display_order',
	'order' => 'ASC',
);

if ( 'active' === $status_filter ) {
	$args['is_active'] = 1;
} elseif ( 'inactive' === $status_filter ) {
	$args['is_active'] = 0;
} elseif ( 'grace' === $status_filter ) {
	$args['reciprocal_status'] = 'grace';
} elseif ( 'pending' === $status_filter ) {
	$args['reciprocal_status'] = 'pending';
} elseif ( 'dead' === $status_filter ) {
	$args['is_dead'] = 1;
}

$links = LinkBack_Link::get_all( $args );

if ( $search ) {
	$links = array_filter( $links, function( $link ) use ( $search ) {
		return false !== stripos( $link->site_name, $search )
			|| false !== stripos( $link->site_url, $search )
			|| false !== stripos( $link->email, $search );
	} );
}

$total      = LinkBack_Link::count();
$active     = LinkBack_Link::count( array( 'is_active' => 1 ) );
$grace      = LinkBack_Link::count( array( 'reciprocal_status' => 'grace' ) );
$pending    = LinkBack_Link::count( array( 'reciprocal_status' => 'pending' ) );
$dead       = LinkBack_Link::count( array( 'is_dead' => 1 ) );
?>
<div class="wrap linkback-admin">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Listing updated.', 'linkback' ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['created'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Listing created.', 'linkback' ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Listing deleted.', 'linkback' ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['verified'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Listing verified.', 'linkback' ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['approved'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Request approved and listing activated.', 'linkback' ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['rejected'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Request denied.', 'linkback' ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['bulk'] ) ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'Bulk action applied.', 'linkback' ); ?></p></div>
	<?php endif; ?>

	<ul class="subsubsub">
		<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=linkback' ) ); ?>" class="<?php echo empty( $status_filter ) ? 'current' : ''; ?>"><?php esc_html_e( 'All', 'linkback' ); ?> <span class="count">(<?php echo absint( $total ); ?>)</span></a> |</li>
		<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=linkback&status=active' ) ); ?>" class="<?php echo 'active' === $status_filter ? 'current' : ''; ?>"><?php esc_html_e( 'Active', 'linkback' ); ?> <span class="count">(<?php echo absint( $active ); ?>)</span></a> |</li>
		<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=linkback&status=grace' ) ); ?>" class="<?php echo 'grace' === $status_filter ? 'current' : ''; ?>"><?php esc_html_e( 'Grace Period', 'linkback' ); ?> <span class="count">(<?php echo absint( $grace ); ?>)</span></a> |</li>
		<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=linkback&status=pending' ) ); ?>" class="<?php echo 'pending' === $status_filter ? 'current' : ''; ?>"><?php esc_html_e( 'Pending Queue', 'linkback' ); ?> <span class="count">(<?php echo absint( $pending ); ?>)</span></a> |</li>
		<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=linkback&status=dead' ) ); ?>" class="<?php echo 'dead' === $status_filter ? 'current' : ''; ?>"><?php esc_html_e( 'Dead', 'linkback' ); ?> <span class="count">(<?php echo absint( $dead ); ?>)</span></a></li>
	</ul>

	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<input type="hidden" name="page" value="linkback">
		<p class="search-box">
			<label class="screen-reader-text" for="linkback-search-input"><?php esc_html_e( 'Search Listings:', 'linkback' ); ?></label>
			<input type="search" id="linkback-search-input" name="s" value="<?php echo esc_attr( $search ); ?>">
			<input type="submit" class="button" value="<?php esc_attr_e( 'Search Listings', 'linkback' ); ?>">
		</p>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=linkback' ) ); ?>">
		<?php wp_nonce_field( 'linkback_bulk_action', 'linkback_nonce' ); ?>
		<input type="hidden" name="linkback_action" value="bulk_action">

		<div class="tablenav top">
			<div class="alignleft actions">
				<select name="bulk_action">
					<option value=""><?php esc_html_e( 'Bulk Actions', 'linkback' ); ?></option>
					<option value="approve"><?php esc_html_e( 'Approve (Allow)', 'linkback' ); ?></option>
					<option value="reject"><?php esc_html_e( 'Reject (Deny)', 'linkback' ); ?></option>
					<option value="activate"><?php esc_html_e( 'Activate', 'linkback' ); ?></option>
					<option value="deactivate"><?php esc_html_e( 'Deactivate', 'linkback' ); ?></option>
					<option value="verify"><?php esc_html_e( 'Verify Selected', 'linkback' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'linkback' ); ?></option>
				</select>
				<input type="submit" class="button action" value="<?php esc_attr_e( 'Apply', 'linkback' ); ?>">
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=linkback-add' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Add New Listing', 'linkback' ); ?></a>
		</div>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<td class="manage-column column-cb check-column">
						<input type="checkbox" id="cb-select-all-1">
					</td>
					<th><?php esc_html_e( 'Site', 'linkback' ); ?></th>
					<th><?php esc_html_e( 'Status', 'linkback' ); ?></th>
					<th><?php esc_html_e( 'Partner Link', 'linkback' ); ?></th>
					<th><?php esc_html_e( 'Payment', 'linkback' ); ?></th>
					<th><?php esc_html_e( 'Hits In', 'linkback' ); ?></th>
					<th><?php esc_html_e( 'Last Checked', 'linkback' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'linkback' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $links ) ) : ?>
					<tr>
						<td colspan="8"><?php esc_html_e( 'No listings found.', 'linkback' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $links as $link ) : ?>
						<tr data-link-id="<?php echo absint( $link->id ); ?>">
							<th class="check-column">
								<input type="checkbox" name="link_ids[]" value="<?php echo absint( $link->id ); ?>">
							</th>
							<td>
								<strong><a href="<?php echo esc_url( $link->site_url ); ?>" target="_blank"><?php echo esc_html( $link->site_name ); ?></a></strong>
								<?php if ( $link->is_featured ) : ?>
									<span class="linkback-status linkback-status-featured"><?php esc_html_e( 'Featured', 'linkback' ); ?></span>
								<?php endif; ?>
								<?php if ( $link->is_dead ) : ?>
									<span class="linkback-status linkback-status-dead"><?php esc_html_e( 'Dead', 'linkback' ); ?></span>
								<?php endif; ?>
								<br><small><?php echo esc_html( $link->site_url ); ?></small>
								<?php if ( ! empty( $link->category ) ) : ?>
									<br><small><?php esc_html_e( 'Category:', 'linkback' ); ?> <?php echo esc_html( $link->category ); ?></small>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( $link->is_active ) : ?>
									<span class="linkback-status linkback-status-active"><?php esc_html_e( 'Active', 'linkback' ); ?></span>
								<?php else : ?>
									<span class="linkback-status linkback-status-inactive"><?php esc_html_e( 'Inactive', 'linkback' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( $link->reciprocal_required ) : ?>
									<?php if ( 'ok' === $link->reciprocal_status ) : ?>
										<span class="linkback-status linkback-status-ok"><?php esc_html_e( 'OK', 'linkback' ); ?></span>
									<?php elseif ( 'grace' === $link->reciprocal_status ) : ?>
										<span class="linkback-status linkback-status-grace" title="<?php echo esc_attr( $link->reciprocal_fail_since ); ?>"><?php esc_html_e( 'Grace', 'linkback' ); ?></span>
									<?php elseif ( 'pending' === $link->reciprocal_status ) : ?>
										<span class="linkback-status linkback-status-pending"><?php esc_html_e( 'Pending', 'linkback' ); ?></span>
									<?php else : ?>
										<span class="linkback-status linkback-status-missing"><?php esc_html_e( 'Missing', 'linkback' ); ?></span>
									<?php endif; ?>
								<?php else : ?>
									<em><?php esc_html_e( 'Not Required', 'linkback' ); ?></em>
								<?php endif; ?>
							</td>
							<td>
								<?php echo esc_html( ucfirst( $link->payment_status ) ); ?>
								<?php if ( $link->payment_amount > 0 ) : ?>
									<br><small>$<?php echo number_format( $link->payment_amount, 2 ); ?></small>
								<?php endif; ?>
							</td>
							<td>
								<?php echo absint( $link->hits_in ); ?>
							</td>
							<td>
								<span class="linkback-last-checked"><?php echo $link->reciprocal_last_checked ? esc_html( human_time_diff( strtotime( $link->reciprocal_last_checked ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'linkback' ) ) : '—'; ?></span>
							</td>
							<td>
								<?php if ( 'pending' === $link->reciprocal_status ) : ?>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=linkback&linkback_action=approve_link&id=' . $link->id ), 'linkback_approve_link', 'linkback_nonce' ) ); ?>" class="button button-small button-primary" style="margin-right: 4px;"><?php esc_html_e( 'Allow (Approve)', 'linkback' ); ?></a>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=linkback&linkback_action=reject_link&id=' . $link->id ), 'linkback_reject_link', 'linkback_nonce' ) ); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to deny this request?', 'linkback' ); ?>');"><?php esc_html_e( 'Deny (Reject)', 'linkback' ); ?></a>
								<?php else : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=linkback-add&edit=' . $link->id ) ); ?>"><?php esc_html_e( 'Edit', 'linkback' ); ?></a> |
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=linkback&linkback_action=verify_link&id=' . $link->id ), 'linkback_verify_link', 'linkback_nonce' ) ); ?>"><?php esc_html_e( 'Verify Now', 'linkback' ); ?></a> |
									<button type="button" class="button-link linkback-ajax-verify" data-id="<?php echo absint( $link->id ); ?>"><?php esc_html_e( 'AJAX Verify', 'linkback' ); ?></button> |
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=linkback&linkback_action=delete_link&id=' . $link->id ), 'linkback_delete_link', 'linkback_nonce' ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure?', 'linkback' ); ?>');"><?php esc_html_e( 'Delete', 'linkback' ); ?></a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</form>
</div>
