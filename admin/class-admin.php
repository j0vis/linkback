<?php
/**
 * Admin interface for LinkBack
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LinkBack_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( 'LinkBack_Admin_Controller', 'handle_actions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
		add_action( 'wp_ajax_linkback_ajax_verify', array( $this, 'ajax_verify' ) );
	}

	/**
	 * Add admin menu pages
	 */
	public function add_menu() {
		add_menu_page(
			__( 'LinkBack', 'linkback' ),
			__( 'LinkBack', 'linkback' ),
			'manage_options',
			'linkback',
			array( $this, 'render_links_page' ),
			'dashicons-admin-links',
			25
		);

		add_submenu_page(
			'linkback',
			__( 'All Listings', 'linkback' ),
			__( 'All Listings', 'linkback' ),
			'manage_options',
			'linkback',
			array( $this, 'render_links_page' )
		);

		add_submenu_page(
			'linkback',
			__( 'Add New Listing', 'linkback' ),
			__( 'Add New', 'linkback' ),
			'manage_options',
			'linkback-add',
			array( $this, 'render_add_edit_page' )
		);

		add_submenu_page(
			'linkback',
			__( 'LinkBack Settings', 'linkback' ),
			__( 'Settings', 'linkback' ),
			'manage_options',
			'linkback-settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'linkback',
			__( 'Import / Export', 'linkback' ),
			__( 'Import / Export', 'linkback' ),
			'manage_options',
			'linkback-import-export',
			array( $this, 'render_import_export_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'linkback' ) ) {
			return;
		}
		wp_enqueue_style( 'linkback-admin-css', LINKBACK_PLUGIN_URL . 'assets/css/admin.css', array(), LINKBACK_VERSION );
		wp_enqueue_script( 'linkback-admin-js', LINKBACK_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), LINKBACK_VERSION, true );
		wp_localize_script( 'linkback-admin-js', 'linkback_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'linkback_ajax_nonce' ),
			'strings'  => array(
				'verifying' => __( 'Verifying...', 'linkback' ),
				'verified'  => __( 'Verified', 'linkback' ),
				'failed'    => __( 'Failed', 'linkback' ),
				'error'     => __( 'Error', 'linkback' ),
			),
		) );
	}

	/**
	 * Render the links list page
	 */
	public function render_links_page() {
		require LINKBACK_PLUGIN_DIR . 'admin/views/links-list.php';
	}

	/**
	 * Render the add/edit page
	 */
	public function render_add_edit_page() {
		require LINKBACK_PLUGIN_DIR . 'admin/views/add-edit-link.php';
	}

	/**
	 * Render the settings page
	 */
	public function render_settings_page() {
		require LINKBACK_PLUGIN_DIR . 'admin/views/settings.php';
	}

	/**
	 * Render import/export page
	 */
	public function render_import_export_page() {
		require LINKBACK_PLUGIN_DIR . 'admin/views/import-export.php';
	}

	/**
	 * Add dashboard widget
	 */
	public function add_dashboard_widget() {
		wp_add_dashboard_widget(
			'linkback_health_widget',
			__( 'LinkBack Health', 'linkback' ),
			array( $this, 'render_dashboard_widget' ),
			null,
			null,
			'normal',
			'high'
		);
	}

	/**
	 * Render dashboard widget content
	 */
	public function render_dashboard_widget() {
		$total     = LinkBack_Link::count();
		$active    = LinkBack_Link::count( array( 'is_active' => 1 ) );
		$grace     = LinkBack_Link::count( array( 'reciprocal_status' => 'grace' ) );
		$pending   = LinkBack_Link::count( array( 'reciprocal_status' => 'pending' ) );
		$dead      = LinkBack_Link::count( array( 'is_dead' => 1 ) );
		$top_links = LinkBack_Link::get_all( array( 'is_active' => 1, 'order_by' => 'hits_out', 'order' => 'DESC', 'limit' => 5 ) );
		?>
		<ul>
			<li><strong><?php esc_html_e( 'Total:', 'linkback' ); ?></strong> <?php echo absint( $total ); ?></li>
			<li><strong><?php esc_html_e( 'Active:', 'linkback' ); ?></strong> <?php echo absint( $active ); ?></li>
			<li><strong><?php esc_html_e( 'Grace Period:', 'linkback' ); ?></strong> <?php echo absint( $grace ); ?></li>
			<li><strong><?php esc_html_e( 'Pending:', 'linkback' ); ?></strong> <?php echo absint( $pending ); ?></li>
			<li><strong><?php esc_html_e( 'Dead:', 'linkback' ); ?></strong> <?php echo absint( $dead ); ?></li>
		</ul>
		<?php if ( ! empty( $top_links ) ) : ?>
			<h4><?php esc_html_e( 'Top Outgoing Clicks', 'linkback' ); ?></h4>
			<ol>
				<?php foreach ( $top_links as $tl ) : ?>
					<li><a href="<?php echo esc_url( $tl->site_url ); ?>" target="_blank"><?php echo esc_html( $tl->site_name ); ?></a> (<?php echo absint( $tl->hits_out ); ?>)</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
		<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=linkback' ) ); ?>"><?php esc_html_e( 'View All Listings →', 'linkback' ); ?></a></p>
		<?php
	}

	/**
	 * AJAX verify a single link
	 */
	public function ajax_verify() {
		check_ajax_referer( 'linkback_ajax_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'linkback' ) );
		}
		$id = ! empty( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( ! $id ) {
			wp_send_json_error( __( 'Invalid ID.', 'linkback' ) );
		}
		$link = LinkBack_Link::get( $id );
		if ( ! $link ) {
			wp_send_json_error( __( 'Link not found.', 'linkback' ) );
		}
		LinkBack_Reciprocal_Checker::check_link( $link );
		$link = LinkBack_Link::get( $id ); // Refresh
		wp_send_json_success( array(
			'status' => $link->reciprocal_status,
			'checked' => $link->reciprocal_last_checked ? human_time_diff( strtotime( $link->reciprocal_last_checked ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'linkback' ) : '—',
		) );
	}
}
