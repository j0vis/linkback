<?php
/**
 * LinkBack List Template
 *
 * Override this template by copying it to yourtheme/linkback/list.php
 *
 * Available variables via $args:
 * @var array $links Array of link objects.
 * @var array $args  Display arguments (show_stats, show_desc, stats_period).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$links        = $args['links'] ?? array();
$show_stats   = ! empty( $args['show_stats'] );
$show_desc    = ! empty( $args['show_desc'] );
$stats_period = ! empty( $args['stats_period'] ) ? absint( $args['stats_period'] ) : 0;
?>
<ul class="linkback-list">
	<?php foreach ( $links as $link ) :
		$first_char     = mb_strtoupper( mb_substr( $link->site_name, 0, 1 ) );
		$gradient_index = ( abs( crc32( $link->site_name ) ) % 6 ) + 1;
		$featured_badge = $link->is_featured ? '<span class="linkback-featured-badge">' . esc_html__( 'Featured', 'linkback' ) . '</span>' : '';
		$period_hits_in = 0;
		if ( $stats_period > 0 ) {
			$stats = LinkBack_Link::get_stats( $link->id, $stats_period );
			foreach ( $stats as $stat ) {
				$period_hits_in += (int) $stat->hits_in;
			}
		}
		?>
		<li class="linkback-item <?php echo $link->is_featured ? 'linkback-item-featured' : ''; ?>">
			<a class="linkback-card-link" href="<?php echo esc_url( $link->site_url ); ?>" target="_blank" rel="noopener nofollow" data-linkback-id="<?php echo absint( $link->id ); ?>">
				<div class="linkback-avatar">
					<?php if ( ! empty( $link->logo_url ) ) : ?>
						<img class="linkback-screenshot" src="<?php echo esc_url( $link->logo_url ); ?>" alt="" />
					<?php else : ?>
						<img class="linkback-screenshot" src="<?php echo esc_url( 'https://s.wordpress.com/mshots/v1/' . rawurlencode( $link->site_url ) . '?w=120' ); ?>" alt="" />
					<?php endif; ?>
				</div>
				<div class="linkback-content">
					<div class="linkback-header">
						<span class="linkback-site-name"><?php echo esc_html( $link->site_name ); ?></span>
						<?php if ( $show_stats ) : ?>
							<span class="linkback-stats-badge" title="<?php esc_attr_e( 'Incoming Hits', 'linkback' ); ?>">
								<span class="linkback-stats-icon">⚡</span>
								<span class="linkback-stats-count"><?php echo $stats_period > 0 ? absint( $period_hits_in ) : absint( $link->hits_in ); ?></span>
							</span>
						<?php endif; ?>
					</div>
					<?php if ( $show_desc && ! empty( $link->description ) ) : ?>
						<p class="linkback-desc"><?php echo esc_html( $link->description ); ?></p>
					<?php endif; ?>
					<?php if ( $link->is_featured ) : ?>
						<div class="linkback-featured-row"><?php echo wp_kses_post( $featured_badge ); ?></div>
					<?php endif; ?>
				</div>
			</a>
		</li>
	<?php endforeach; ?>
</ul>
<?php if ( get_option( 'linkback_enable_signup_link', 1 ) ) :
	$signup_url = LinkBack_Link::get_signup_url();
	if ( ! empty( $signup_url ) ) : ?>
		<div class="linkback-signup-link-container">
			<a href="<?php echo esc_url( $signup_url ); ?>" class="linkback-signup-link"><?php esc_html_e( '➕ Submit Your Site', 'linkback' ); ?></a>
		</div>
	<?php endif;
endif; ?>
<?php
// Outgoing hit tracking via JS beacon so links can remain direct for SEO.
$track_nonce = wp_create_nonce( 'linkback_track_out' );
?>
<script>
(function() {
	var links = document.querySelectorAll('.linkback-card-link[data-linkback-id]');
	links.forEach(function(el) {
		el.addEventListener('click', function() {
			var id = this.getAttribute('data-linkback-id');
			var url = '<?php echo esc_url( home_url( '/' ) ); ?>?linkback_redirect=' + encodeURIComponent(id) + '&_wpnonce=<?php echo esc_js( $track_nonce ); ?>';
			if (navigator.sendBeacon) {
				navigator.sendBeacon(url);
			} else {
				var img = new Image();
				img.src = url;
			}
		});
	});
})();
</script>
