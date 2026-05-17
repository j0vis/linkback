<?php
/**
 * LinkBack Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LinkBack_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'linkback_widget',
			__( 'LinkBack Links', 'linkback' ),
			array( 'description' => __( 'Display your partner directory listings.', 'linkback' ) )
		);
	}

	public function widget( $args, $instance ) {
		$title      = ( ! empty( $instance['title'] ) && 'Partner Links' !== $instance['title'] ) ? $instance['title'] : get_option( 'linkback_default_title', __( 'Partner Links', 'linkback' ) );
		$count      = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : get_option( 'linkback_max_display', 10 );
		$order_by   = ! empty( $instance['order_by'] ) ? sanitize_text_field( $instance['order_by'] ) : 'display_order';
		$order      = ! empty( $instance['order'] ) ? sanitize_text_field( $instance['order'] ) : 'ASC';
		$show_stats = ! empty( $instance['show_stats'] ) ? 1 : 0;
		$category   = ! empty( $instance['category'] ) ? sanitize_text_field( $instance['category'] ) : '';

		$overrides = array(
			'order_by' => $order_by,
			'order'    => $order,
			'limit'    => $count,
		);
		if ( $category ) {
			$overrides['category'] = $category;
		}

		$links = LinkBack_Link::get_public_links( $overrides );

		if ( empty( $links ) ) {
			return;
		}

		echo $args['before_widget'];
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		echo LinkBack_Link::render_list( $links, array(
			'show_stats' => $show_stats,
			'show_desc'  => true,
		) );

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title      = ( ! empty( $instance['title'] ) && 'Partner Links' !== $instance['title'] ) ? $instance['title'] : get_option( 'linkback_default_title', __( 'Partner Links', 'linkback' ) );
		$count      = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : get_option( 'linkback_max_display', 10 );
		$order_by   = ! empty( $instance['order_by'] ) ? $instance['order_by'] : 'display_order';
		$order      = ! empty( $instance['order'] ) ? $instance['order'] : 'ASC';
		$show_stats = ! empty( $instance['show_stats'] ) ? 1 : 0;
		$category   = ! empty( $instance['category'] ) ? $instance['category'] : '';
		$categories = LinkBack_Link::get_categories();
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'linkback' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_html_e( 'Number of links:', 'linkback' ); ?></label>
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="number" step="1" min="1" value="<?php echo esc_attr( $count ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'order_by' ) ); ?>"><?php esc_html_e( 'Order By:', 'linkback' ); ?></label>
			<select id="<?php echo esc_attr( $this->get_field_id( 'order_by' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'order_by' ) ); ?>">
				<option value="display_order" <?php selected( $order_by, 'display_order' ); ?>><?php esc_html_e( 'Display Order', 'linkback' ); ?></option>
				<option value="hits_in" <?php selected( $order_by, 'hits_in' ); ?>><?php esc_html_e( 'Incoming Hits', 'linkback' ); ?></option>
				<option value="hits_out" <?php selected( $order_by, 'hits_out' ); ?>><?php esc_html_e( 'Outgoing Hits', 'linkback' ); ?></option>
				<option value="created_at" <?php selected( $order_by, 'created_at' ); ?>><?php esc_html_e( 'Date Added', 'linkback' ); ?></option>
				<option value="random" <?php selected( $order_by, 'random' ); ?>><?php esc_html_e( 'Random', 'linkback' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>"><?php esc_html_e( 'Order:', 'linkback' ); ?></label>
			<select id="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'order' ) ); ?>">
				<option value="ASC" <?php selected( $order, 'ASC' ); ?>><?php esc_html_e( 'Ascending', 'linkback' ); ?></option>
				<option value="DESC" <?php selected( $order, 'DESC' ); ?>><?php esc_html_e( 'Descending', 'linkback' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>"><?php esc_html_e( 'Category:', 'linkback' ); ?></label>
			<select id="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'category' ) ); ?>">
				<option value=""><?php esc_html_e( 'All Categories', 'linkback' ); ?></option>
				<?php echo LinkBack_Link::render_category_options( $categories, $category ); ?>
			</select>
		</p>
		<p>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_stats' ) ); ?>" value="1" <?php checked( $show_stats, 1 ); ?>>
				<?php esc_html_e( 'Show hit counts', 'linkback' ); ?>
			</label>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title']      = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['count']      = ( ! empty( $new_instance['count'] ) ) ? absint( $new_instance['count'] ) : 10;
		$instance['order_by']   = ( ! empty( $new_instance['order_by'] ) ) ? sanitize_text_field( $new_instance['order_by'] ) : 'display_order';
		$instance['order']      = ( ! empty( $new_instance['order'] ) ) ? sanitize_text_field( $new_instance['order'] ) : 'ASC';
		$instance['show_stats'] = ! empty( $new_instance['show_stats'] ) ? 1 : 0;
		$instance['category']   = ! empty( $new_instance['category'] ) ? sanitize_text_field( $new_instance['category'] ) : '';
		return $instance;
	}
}
