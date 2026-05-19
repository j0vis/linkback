<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$links = LinkBack_Link::get_all( array( 'limit' => 0 ) );
?>
<div class="wrap linkback-admin">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php if ( isset( $_GET['imported'] ) ) : ?>
		<div class="notice notice-success"><p><?php echo esc_html( sprintf( __( 'Imported %d listings.', 'linkback' ), absint( $_GET['imported'] ) ) ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['error'] ) ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Import failed. Please check your CSV file.', 'linkback' ); ?></p></div>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Export to CSV', 'linkback' ); ?></h2>
	<p><?php esc_html_e( 'Download all listings as a CSV file.', 'linkback' ); ?></p>
	<table class="wp-list-table widefat fixed striped" style="max-width: 800px;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Site Name', 'linkback' ); ?></th>
				<th><?php esc_html_e( 'Site URL', 'linkback' ); ?></th>
				<th><?php esc_html_e( 'Backlink URL', 'linkback' ); ?></th>
				<th><?php esc_html_e( 'Email', 'linkback' ); ?></th>
				<th><?php esc_html_e( 'Description', 'linkback' ); ?></th>
				<th><?php esc_html_e( 'Category', 'linkback' ); ?></th>
				<th><?php esc_html_e( 'Status', 'linkback' ); ?></th>
				<th><?php esc_html_e( 'Active', 'linkback' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $links ) ) : ?>
				<tr><td colspan="8"><?php esc_html_e( 'No listings to export.', 'linkback' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $links as $link ) : ?>
					<tr>
						<td><?php echo esc_html( $link->site_name ); ?></td>
						<td><?php echo esc_html( $link->site_url ); ?></td>
						<td><?php echo esc_html( $link->backlink_url ); ?></td>
						<td><?php echo esc_html( $link->email ); ?></td>
						<td><?php echo esc_html( $link->description ); ?></td>
						<td><?php echo esc_html( $link->category ); ?></td>
						<td><?php echo esc_html( $link->reciprocal_status ); ?></td>
						<td><?php echo $link->is_active ? esc_html__( 'Yes', 'linkback' ) : esc_html__( 'No', 'linkback' ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
	<p>
		<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=linkback-import-export&linkback_action=export_csv' ), 'linkback_export_csv', 'linkback_nonce' ) ); ?>" class="button"><?php esc_html_e( 'Download CSV', 'linkback' ); ?></a>
	</p>

	<h2><?php esc_html_e( 'Import from CSV', 'linkback' ); ?></h2>
	<p><?php esc_html_e( 'Upload a CSV file with the following headers: site_name, site_url, backlink_url, email, description, category', 'linkback' ); ?></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=linkback-import-export' ) ); ?>" enctype="multipart/form-data">
		<?php wp_nonce_field( 'linkback_import_csv', 'linkback_nonce' ); ?>
		<input type="hidden" name="linkback_action" value="import_csv">
		<p>
			<input type="file" name="linkback_csv" accept=".csv">
		</p>
		<?php submit_button( __( 'Import CSV', 'linkback' ) ); ?>
	</form>
</div>
